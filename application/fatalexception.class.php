<?php
	/* Asteroid
	 * class FatalException
	 * 
	 * An exception, but one that cannot be recovered from.
	 * Should only be caught by the script that runs the application (public/default.php).
	 */
	namespace Asteroid;
	class FatalException extends \Exception {
		protected $method = null;
		public function __construct($method, $message, $code = 0, $previous = null) {
			$this->method = (string)$method;
			parent::__construct($message, $code, $previous);
		}
		
		public function __toString() {
			return $this->method . "(): " . $this->message;
		}
	}
	