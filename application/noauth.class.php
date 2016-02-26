<?php
	/* Asteroid
	 * class NoAuth
	 * 
	 * This authentication handler is used when auth->handler is set to null
	 */
	namespace Asteroid;
	class NoAuth implements AuthenticationInterface {
		protected $application = null;
		protected $controller = null;
		
		// function __construct(): Creates a new Authentication object
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function controller(): Gets the authentication controller
		public function controller() {
			if(!is_object($this->controller) || (!$this->controller instanceof Controllers\NoAuth))
				$this->controller = new Controllers\NoAuth($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->controller, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->controller;
		}
		
		// function user(): Gets the current user or null if not authenticated
		public function user() {
			return null;
		}
		
		// function loggedin(): Checks if a user is logged in
		public function loggedin() {
			return false;
		}
	}
	