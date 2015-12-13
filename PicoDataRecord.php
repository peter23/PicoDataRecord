<?php

	class PicoDataRecord {

		public $__fields;

		public $__data;


		public function __construct($fields) {
			$this->__fields = $fields;
			$this->__data = array_fill_keys(array_keys($fields), null);
		}


		public function load($data) {
			foreach($this->__fields as $field_name=>&$field_data) {
				if(isset($data[$field_name])) {
					$this->__data[$field_name] = $data[$field_name];
				}
			}
		}


		public function __get($name) {
			if(!isset($this->__fields[$name])) {
				throw new Exception('PicoDataRecord.getField: Undefined field "'.$name.'"');
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
					$ret = null;
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
				throw new Exception('PicoDataRecord.getField: Undefined field "'.$name.'"');
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
