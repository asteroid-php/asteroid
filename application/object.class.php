<?php
	/* Asteroid
	 * class Object
	 * 
	 * Gets / sets an object / arrays values.
	 */
	namespace Asteroid;
	use JsonSerializable;
	use stdClass;
	class Object implements JsonSerializable {
		protected $data = null;
		
		// function __construct(): Merges object(s)/array(s) into an Object object
		public function __construct() {
			$this->data = new stdClass();
			
			foreach(func_get_args() as $merge)
				if(is_object($merge) || is_array($merge))
					foreach($merge as $key => $value)
						$this->set([ $key ], $value);
		}
		
		// function get(): Gets a value
		// $object->get([ "some_value" ]); // $object->some_value
		// $object->get([ "other_value", 1 ]); // $object->other_value->1
		// But if other_value is an array:
		// $object->get([ "other_value", 1 ]); // $object->other_value[1]
		public function get($name) {
			if(is_string($name) || is_int($name)) $name = Array($name);
			if(!is_array($name)) return null;
			
			$aset = false;
			
			$options = Array(&$this->data);
			$ek = 0;
			foreach($name as $i => $key) {
				if(is_object($options[$ek])) {
					if(!isset($options[$ek]->{$key}) && $aset) {
						$options[$ek]->{$key} = new stdClass();
						$options[$ek + 1] = &$options[$ek]->{$key};
					} elseif(!isset($options[$ek]->{$key}) && !$aset) $options[$ek + 1] = null;
					else $options[$ek + 1] = &$options[$ek]->{$key};
				} elseif(is_array($options[$ek])) {
					if(!isset($options[$ek][$key]) && $aset) {
						$options[$ek][$key] = Array();
						$options[$ek + 1] = &$options[$ek][$key];
					} elseif(!isset($options[$ek][$key]) && !$aset) $options[$ek + 1] = null;
					else $options[$ek + 1] = &$options[$ek][$key];
				} else {
					$options[$ek + 1] = &$options[$ek];
				}
				$ek++;
			}
			$option = &$options[$ek];
			
			return $option;
		}
		
		// function __get(): Gets a value using normal syntax
		public function __get($key) {
			return $this->get([ $key ]);
		}
		
		// function geta(): Gets values and puts them in an array
		// Example: list($_1, $_2) = $object->geta([ "_1" ], [ "_2" ]);
		public function geta() {
			$return = Array();
			foreach(func_get_args() as $key) {
				$return[] = $this->get($key);
			}
			
			return $return;
		}
		
		// function object(): Gets a value in an object instance
		public function object($name) {
			$reflection = new ReflectionClass(get_class($this));
			return $reflection->createInstanceArgs(call_user_func_array(Array($this, "geta"), func_get_args()));
		}
		
		// function check(): Checks if a value is really set
		public function check($name, $type = null, $class = null) {
			if(is_string($name) || is_int($name)) $name = Array($name);
			if(!is_array($name)) return null;
			
			$check = end($name);
			unset($name[key($name)]);
			
			$option = $this->get($name);
			
			if(!is_array($option) && !is_object($option)) return false;
			if(!array_key_exists($check, $optionarray = (array)$option))
				return false;
			
			if(!is_string($type))
				return true;
			elseif((gettype($optionarray[$check]) == "object") && is_string($class))
				return $optionarray[$check] instanceof $class ? true : false;
			elseif(gettype($optionarray[$check]) == $type)
				return true;
			else return false;
		}
		
		public function __isset($name) {
			return $this->check($name);
		}
		
		// function set(): Sets a value
		// $object->set([ "some_value" ], "new_value"); // $object->some_value = "new_value"
		// $object->set([ "other_value", 1 ], "new_value"); // $object->other_value->1 = "new_value"
		// But if other_value is an array:
		// $object->set([ "other_value", 1 ], "new_value"); // $object->other_value[1] = "new_value"
		// And if the current value is an object / array, and the new value is an object / array, the values will be merged
		// $object->set([ "array" ], Array("something")); // $object->array = array_merge((array)$object->array, Array("something"))
		public function set($name, $value) {
			if(is_string($name) || is_int($name)) $name = Array($name);
			if(!is_array($name)) return null;
			
			$aset = true;
			
			$options = Array(&$this->data);
			$ek = 0;
			foreach($name as $i => $key) {
				if(is_object($options[$ek])) {
					if(!isset($options[$ek]->{$key}) && $aset) {
						$options[$ek]->{$key} = new stdClass();
						$options[$ek + 1] = &$options[$ek]->{$key};
					} elseif(!isset($options[$ek]->{$key}) && !$aset) $options[$ek + 1] = null;
					else $options[$ek + 1] = &$options[$ek]->{$key};
				} elseif(is_array($options[$ek])) {
					if(!isset($options[$ek][$key]) && $aset) {
						$options[$ek][$key] = new stdClass();
						$options[$ek + 1] = &$options[$ek][$key];
					} elseif(!isset($options[$ek][$key]) && !$aset) $options[$ek + 1] = null;
					else $options[$ek + 1] = &$options[$ek][$key];
				} else {
					$options[$ek + 1] = &$options[$ek];
				}
				$ek++;
			}
			$option = &$options[$ek];
			
			/* if(is_object($option) && !($option instanceof Closure) && (is_object($value) || is_array($value))) {
				foreach($value as $k => $v) {
					if(is_object($v) && isset($option->{$k})) $option->{$k} = (object)array_merge((array)$option->{$k}, (array)$v);
					if(is_array($v) && isset($option->{$k})) $option->{$k} = (array)array_merge((array)$option->{$k}, (array)$v);
					else $option->{$k} = $v;
				}
			} elseif(is_array($option) && (is_object($value) || is_array($value))) {
				foreach($value as $k => $v) {
					if(is_object($v) && isset($option[$k])) $option[$k] = (object)array_merge((array)$option[$k], (array)$v);
					if(is_array($v) && isset($option[$k])) $option[$k] = (array)array_merge((array)$option[$k], (array)$v);
					else $option[$k] = $v;
				}
			} else */ $option = $value;
			
			if(isset($options[$ek - 1]) && is_object($options[$ek - 1]) && ($value === null))
				unset($options[$ek - 1]->{$key});
			elseif(isset($options[$ek - 1]) && is_array($options[$ek - 1]) && ($value === null))
				unset($options[$ek - 1][$key]);
			
			// Autosave
			$this->_runautosave();
		}
		
		public function __set($name, $value) {
			return $this->set($name, $value);
		}
		
		// function add(): Adds a value to an array
		// $object->set([ "some_value" ], "new_value"); // $object->some_value = "new_value"
		// $object->set([ "other_value", 1 ], "new_value"); // $object->other_value->1 = "new_value"
		// But if other_value is an array:
		// $object->set([ "other_value", 1 ], "new_value"); // $object->other_value[1] = "new_value"
		// And if the current value is an object / array, and the new value is an object / array, the values will be merged
		// $object->set([ "array" ], Array("something")); // $object->array = array_merge((array)$object->array, Array("something"))
		public function add($name, $value) {
			if(is_string($name) || is_int($name)) $name = Array($name);
			if(!is_array($name)) return null;
			
			$aset = true;
			
			$options = Array(&$this->data);
			$ek = 0;
			foreach($name as $i => $key) {
				if(is_object($options[$ek])) {
					if(!isset($options[$ek]->{$key}) && $aset) {
						$options[$ek]->{$key} = new stdClass();
						$options[$ek + 1] = &$options[$ek]->{$key};
					} elseif(!isset($options[$ek]->{$key}) && !$aset) $options[$ek + 1] = null;
					else $options[$ek + 1] = &$options[$ek]->{$key};
				} elseif(is_array($options[$ek])) {
					if(!isset($options[$ek][$key]) && $aset) {
						$options[$ek][$key] = new stdClass();
						$options[$ek + 1] = &$options[$ek][$key];
					} elseif(!isset($options[$ek][$key]) && !$aset) $options[$ek + 1] = null;
					else $options[$ek + 1] = &$options[$ek][$key];
				} else {
					$options[$ek + 1] = &$options[$ek];
				}
				$ek++;
			}
			$option = &$options[$ek];
			
			if($option === null)
				$option = Array();
			if(is_array($option))
				$option[] = $value;
			
			// Autosave
			$this->_runautosave();
		}
		
		public function __unset($name) {
			$this->set([ $name ], null);
		}
		
		// function tarray(): Converts $this into an array
		public function tarray() {
			$array = Array();
			foreach($this->data as $key => $value) {
				$array[$key] = $value;
			} return $array;
		}
		
		// function tobject(): Converts $this into an object of type $object
		public function tobject($object = null) {
			if(is_string($object)) $object = new $object();
			elseif(!is_object($object)) $object = new stdClass();
			foreach($this->data as $key => $value) {
				$object->{$key} = $value;
			} return $object;
		}
		
		// function _runautosave();
		protected function _runautosave() {
			
		}
		
		public function __destruct() {
			// Autosave
			$this->_runautosave();
		}
		
		// function __debugInfo(): Returns the data in this object
		public function __debugInfo() {
			return $this->tarray();
		}
		
		// function jsonSerialize(): Returns the data in this object
		public function jsonSerialize() {
			return $this->tarray();
		}
	}
	