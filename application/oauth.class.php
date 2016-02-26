<?php
	/* Asteroid
	 * class OAuth
	 * 
	 * Extends samuelthomas2774/oauth-client to change session functions.
	 */
	namespace Asteroid;
	use OAuthST;
	class OAuth extends OAuthST {
		protected $application = null;
		
		// function __construct(): Creates a new OAuth object
		public function __construct($application, $client_id, $client_secret, $options = Array()) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			if(!is_array($options))
				$options = Array();
			
			parent::__construct($client_id, $client_secret, $options);
		}
		
		// function sessions(): Checks if sessions are enabled
		public function sessions() {
			// Get session_prefix
			// If not a string or false reset to default
			if(!is_string($session_prefix = $this->options("session_prefix")) && ($session_prefix !== false))
				$this->options("session_prefix", $session_prefix = "st_");
			
			if($session_prefix === false)
				// Sessions are diabled
				return false;
			else
				// Sessions are enabled and one is active
				return true;
		}
		
		// function session(): Gets/sets session data
		public function session($name) {
			$params = func_get_args();
			
			// Check if sessions are enabled
			if(!$this->sessions()) return null;
			$session_prefix = $this->options([ "session_prefix" ]);
			
			if(array_key_exists(1, $params))
				// Set / delete
				$return = $this->application->session()->set(Array($session_prefix . $name), $params[1]);
			else
				// Get
				return $this->application->session()->get(Array($session_prefix . $name));
			
			return $return;
		}
	}
	