<?php
	/* Asteroid
	 * class Events
	 * 
	 * Allows registering and executing custom handlers for events.
	 */
	namespace Asteroid;
	class Events {
		protected $application = null;
		protected $events = Array();
		
		// function __construct(): Creates a new Events object
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function bind(): Binds a function to an event
		public function bind($event, $callback) {
			if(!is_string($event)) return false;
			if(!is_callable($callback)) return false;
			if(!isset($this->events[$event])) $this->events[$event] = Array();
			$this->events[$event][] = $callback;
			return true;
		}
		
		// function unbind(): Removes all callbacks of an event
		public function unbind($event) {
			if(!is_string($event)) return false;
			$this->events[$event] = Array();
			return true;
		}
		
		// function trigger(): Triggers an event - will return false if any callback returns false
		public function trigger($event, $params = Array(), &$status = null) {
			if(!is_string($event)) return false;
			if(!is_array($params)) return false;
			$return = true;
			$status = Array();
			foreach($this->getHandlers($event) as $callback)
				try {
					$r = call_user_func_array($callback, array_merge(Array($this->application), $params));
					if($r === false) $return = false;
					elseif(is_object($r) && ($r instanceof BaseStatus))
						$status[] = $r;
					else $status[] = true;
				} catch(BaseStatus $status) {
					$status[] = $status;
				} catch(\Exception $exception) {
					$status[] = $exception;
					$return = false;
				}
			
			return $return;
		}
		
		// function triggerR(): Triggers an event and returns an array of returns
		public function triggerR($event, $params = Array()) {
			if(!is_string($event)) return false;
			if(!is_array($params)) return false;
			$return = Array();
			foreach($this->getHandlers($event) as $callback)
				try {
					$return[] = call_user_func_array($callback, array_merge(Array($this->application), $params));
				} catch(BaseStatus $status) {
					$return[] = $status;
				} catch(\Exception $exception) {
					$return[] = $exception;
				}
			
			return $return;
		}
		
		// function getHandlers(): Returns all handlers bound to an event
		public function getHandlers($event = null) {
			if(!is_array($this->events))
				return Array();
			elseif(!is_string($event))
				return $this->events;
			elseif(!isset($this->events[$event]) || !is_array($this->events[$event]))
				return Array();
			else return $this->events[$event];
		}
	}
	