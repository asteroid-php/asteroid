<?php
	/* Asteroid
	 * Request Class
	 * 
	 * Gets request variables.
	 */
	namespace Asteroid;
	class Request {
		protected $application = null;
		
		// Get, post and cookie variables
		private $http_method = null;
		private $http_headers = null;
		private $http_query = Array();
		private $http_get = Array();
		private $http_post = Array();
		private $http_cookie = Array();
		
		// function __construct(): Creates a new Request
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			$this->http_method = strtoupper($_SERVER["REQUEST_METHOD"]);
			
			// Get the "real" request query string
			if(strpos($_SERVER["REQUEST_URI"], "?") === false) $query_string = "";
			else $query_string = substr($_SERVER["REQUEST_URI"], strpos($_SERVER["REQUEST_URI"], "?") + 1);
			parse_str($query_string, $this->http_query);
			
			$this->http_get = $_GET;
			$this->http_post = $_POST;
			$this->http_cookie = $_COOKIE;
		}
		
		// function method(): Returns the request method
		public function method($method = null) {
			if(is_string($method) && (strtoupper($method) == $this->http_method)) return true;
			elseif(is_string($method)) return false;
			else return $this->http_method;
		}
		
		// function header(): Returns a http header
		public function header($name) {
			// Get http headers
			$headers = $this->headers();
			
			foreach($headers as $key => $value)
				if(strtolower($name) == strtolower($key))
					return $value;
			
			return null;
		}
		
		// function headers(): Gets all http request headers
		public function headers() {
			if(is_object($this->http_headers))
				return $this->http_headers;
			
			$headers = new Object();
			foreach($_SERVER as $key => $value) {
				if(substr(strtolower($key), 0, 5) == "http_") {
					$hkey = substr(strtolower($key), 5);
					
					// Standardize $name
					$hkey = preg_replace("/^([^a-zA-Z0-9]*)$/", " ", $hkey);
					$hkey = ucwords($hkey);
					$hkey = str_replace(" ", "-", $hkey);
					
					$headers->{$hkey} = $value;
				}
			}
			
			return $headers;
		}
		
		// function host(): Returns the http host header
		public function host() {
			return $this->header("Host");
		}
		
		// function ip(): Returns the source ip address and port number
		public function ip($port = false) {
			if(isset($_SERVER["REMOTE_ADDR"]))
				$ip = $_SERVER["REMOTE_ADDR"];
			else $ip = "0.0.0.0";
			
			if($port === true)
				$ip .= ":" . $this->port();
			
			return $ip;
		}
		
		// function ipinfo(): Returns information about an ip address by ipinfo.io
		public function ipinfo($ip = null) {
			if(!is_string($ip) || !preg_match("/^((([0-2]?[0-9])?[0-9])(\.([0-2]?[0-9])?[0-9]){3})$/", $ip))
				$ip = $this->ip();
			
			$request = $this->application->http("GET", "http://ipinfo.io/{$ip}/json");
			$request->execute();
			return $request->responseObject();
		}
		
		// function port(): Returns the port number
		public function port() {
			if(isset($_SERVER["REMOTE_PORT"]))
				return (int)$_SERVER["REMOTE_PORT"];
			else return 0;
		}
		
		// function get(): Returns a get parameter
		public function get($key = null) {
			if($key === null) return $this->http_get;
			elseif(is_string($key)) return isset($this->http_get[$key]) ? $this->http_get[$key] : null;
			else return null;
		}
		
		// function query(): Returns a "real" get parameter
		public function query($key = null) {
			if($key === null) return $this->http_query;
			elseif(is_string($key)) return isset($this->http_query[$key]) ? $this->http_query[$key] : null;
			else return null;
		}
		
		// function post(): Returns a post parameter
		public function post($key = null) {
			if($key === null) return $this->http_post;
			elseif(is_string($key)) return isset($this->http_post[$key]) ? $this->http_post[$key] : null;
			else return null;
		}
		
		// function cookie(): Returns a cookie parameter
		public function cookie($key = null) {
			if($key === null) return $this->http_cookie;
			elseif(is_string($key)) return isset($this->http_cookie[$key]) ? $this->http_cookie[$key] : null;
			else return null;
		}
	}
	