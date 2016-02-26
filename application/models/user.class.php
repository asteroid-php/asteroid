<?php
	/* Asteroid
	 * User model
	 * 
	 */
	namespace Asteroid\Models;
	use Asteroid\Application;
	use Asteroid\Exception;
	use stdClass;
	class User {
		protected $application = null;
		protected $user = null;
		protected static $cfdu = null;
		
		// function __construct(): Creates a new user object
		public function __construct($application, $id = null) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			if(self::$cfdu !== null) {
				$this->user = self::$cfdu;
				return null;
			} self::$cfdu = null;
			
			$oauth = $this->application->authentication()->oauth();
			try {
				if(is_string($id)) {
					$request = $oauth->api("GET", "/user/" . urlencode($id));
					$request->execute();
					$this->user = $request->responseObject();
				} else $this->user = $oauth->userProfile();
			} catch(\Exception $error) { $this->user = null; }
		}
		
		// function getID(): Gets this user's id
		public function getID() {
			return $this->__get("id");
		}
		
		// function getUsername(): Gets this user's username
		public function getUsername() {
			return $this->__get("username");
		}
		
		// function get(): Returns the user object
		public function get() {
			return $this->user;
		}
		
		// function __get(): Gets user information
		public function __get($key) {
			if(isset($this->user->{$key})) return $this->user->{$key};
			else return null;
		}
		
		// function __set(): Does nothing
		public function __set($key, $value) {}
		
		// function __isset(): Returns true - everything here has a default value of null
		public function __isset($key) {
			if(!method_exists($this, $key)) return true;
			else return false;
		}
		
		// function __unset(): Does nothing
		public function __unset($key) {}
		
		// function valid(): Checks if the user is valid (not a placeholder for "not logged in")
		public function valid() {
			if($this->__get("id") !== null) return true;
			else return false;
		}
		
		// function loggedin(): Checks if this user is the logged in user
		public function loggedin() {
			if(!$this->valid()) return false;
			if($this->application->authentication()->loggedin($user) && ($user->id == $this->getID())) return true;
			else return false;
		}
		
		// function admin(): Checks if this user is an admin
		public function admin() {
			$admins = $this->application->configuration([ "auth", "admins" ]);
			if(in_array($this->getUsername(), $admins)) return true;
			else return false;
		}
		
		// function cfd(): Creates a user object with a user's data - for use by authentication class
		public static function cfd($application, $user) {
			self::$cfdu = $user;
			return new self($application);
		}
	}
	