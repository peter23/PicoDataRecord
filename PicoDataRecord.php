<?php

/**
 * PicoDataRecord filters, formats, encodes data records for web
 *
 * For more information @see readme.md
 *
 * @link https://github.com/peter23/PicoDataRecord
 * @author i@peter23.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */


	class PicoDataRecord {

		public $__data;
		public $__fields;


		public function __construct($fields) {
			$this->__fields = $fields;
			$this->__data = array_fill_keys(array_keys($fields), null);
		}


		//LOAD
		public function rawLoad($data) {
			foreach($this->__fields as $field_name=>&$field_data) {
				if(isset($data[$field_name])) {
					$this->__data[$field_name] = $data[$field_name];
				}
			}
		}

		public function load($data) {
			$this->rawLoad($data);
			return $this->filter();
		}


		//CONVERT AND FILTER
		public function convertVal($val, &$field_data) {
			//we will process any value as array value
			if(!is_array($val)) {
				$val = array($val);
			}

			//if it is array value and number of elements is defined, then fill all non-existent elements with null
			if(isset($field_data['array']) && $field_data['array']) {
				for($i = 0; $i < $field_data['array']; $i++) {
					if(!isset($val[$i]))  $val[$i] = null;
				}
			}

			//process value
			switch($field_data['type']) {

				case 'date':
					foreach($val as &$val1) {
						$val1 = $this->strtotime_ex($val1);
						if($val1) {
							$val1 = date('Y-m-d', $val1);
						}
					}
					break;

				case 'datetime':
					foreach($val as &$val1) {
						$val1 = $this->strtotime_ex($val1);
						if($val1) {
							$val1 = date('Y-m-d H:i:s', $val1);
						}
					}
					break;

				case 'list':
					foreach($val as &$val1) {
						if(!isset($field_data['values'][$val1])) {
							$val1 = null;
						}
					}
					break;

				case 'number':
					foreach($val as &$val1) {
						$val1 = str_replace(array(' ', "\t", "\n", "\r"), '', $val1);
						if(strlen($val1)) {
							$val1 = str_replace(',', '.', $val1);
							$val1 = floatval($val1);
						} else {
							$val1 = null;
						}
					}
					break;

				case 'text':
					break;

				default:
					throw new Exception('PicoDataRecord.convertVal: Unknown type "'.$field_data['type'].'"');
					break;

			}

			if(!isset($field_data['array'])) {
				return $val[0];
			} else {
				return $val;
			}
		}

		public function filterVal($val, $filter) {
			//we will process any value as array value
			if(!is_array($val)) {
				$val = array($val);
				$no_array = true;
			}

			switch($filter) {

				case 'delete_empty':
					//delete empty elements (for array)
					$new_val = array();
					foreach($val as $val_n=>$val1) {
						if(strlen($val1)) {
							$new_val[$val_n] = $val1;
						}
					}
					$val = $new_val;
					break;

				case 'required':
					//required at least one
					foreach($val as &$val1) {
						if(strlen($val1)) {
							//go out from foreach and switch
							break 2;
						}
					}
					throw new PicoDataRecordFilterException;
					break;

				case 'required_all':
					//required all (for array)
					foreach($val as &$val1) {
						if(!strlen($val1)) {
							throw new PicoDataRecordFilterException;
						}
					}
					break;

				case 'trim':
					foreach($val as &$val1) {
						$val1 = trim($val1);
					}
					break;

				case 'email':
					foreach($val as &$val1) {
						if(!preg_match('#^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,8}$#', $val1)) {
							throw new PicoDataRecordFilterException;
						}
					}
					break;

				default:
					throw new Exception('PicoDataRecord.filterVal: Unknown filter "'.$filter.'"');
					break;

			}

			if(isset($no_array)) {
				return $val[0];
			} else {
				return $val;
			}
		}

		public function filter() {
			$ret = array();
			foreach($this->__fields as $field_name=>&$field_data) {
				$this->__data[$field_name] = $this->convertVal($this->__data[$field_name], $field_data);

				if(isset($field_data['filter'])) {
					foreach($field_data['filter'] as $filter) {
						try {
							$this->__data[$field_name] = $this->filterVal($this->__data[$field_name], $filter);
						} catch(PicoDataRecordFilterException $e) {
							$ret[] = array($field_name, $filter);
						}
					}
				}
			}
			return $ret;
		}


		//OUTPUT
		public function __get($name) {
			if(!isset($this->__fields[$name])) {
				throw new Exception('PicoDataRecord.__get: Undefined field "'.$name.'"');
			}

			$val = $this->__data[$name];
			//we will process any value as array value
			if(!is_array($val)) {
				$val = array($val);
				$no_array = true;
			}

			switch($this->__fields[$name]['type']) {
				case 'date':
				case 'datetime':
				case 'number':
				case 'text':
					foreach($val as &$val1) {
						$val1 = $this->encodeField($name, $val1);
					}
					break;

				case 'list':
					foreach($val as &$val1) {
						$val1 = array(
							$this->encodeField($name, $val1),
							$this->encodeField($name, isset($this->__fields[$name]['values'][$val1]) ? $this->__fields[$name]['values'][$val1] : null),
						);
					}
					break;

				default:
					throw new Exception('PicoDataRecord.__get: Unknown field type "'.$this->__fields[$name]['type'].'"');
					break;
			}

			if(isset($no_array)) {
				return $val[0];
			} else {
				return $val;
			}
		}


		public function allValues($name) {
			if(!isset($this->__fields[$name])) {
				throw new Exception('PicoDataRecord.allValues: Undefined field "'.$name.'"');
			}

			if(!isset($this->__fields[$name]['values'])) {
				throw new Exception('PicoDataRecord.allValues: Field "'.$name.'" do not have values');
			}

			return $this->encodeField($name, $this->__fields[$name]['values']);
		}


		public function encodeVal($enc_type, $value) {
			if($enc_type == 'html') {
				$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			} elseif($enc_type == 'url') {
				$value = urlencode($value);
			}
			return $value;
		}

		public function encodeField($name, $value) {
			if(!isset($this->__fields[$name])) {
				throw new Exception('PicoDataRecord.encodeField: Undefined field "'.$name.'"');
			}

			if(isset($this->__fields[$name]['format'])) {
				switch($this->__fields[$name]['type']) {
					case 'date':
					case 'datetime':
						if($value) {
							$value = strtotime($value);
							if($value) {
								$value = date($this->__fields[$name]['format'], $value);
							}
						}
						break;
					case 'number':
						if(strlen($value)) {
							$frmt = explode('|', $this->__fields[$name]['format']);
							array_unshift($frmt, $value);
							$value = call_user_func_array('number_format', $frmt);
						}
						break;
				}
			}

			$enc_type = isset($this->__fields[$name]['encode']) ? $this->__fields[$name]['encode'] : 'html';
			if(!is_array($value)) {
				$value = $this->encodeVal($enc_type, $value);
			} else {
				$new_value = array();
				foreach($value as $value_n=>$value1) {
					$new_value[$this->encodeVal($enc_type, $value_n)] = $this->encodeVal($enc_type, $value1);
				}
				$value = $new_value;
			}

			return $value;
		}

		public function strtotime_ex($s) {
			$s = trim($s);
			if(preg_match('#(\d+\.){2}\d+#', $s, $m)) {
				$m0 = $m[0];
				$m0 = explode('.', $m0);
				$m0 = $m0[1].'/'.$m0[0].'/'.$m0[2];
				$s = str_replace($m[0], $m0, $s);
			}
			return strtotime($s);
		}

	}


	class PicoDataRecordFilterException extends Exception { }
