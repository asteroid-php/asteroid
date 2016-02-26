<?php
	/* Asteroid
	 * class Object
	 * 
	 * Gets / sets an object / arrays values.
	 */
	namespace Asteroid;
	use stdClass;
	class Object {
		// function __construct(): Merges object(s)/array(s) into an Object object
		public function __construct() {
			foreach(func_get_args() as $merge) if(is_object($merge) || is_array($merge)) {
				foreach($merge as $key => $value)
					$this->set([ $key ], $value);
			}
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
			
			$options = Array(&$this);
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
			if(isset($this->{$key})) return $this->{$key};
			else return null;
		}
		
		// function geta(): Gets values and puts them in an array
		// Example: list($_1, $_2) = $object->geta([ "_1" ], [ "_2" ]);
		/*public function geta() {
			$return = Array();
			foreach(func_get_args() as $key) {
				$return[] = $this->get($key);
			}
			
			return $return;
		}*/
		
		// function check(): Checks if a value is really set
		public function check($name) {
			if(is_string($name) || is_int($name)) $name = Array($name);
			if(!is_array($name)) return null;
			
			$check = end($name);
			unset($name[key($name)]);
			
			$option = $this->get($name);
			
			if(!is_array($option) && !is_object($option)) return false;
			if(array_key_exists($check, (array)$option)) return true;
			else return false;
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
			
			$options = Array(&$this);
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
			
			if(is_object($option) && !($option instanceof Closure) && (is_object($value) || is_array($value))) {
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
			} else $option = $value;
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
			
			$options = Array(&$this);
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
		}
		
		// function tarray(): Converts $this into an array
		public function tarray() {
			$array = Array();
			foreach($this as $key => $value) {
				$array[$key] = $value;
			} return $array;
		}
		
		// function tobject(): Converts $this into an object of type $object
		public function tobject($object) {
			if(is_string($object)) $object = new $object();
			elseif(!is_object($object)) $object = new Object();
			foreach($this as $key => $value) {
				$object->{$key} = $value;
			} return $object;
		}
	}
	