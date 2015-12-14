<?php

	class PicoDataRecord {

		public $__fields;

		public $__data;


		public function __construct($fields) {
			$this->__fields = $fields;
			$this->__data = array_fill_keys(array_keys($fields), null);
		}


		public function rawLoad($data) {
			foreach($this->__fields as $field_name=>&$field_data) {
				if(isset($data[$field_name])) {
					$this->__data[$field_name] = $data[$field_name];
				}
			}
		}


		public function filter() {
			$ret = array();
			foreach($this->__fields as $field_name=>&$field_data) {

				//for each field:

				//converting
				switch($field_data['type']) {
					case 'date':
						$this->__data[$field_name] = strtotime($this->__data[$field_name]);
						if($this->__data[$field_name]) {
							$this->__data[$field_name] = date('Y-m-d', $this->__data[$field_name]);
						}
						break;
					case 'datetime':
						$this->__data[$field_name] = strtotime($this->__data[$field_name]);
						if($this->__data[$field_name]) {
							$this->__data[$field_name] = date('Y-m-d H:i:s', $this->__data[$field_name]);
						}
						break;
					case 'list':
						if(!isset($field_data['values'][$this->__data[$field_name]])) {
							$this->__data[$field_name] = null;
						}
						break;
					case 'number':
						$this->__data[$field_name] = trim($this->__data[$field_name]);
						if(strlen($this->__data[$field_name])) {
							$this->__data[$field_name] = str_replace(',', '.', $this->__data[$field_name]);
							$this->__data[$field_name] = floatval($this->__data[$field_name]);
						} else {
							$this->__data[$field_name] = null;
						}
						break;
				}

				//filtering
				if(isset($field_data['filter'])) {
					foreach($field_data['filter'] as $filter) {
						//for each filter:
						$err = false;
						switch($filter) {
							case 'required':
								if(!strlen($this->__data[$field_name])) {
									$err = true;
								}
								break;

							case 'trim':
								$this->__data[$field_name] = trim($this->__data[$field_name]);
								break;

							default:
								throw new Exception('PicoDataRecord.filter: Unknown filter "'.$filter.'"');
								break;
						}
						if($err) {
							$ret[] = array($field_name, $filter);
						}
					}  //foreach filter
				}

			}  //foreach field
			return $ret;
		}


		public function load($data) {
			$this->rawLoad($data);
			return $this->filter();
		}


		public function __get($name) {
			if(!isset($this->__fields[$name])) {
				throw new Exception('PicoDataRecord.__get: Undefined field "'.$name.'"');
			}

			switch($this->__fields[$name]['type']) {
				case 'date':
				case 'datetime':
				case 'number':
				case 'text':
					$ret = $this->encodeField($name, $this->__data[$name]);
					break;

				case 'list':
					$ret = array(
						$this->encodeField($name, $this->__data[$name]),
						$this->encodeField($name, $this->__fields[$name]['values'][$this->__data[$name]]),
					);
					break;

				default:
					throw new Exception('PicoDataRecord.__get: Unknown field type "'.$this->__fields[$name]['type'].'"');
					break;
			}

			return $ret;
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

			if(isset($this->__fields[$name]['encode'])) {
				$enc_type = $this->__fields[$name]['encode'];
				if(!is_array($value)) {
					$value = $this->encodeVal($enc_type, $value);
				} else {
					$new_value = array();
					foreach($value as $value_n=>$value1) {
						$new_value[$this->encodeVal($enc_type, $value_n)] = $this->encodeVal($enc_type, $value1);
					}
					$value = $new_value;
				}
			}

			return $value;
		}


		public function encodeVal($enc_type, $value) {
			if($enc_type == 'html') {
				$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			} elseif($enc_type == 'url') {
				$value = urlencode($value);
			}
			return $value;
		}

	}
