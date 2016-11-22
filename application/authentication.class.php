<?php
	/* Asteroid
	 * class Authentication
	 * 
	 * Authentication, currently just a wrapper for my oauth client.
	 * 
	 * You can set a custom authentication handler in the configuration:
	 * Set auth->handler to an object that implements AuthenticationInterface
	 */
	namespace Asteroid;
	use Asteroid\Models\User as UserModel;
	use stdClass;
	class Authentication implements AuthenticationInterface {
		protected $application = null;
		protected $controller = null;
		protected $oauth = null;
		
		// function __construct(): Creates a new Authentication object
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			// Create an OAuth object
			$class = $this->application->configuration([ "oauth", "library_class" ]);
			$client_id = $this->application->configuration([ "oauth", "client_id" ]);
			$client_secret = $this->application->configuration([ "oauth", "client_secret" ]);
			$options = $this->application->configuration([ "oauth", "options" ]);
			if(is_object($options)) $options = (array)$options;
			if(!is_array($options)) $options = Array();
			
			$this->oauth = new $class($client_id, $client_secret, $options);
			
			$this->oauth->options("session_handler", Array(
				"check" => function($oauth) { return true; },
				"get" => function($key, $oauth) use($application) {
					return $application->session()->get([ "oauth", $key ]);
				},
				"set" => function($key, $value, $oauth) use($application) {
					$application->session()->set([ "oauth", $key ], $value);
				},
				"delete" => function($key, $oauth) use($application) {
					$application->session()->set([ "oauth", $key ], null);
				}
			));
			
			// Get access token
			if(is_string($token = $this->oauth->session("token")))
				$this->oauth->accessToken($token, false);
		}
		
		// function controller(): Gets the authentication controller
		public function controller() {
			if(!is_object($this->controller) || (!$this->controller instanceof Controllers\Authentication))
				$this->controller = new Controllers\Authentication($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->controller, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->controller;
		}
		
		// function user(): Gets the current user or null if not authenticated
		public function user() {
			if(!is_string($this->oauth->accessToken()))
				return UserModel::cfd($this->application, new stdClass());
			
			try { return UserModel::cfd($this->application, $this->oauth->userProfile()); }
			catch(\Exception $error) { return UserModel::cfd($this->application, new stdClass()); }
		}
		
		// function loggedin(): Checks if a user is logged in
		public function loggedin(&$user = null) {
			$user = $this->user();
			if($user->id !== null) return true;
			else return false;
		}
		
		// function oauth(): Returns the oauth object
		public function oauth() {
			return $this->oauth;
		}
	}
	