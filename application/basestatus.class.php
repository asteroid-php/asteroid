<?php
	/* Asteroid
	 * class BaseStatus
	 * 
	 * Returned / thrown when something is run successfully.
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
	}
	