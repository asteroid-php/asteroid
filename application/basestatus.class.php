<?php
	/* Asteroid
	 * class BaseStatus
	 * 
	 * Returned / thrown when something is run.
	 */
	namespace Asteroid;
	abstract class BaseStatus extends Exception {
		protected $application = null;
		protected $code = null;
		protected $message = null;
		protected $message_type = "neutral";
		
		public function __construct($application) {
			$this->application = $application;
		}
		
		public function getStatus() {
			return (object)Array(
				"code" => $this->code
			);
		}
		
		public final function _continue() {
			if(is_int($this->code))
				$this->application->response()->code($this->code);
			if(is_string($this->message))
				$this->application->message($this->message, $this->message_type);
			
			return $this->continuestatus();
		}
		
		public function continuestatus() {
			
		}
		
		public function __debugInfo() {
			return (array)$this->getStatus();
		}
		
		public function status($type = "PlainStatus") {
			return $this->application->status($type);
		}
		
		public function message($message, $type = "neutral") {
			return $this->application->message($message, $type);
		}
		
		public function success($message) {
			return $this->application->success($message);
		}
		
		public function error($message, $error = false) {
			return $this->application->message($message, $error);
		}
		
		public function view() {
			return $this->application->view();
		}
		
		public function __get($name) {
			if($name == "view")
				return $this->view();
		}
	}
	