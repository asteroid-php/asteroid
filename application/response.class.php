<?php
	/* Asteroid
	 * class Response
	 * 
	 * Modifies the http response headers and body.
	 */
	namespace Asteroid;
	class Response {
		protected $application = null;
		protected $codes = Array(
			100 => "Continue",
			101 => "Switching Protocols",
			102 => "Processing",
			200 => "OK",
			201 => "Created",
			202 => "Accepted",
			203 => "Non-Authoritative Information",
			204 => "No Content",
			205 => "Reset Content",
			206 => "Partial Content",
			207 => "Multi-Status",
			300 => "Multiple Choices",
			301 => "Moved Permanently",
			302 => "Found",
			303 => "See Other",
			304 => "Not Modified",
			305 => "Use Proxy",
			306 => "Switch Proxy",
			307 => "Temporary Redirect",
			400 => "Bad Request",
			401 => "Unauthorized",
			402 => "Payment Required",
			403 => "Forbidden",
			404 => "Not Found",
			405 => "Method Not Allowed",
			406 => "Not Acceptable",
			407 => "Proxy Authentication Required",
			408 => "Request Timeout",
			409 => "Conflict",
			410 => "Gone",
			411 => "Length Required",
			412 => "Precondition Failed",
			413 => "Request Entity Too Large",
			414 => "Request-URI Too Long",
			415 => "Unsupported Media Type",
			416 => "Requested Range Not Satisfiable",
			417 => "Expectation Failed",
			418 => "I'm a teapot",
			422 => "Unprocessable Entity",
			423 => "Locked",
			424 => "Failed Dependency",
			425 => "Unordered Collection",
			426 => "Upgrade Required",
			449 => "Retry With",
			450 => "Blocked by Windows Parental Controls",
			500 => "Internal Server Error",
			501 => "Not Implemented",
			502 => "Bad Gateway",
			503 => "Service Unavailable",
			504 => "Gateway Timeout",
			505 => "HTTP Version Not Supported",
			506 => "Variant Also Negotiates",
			507 => "Insufficient Storage",
			509 => "Bandwidth Limit Exceeded",
			510 => "Not Extended"
		);
		
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function code(): Sends a http response code
		public function code($code) {
			if(!is_int($code))
				throw new Exception(__METHOD__, "\$code must be an integer.");
			
			if(!isset($this->codes[$code]))
				throw new Exception(__METHOD__, "\$name and \$value must be strings.");
			
			if(headers_sent()) return false;
			header("{$_SERVER["SERVER_PROTOCOL"]} {$code} {$this->codes[$code]}", null, $code);
			return $this;
		}
		
		// function header(): Sends a http header - returns false if headers have already been sent
		public function header($name, $value) {
			if(!is_string($name) && !is_string($value))
				throw new Exception(__METHOD__, "\$name and \$value must be strings.");
			
			if(headers_sent()) return false;
			header($name . ": " . $value, true);
			return $this;
		}
		
		// function cookie(): Sends a http cookie header
		public function cookie($name, $value, $options = Array()) {
			if($value === null) // Delete the cookie, sets the expire header to 1st January 1970 12:00:01 am
				$options["expires"] = 1;
			if(isset($options["time"])) // Set expires, where time is how long the cookie will last until deleted
				$options["expires"] = time() - (int)$options["time"];
			
			// Create the cookie header
			// setcookie(): $name, $value, $expires_in_seconds, $path, $host, $https_only, $disallow_javascript
			setcookie($name, is_string($value) ? $value : "", isset($options["expires"]) ? $options["expires"] : 0,
				isset($options["path"]) ? $options["path"] : $this->application->configuration([ "path" ]),
				isset($options["host"]) ? $options["host"] : $this->application->configuration([ "host" ]),
				isset($options["secure"]) ? $options["secure"] : false, isset($options["http"]) ? $options["http"] : false
			);
		}
		
		// function add(): Adds something to the output
		public function add() {
			echo implode("", func_get_args());
			return $this;
		}
		
		// function send(): Stops the script
		public function send() {
			exit(implode("", func_get_args()));
		}
	}
	