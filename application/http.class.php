<?php
	/* Asteroid
	 * class HTTP
	 * 
	 * Makes HTTP[S] requests with curl.
	 */
	namespace Asteroid;
	use stdClass;
	class HTTP {
		// Array $request: An array of information about the request
		protected $request = Array(
			"method" => null,
			"url" => null,
			"params" => Array(),
			"headers" => Array()
		);
		
		// Array $response: An array of information about the response - this is filled when the request is executed
		protected $response = Array(
			"headers" => null,
			"body" => null,
			"curl" => null
		);
		
		// CURL $curl: A curl handler for the request
		protected $curl = null;
		
		// Constants
		const TEXT = 10;
		const JSON_ARRAY = 21;
		const JSON_OBJECT = 22;
		const QUERYSTRING_ARRAY = 31;
		const QUERYSTRING_OBJECT = 32;
		const XML_ARRAY = 41;
		const XML_OBJECT = 42;
		const XML_SIMPLEXML = 43;
		
		// function __construct(): Creates a new HTTP object
		public function __construct($application, $method, $url, $params = Array(), $headers = Array()) {
			// Store method in HTTP::request["method"]
			if(!in_array($method, Array("GET", "POST", "PUT", "DELETE"))) throw new Exception(__METHOD__, "\$method must be either GET, POST, PUT or DELETE.");
			else $this->request["method"] = $method;
			
			// Store url in HTTP::request["url"]
			if(!is_string($url)) throw new Exception(__METHOD__, "\$url must be a string.");
			else $this->request["url"] = $url;
			
			// Store params in HTTP::request["params"]
			if(($method == "PUT") && !is_string($params)) $this->request["params"] = "";
			elseif(($method != "PUT") && !is_array($params)) $this->request["params"] = Array();
			else $this->request["params"] = $params;
			
			// Store headers in HTTP::request["headers"]
			if(!is_array($headers)) $this->request["headers"] = Array();
			else $this->request["headers"] = $headers;
		}
		
		// function execute(): Executes the request
		public function execute() {
			// Create a curl handle, if none already exists
			if($this->curl === null) $this->curl = curl_init();
			
			// Append parameters to the url for GET and DELETE requests
			if(in_array($this->request["method"], Array("GET", "DELETE"))) {
				if(strpos($this->request["url"], "?") !== false)
					$url = $this->request["url"] . "&" . http_build_query($this->request["params"]);
				else $url = $this->request["url"] . "?" . http_build_query($this->request["params"]);
			} else {
				$url = $this->request["url"];
			}
			
			curl_setopt($this->curl, CURLOPT_URL, $url);
			curl_setopt($this->curl, CURLOPT_HEADER, false);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			
			if($this->request["method"] == "GET") {
				// Method is always GET by default, so we don't need to do anything
			} elseif($this->request["method"] == "POST") {
				curl_setopt($this->curl, CURLOPT_POST, true);
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->request["params"]);
			} elseif($this->request["method"] == "PUT") {
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->request["params"]);
			} elseif($this->request["method"] == "DELETE") {
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
			}
			
			// Headers
			$i = 0; $headers = Array();
			curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function($ch, $header) use(&$i, &$headers) {
				if(strlen(trim($header)) == 0) return strlen($header);
				
				if(strpos($header, ": ") !== false) {
					list($key, $value) = explode(": ", $header, 2);
					$key = trim($key); $value = trim($value);
					
					$key = explode("-", $key);
					foreach($key as $_1 => $_2) $key[$_1] = ucfirst($_2);
					$key = implode("-", $key);
					
					$headers[$i] = trim($header);
					$headers[$key] = $value;
				} else {
					$headers[$i] = trim($header);
				}
				
				$i++;
				return strlen($header);
			});
			
			$curl_response = curl_exec($this->curl);
			$this->response["headers"] = $headers;
			$this->response["body"] = $curl_response;
			$this->response["curl"] = curl_getinfo($this->curl);
			
			// Return success
			if($curl_response !== false) return true;
			else return false;
		}
		
		// function request(): Returns information about the request
		public function request() {
			return $this->request;
		}
		
		// function parameter(): Returns / sets a parameter
		public function parameter($name, $value = null) {
			if(func_num_args() >= 2) $this->request["params"][$name] = $value;
			else return isset($this->request["params"][$name]) ? $this->request["params"][$name] : null;
		}
		
		// function header(): Returns / sets a parameter
		public function header($name, $value = null) {
			if(func_num_args() >= 2) $this->request["headers"][$name] = $value;
			else return isset($this->request["headers"][$name]) ? $this->request["headers"][$name] : null;
		}
		
		// function auth(): Returns / sets http authentication
		public function auth($username = null, $password = null) {
			if(is_string($username) && is_string($password))
				$this->header("Authorization", "Basic " . base64_encode("{$username}:{$password}"));
			elseif(is_string($username)) $this->header("Authorization", $username);
			else return $this->header("Authorization");
		}
		
		// function __toString(): Returns the request and response as a string
		public function __toString() {
			// Request
			if(in_array($this->request["method"], Array("GET", "DELETE"))) {
				$query = http_build_query($this->request["params"]);
				if(strlen($query) > 0) $url = $this->request["url"] . (strpos($this->request["url"], "?") !== false ? "&" : "?") . $query;
				else $url = $this->request["url"];
			} else $url = $this->request["url"];
			$url = (object)parse_url($url);
			
			$request_headers = Array();
			$request_headers[] = "HTTP/1.1 " . $url->path . (isset($url->query) && !empty($url->query) ? "?" . $url->query : "") . " " . $this->request["method"];
			foreach($this->request["headers"] as $key => $value) $request_headers[] = trim($key) . ": " . trim($value) . "\n";
			
			$request_body = $request_headers;
			if($this->request["method"] == "POST") $request_body .= "\n\n" . http_build_query($this->request["params"]);
			elseif($this->request["method"] == "PUT") $request_body .= "\n\n" . $this->request["params"];
			
			// Response
			if($this->response["body"] !== null) {
				$response_headers = Array();
				foreach($this->response["headers"] as $key => $value) if(is_int($key)) $response_headers[] = $value;
				$response_headers = implode("\n", $response_headers);
				
				$response_body = $response_headers;
				$response_body .= "\n\n" . $this->response["body"];
			}
			
			// Return
			return $request_body . (isset($response_body) ? "\n\n----------\n\n" . $response_body : "");
		}
		
		// function response(): Returns the response as a string
		public function response($response_type = HTTP::TEXT) {
			switch($response_type) {
				default: case HTTP::TEXT: return $this->response["body"]; break;
				case HTTP::JSON_ARRAY: $json = json_decode($this->response["body"], true); return $json === false ? Array() : $json; break;
				case HTTP::JSON_OBJECT: $json = json_decode($this->response["body"], false); return new Object($json === false ? Array() : $json); break;
				case HTTP::QUERYSTRING_ARRAY: parse_str($this->response["body"], $query); return $query === false ? Array() : $query; break;
				case HTTP::QUERYSTRING_OBJECT: parse_str($this->response["body"], $query); return new Object($query === false ? Array() : $query); break;
				case HTTP::XML_ARRAY: $xml = simplexml_load_string($this->response["body"]); return (array)$xml; break;
				case HTTP::XML_OBJECT: $xml = simplexml_load_string($this->response["body"]); return new Object((array)$xml); break;
				case HTTP::XML_SIMPLEXML: $xml = simplexml_load_string($this->response["body"]); return $xml; break;
			}
		}
		
		// function responseHeaders(): Returns the response headers as an array
		public function responseHeaders() {
			return $this->response["headers"];
		}
		
		// function getHeader(): Returns a response
		public function getHeader($header) {
			$headers = $this->response["headers"];
			if(!is_array($headers)) return null;
			
			foreach($headers as $key => $value)
				if(strtolower($header) == strtolower($key))
					return $value;
			
			return null;
		}
		
		// function responseObject(): Returns the response as an object
		public function responseObject() {
			return $this->response(HTTP::JSON_OBJECT);
		}
		
		// function responseArray(): Returns the response as an object
		public function responseArray() {
			return $this->response(HTTP::JSON_ARRAY);
		}
		
		// function responseQueryString(): Returns the response as an object
		public function responseQueryString() {
			return $this->response(HTTP::QUERYSTRING_OBJECT);
		}
		
		// function responseXMLObject(): Returns the response as an object
		public function responseXMLObject() {
			return $this->response(HTTP::XML_OBJECT);
		}
		
		// function close(): Closes the curl handle
		public function close() {
			if($this->curl !== null) {
				curl_close($this->curl);
				$this->curl = null;
			}
		}
		
		// function __destruct(): Shortcut for HTTP::close()
		public function __destruct() {
			$this->close();
		}
		
		// function __sleep(): Shortcut for HTTP::close()
		public function __sleep() {
			$this->close();
		}
	}
	