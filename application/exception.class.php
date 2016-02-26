<?php
	/* Asteroid
	 * class Exception
	 * 
	 * Handles all exceptions.
	 */
	namespace Asteroid;
	class Exception extends \Exception {
		protected $method = null;
		public function __construct($method, $message, $code = 0, $previous = null) {
			$this->method = (string)$method;
			parent::__construct($message, $code, $previous);
		}
		
		public function __toString() {
			return $this->method . "(): " . $this->message;
		}
	}
	