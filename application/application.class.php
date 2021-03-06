<?php
	/* Asteroid
	 * class Application
	 */
	namespace Asteroid;
	use PDOException;
	use ReflectionClass;
	use JsonSerializable;
	use stdClass;
	class Application implements JsonSerializable {
		protected $configuration = null;
		protected $authentication = null;
		protected $controller = null;
		protected $view = null;
		protected $request = null;
		protected $response = null;
		protected $events = null;
		protected $session = null;
		protected $filesystem = Array();
		protected $status = null;
		
		protected $definitions = Array();
		
		protected $databases = null;
		protected $models = Array();
		protected $libraries = Array();
		protected $messages = Array();
		
		protected $url_controller = null;
		protected $url_action = null;
		protected $url_action_info = null;
		
		private $version = "2.0.0";
		private $version_beta = 3;
		
		// function __construct(): Creates a new application object
		public function __construct() {
			
		}
		
		// function run(): Runs the application, creates a new instance of the current controller
		public function run() {
			// Load configuration libraries
			$libraries = $this->configuration([ "libraries" ]);
			if(is_array($libraries)) foreach($libraries as $library)
				$this->load($library);
			
			// Set the X-Asteroid-Version header
			if(in_array("Asteroid\Libraries\Asteroid", array_keys($this->libraries)))
				$this->response()->header("X-Asteroid-Version", "v" . $this->getVersion());
			
			// Trigger the "ready" event
			if(!$this->events()->trigger("ready"))
				throw new Exception(__METHOD__, "Failed to run the application: canceled by event handler.");
			
			// Reserve /static for static files and /error for errors, return a 404 error if the controller is either of these
			if(!in_array($this->getControllerURL(), Array("static", "error")))
				$r = $this->controller()->loadFromURL($this->getControllerURL(), $this->getAction(), $this->getActionInfo());
			else $r = "_404";
			
			if($r == "_404")
				$this->controller()->loadFromClass($this->configuration([ "controllers", "error" ]), "_404", array_merge(Array($this->getControllerURL(), $this->getAction()), $this->getActionInfo()));
			elseif(($r === false) || ($r == "_500"))
				throw new FatalException(__METHOD__, "Internal Server Error.");
		}
		
		// function configuration(): Returns the configuration object
		public function configuration() {
			if(!is_object($this->configuration) || (!$this->configuration instanceof Configuration)) $this->configuration = new Configuration($this);
			if(func_num_args() > 1) $this->configuration->set(func_get_arg(0), func_get_arg(1));
			elseif(func_num_args() == 1) return $this->configuration->get(func_get_arg(0));
			else return $this->configuration;
		}
		
		// function authentication(): Gets the authentication object
		public function authentication() {
			if(!is_object($this->authentication) || (!$this->authentication instanceof AuthenticationInterface)) {
				$class = $this->configuration([ "auth", "handler" ]);
				if(is_string($class)) $this->authentication = new $class($this);
				else $this->authentication = new NoAuth($this);
			} if(func_num_args() > 0) return call_user_func_array(Array($this->authentication, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->authentication;
		}
		
		// function controller(): Gets the controller object
		public function controller() {
			if(!is_object($this->controller) || (!$this->controller instanceof Controller)) $this->controller = new Controller($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->controller, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->controller;
		}
		
		// function view(): Gets the view object
		public function view() {
			if(!is_object($this->view) || (!$this->view instanceof View)) $this->view = new View($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->view, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->view;
		}
		
		// function request(): Gets the request object
		public function request() {
			if(!is_object($this->request) || (!$this->request instanceof Request)) $this->request = new Request($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->request, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->request;
		}
		
		// function response(): Gets the response object
		public function response() {
			if(!is_object($this->response) || (!$this->response instanceof Response)) $this->response = new Response($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->response, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->response;
		}
		
		// function events(): Gets the events object
		public function events() {
			if(!is_object($this->events) || (!$this->events instanceof Events)) $this->events = new Events($this);
			if(func_num_args() > 0) return call_user_func_array(Array($this->events, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->events;
		}
		
		// function session(): Gets the session object
		public function session() {
			if(!is_object($this->session) || (!$this->session instanceof Session)) $this->session = Session::cookie($this, $this->configuration([ "session", "cookie_name" ]));
			if(func_num_args() > 0) return call_user_func_array(Array($this->session, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->session;
		}
		
		// function filesystem(): Gets the filesystem object
		public function filesystem($directory = null) {
			return new Filesystem($this, $directory);
		}
		
		// function status(): Creates a status object
		public function status($type = "PlainStatus") {
			if(!is_string($type))
				throw new Exception(__METHOD__, "\$type must be a string.");
			
			$class = $this->configuration([ "status", $type ]);
			if(!class_exists($class) && !class_exists($class = "Asteroid\\Status\\" . $type))
				throw new Exception(__METHOD__, "Failed to load status {$type}.");
			
			return new $class($this);
		}
		
		public function successs() {
			return $this->status("Success");
		}
		
		public function notfounds() {
			return $this->status("NotFound");
		}
		
		// function __get(): Gets a defined variable
		public function __get($name) {
			if($name == "configuration") return $this->configuration();
			elseif($name == "authentication") return $this->authentication();
			elseif($name == "controller") return $this->controller();
			elseif($name == "view") return $this->view();
			elseif($name == "request") return $this->request();
			elseif($name == "response") return $this->response();
			elseif($name == "events") return $this->events();
			elseif($name == "session") return $this->session();
			elseif($name == "filesystem") return $this->filesystem();
			elseif($name == "status") return $this->status();
			elseif($name == "controllerurl") return $this->getControllerURL();
			elseif($name == "action") return $this->getAction();
			elseif($name == "actioninfo") return $this->getActionInfo();
			elseif(!$this->defined($name) && isset($this->{$name}))
				return $this->{$name};
			elseif(!$this->defined($name))
				throw new Exception(__METHOD__, "\$name is not defined.");
			elseif(is_callable($this->definition($name)->value_function))
				return call_user_func($this->definition($name)->value_function);
			elseif(isset($this->definition($name)->value))
				return $this->definition($name)->value;
			else return null;
		}
		
		// function __call(): Calls a defined function
		public function __call($name, $parameters) {
			if(!$this->defined($name))
				throw new Exception(__METHOD__, "\$name [{$name}] is not defined.");
			elseif(isset($this->definition($name)->function) && is_callable($this->definition($name)->function))
				return call_user_func_array($this->definition($name)->function, $parameters);
			elseif(isset($this->definition($name)->value) && is_object($this->definition($name)->value) && (count($parameters) > 0))
				return call_user_func_array(Array($this->definition($name)->value, $parameters[0]), array_slice($parameters, 1));
			elseif(isset($this->definition($name)->value) && is_object($this->definition($name)->value))
				return $this->definition($name)->value;
			else return null;
		}
		
		// function define(): Defines a function / variable / both
		public function define($name, $variable, $function = null, $variable_function = null) {
			if(!is_string($name))
				throw new Exception(__METHOD__, "\$name must be a string.");
			if($this->defined($name))
				throw new Exception(__METHOD__, "\$name is already defined.");
			
			if(($function === null) && !is_object($variable) && is_callable($variable)) {
				$function = $variable;
				$variable = null;
			}
			
			if(($function !== null) && !is_callable($function))
				throw new Exception(__METHOD__, "\$function must be callable or null.");
			if(($variable === null) && ($function === null) && ($variable_function === null))
				throw new Exception(__METHOD__, "Either \$variable, \$function or \$variable_function must be defined.");
			if(($variable_function !== null) && !is_callable($variable_function))
				throw new Exception(__METHOD__, "\$variable_function must be callable or null.");
			if(($variable !== null) && is_callable($variable_function))
				throw new Exception(__METHOD__, "\$variable and \$variable_function cannot both be defined.");
			
			$definition = $this->definitions[$name] = new stdClass();
			$definition->name = $name;
			$definition->value = $variable;
			$definition->value_function = $variable_function;
			$definition->function = $function;
		}
		
		// function definition(): Gets the definition of a function / variable
		protected function definition($name) {
			if(isset($this->definitions[$name]))
				return $this->definitions[$name];
			else return null;
		}
		
		// function defined(): Checks if a function / variable has been defined
		public function defined($name) {
			if(is_object($this->definition($name)))
				return true;
			else return false;
		}
		
		// function __isset(): Checks if a function / variable has been defined
		public function __isset($name) {
			if(in_array($name, Array("configuration", "authentication", "controller", "view", "request", "response", "events", "session", "filesystem", "status", "controllerurl", "action", "actioninfo")))
				return true;
			else return $this->defined($name);
		}
		
		// function subcontroller(): Lets an action use another controller
		public function subcontroller($controller, $configuration = null, $action_info_include = 0, $variables = Array()) {
			if(!is_string($controller))
				throw new Exception(__METHOD__, "\$controller must be a string containing an existing class.");
			
			if(is_array($configuration))
				$configuration = (object)$configuration;
			if(!is_object($configuration))
				$configuration = $this->configuration->controller();
			
			$oldcontroller = $this->getControllerURL();
			$newcontroller = $this->getControllerURL() . "/" . $this->getAction();
			if(count($this->getActionInfo()) >= 1)
				$newaction = $this->getActionInfo(1);
			else $newaction = "index";
			$newactioninfo = array_slice($this->getActionInfo(), 1);
			
			for($i = 0; $i < $action_info_include; $i++) {
				$newcontroller = $newcontroller . "/" . $newaction;
				if(count($newactioninfo) >= 1)
					$newaction = $newactioninfo[0];
				else $newaction = "index";
				$newactioninfo = array_slice($newactioninfo, 1);
			}
			
			$this->url_controller = $newcontroller;
			$this->url_action = $newaction;
			$this->url_action_info = $newactioninfo;
			
			$this->configuration->controller($newcontroller, $controller, $configuration);
			
			return $this->controller()->loadFromURL($newcontroller, $newaction, $newactioninfo, $variables);
		}
		
		// function database(): Connects to and returns a database
		public function database($name = "default") {
			if(!is_object($this->databases)) $this->databases = new Object();
			if(isset($this->databases->{$name})) return $this->databases->{$name};
			elseif($this->configuration()->check([ "databases", $name ])) {
				// Connect to a database
				try {
					$hostname = $this->configuration()->get([ "databases", $name, "hostname" ]);
					$username = $this->configuration()->get([ "databases", $name, "username" ]);
					$password = $this->configuration()->get([ "databases", $name, "password" ]);
					$database = $this->configuration()->get([ "databases", $name, "database" ]);
					return $this->databases->{$name} = new Database($hostname, $username, $password, $database);
				} catch(PDOException $error) {
					$this->error("**Database error:** " . $error->getMessage());
					return false;
				}
			}
			
			// ... nothing happened
			// Use the default database
			if($name != "default") return $this->database("default");
			else throw new Exception(__METHOD__, "Default database does not exist.");
		}
		
		// function model(): Gets/sets data from a model (or http request / email)
		public function model($model) {
			if(!is_string($model))
				throw new Exception(__METHOD__, "\$model must be a string.");
			
			if(in_array(strtolower($model), Array("http", "mail")))
				return call_user_func_array(Array($this, strtolower($model)), array_slice(func_get_args(), 1));
			
			if(isset($this->models[$model]))
				return $this->models[$model];
			
			$class = $this->configuration([ "models", $model ]);
			if(!class_exists($class) && !class_exists($class = "Asteroid\\Models\\" . $model))
				throw new Exception(__METHOD__, "Failed to load model {$model}.");
			
			$reflection = new ReflectionClass($class);
			if(method_exists($class, "__construct"))
				$object = $reflection->newInstanceArgs(array_merge(Array($this), array_slice(func_get_args(), 1)));
			else $object = $reflection->newInstance();
			
			// Give this model the application
			$object->application = $this;
			
			if(isset($object->one_instance) && ($object->one_instance === true))
				$this->models[$model] = $object;
			
			return $object;
		}
		
		// function load(): Loads a library
		public function load($object) {
			if(!is_object($object) && (!is_string($object) || !class_exists($object)))
				throw new Exception(__METHOD__, "\$object must be a string containing the class name of an existing library or an object.");
			
			$class = is_string($object) ? $object : get_class($object);
			if(isset($this->libraries[$class])) return true; // Library is already loaded
			if(is_string($object)) $object = new $object($this);
			if(method_exists($object, "init")) $object->init($this);
			
			// Register the ready event
			if(method_exists($object, "ready"))
				$this->events()->bind("ready", Array($object, "ready"));
			
			$this->libraries[$class] = $object;
			return true;
		}
		
		// function http(): Creates a http request object
		public function http($method, $url, $params = Array(), $headers = Array()) {
			return new HTTP($this, $method, $url, $params, $headers);
		}
		
		// function mail(): Creates an email object
		public function mail() {
			return new Mail($this);
		}
		
		// function redirect(): Redirects to a url
		public function redirect($url) {
			if($this->errors())
				$this->message("Redirecting to **" . $url . "**");
			
			if(strtolower($this->request()->header("X-Requested-With")) == "xmlhttprequest") {
				if(filter_var($url, FILTER_VALIDATE_URL) && (strpos($this->getBaseURL(), $url) !== 0))
					$this->events()->bind("render_body", function() use($url) {
						return "<script data-ajaxify=\"\">$(document).ready(function() { location.replace(\"" . htmlentities($url) . "\"); });</script>";
					});
				else $this->events()->bind("render_body", function() use($url) {
					return "<script data-ajaxify=\"\">$(document).ready(function() { if(typeof History != \"undefined\") History.pushState(null, null, \"" . htmlentities($url) . "\"); else location.replace(\"" . htmlentities($url) . "\"); });</script>";
				});
				
				$this->view()->render("blank");
			} else $this->response()->code(303)->header("Location", $url);
			exit();
		}
		
		// function redirectWithMessages(): Uses javascript to redirect to another page on this site with messages - when javascript is disabled this will simply fail and messages will be shown on the same page - this requires History.js
		public function redirectWithMessages() {
			$url = call_user_func_array(Array($this, "generateURL"), func_get_args());
			$relativeurl = call_user_func_array(Array($this, "generateRelativeURL"), func_get_args());
			
			if($this->errors())
				$this->message("Redirecting to **" . $relativeurl . "**");
			
			$this->events()->bind("render_body", function() use($url) {
				return "<script data-ajaxify=\"\">$(document).ready(function() { var messages = []; $(\".messages > *\").each(function() { messages.push({ class: $(this).attr(\"class\"), html: $(this).html() }); }); History.replaceState({ messages: messages }, null, \"" . htmlentities($url) . "\"); });</script>";
			});
			
			$this->view()->render("blank");
		}
		
		// function getHostname(): Gets the hostname from the configuration
		public function getHostname() {
			return preg_replace("/^(www\.)*/i", "", $this->configuration([ "hostname" ]));
		}
		
		// function getBaseURL(): Gets the base url from configuration
		public function getBaseURL() {
			$baseurl = $this->configuration([ "https" ]) !== true ? "http://" : "https://";
			if($this->configuration([ "hostnamewww" ]) === true) $baseurl .= "www.";
			$baseurl .= $this->getHostname();
			$baseurl .= "/" . trim($this->configuration([ "path" ]), "/");
			return rtrim($baseurl, "/");
		}
		
		// function getURL(): Gets the current url
		public function getURL() {
			// Get the url from ?url, ?action, /path-info or /request-uri
			if(is_string($parameter = $this->configuration([ "parameter" ])) && isset($_GET[$parameter]) && is_string($_GET[$parameter])) return $_GET[$parameter];
			elseif(isset($_GET["action"]) && is_string($_GET["action"])) return $_GET["action"];
			elseif(isset($_SERVER["PATH_INFO"]) && (strlen($_SERVER["PATH_INFO"]) > 1)) return explode("?", substr($_SERVER["PATH_INFO"], 1))[0];
			else return explode("?", substr($_SERVER["REQUEST_URI"], 1))[0];
		}
		
		// function parseURL(): Parses a url and sets the controller, action and action info from it
		public function parseURL($url = null, $return = null) {
			if(!is_string($url))
				$url = $this->getURL();
			
			if(($return === null) && is_string($this->url_controller))
				$return = true;
			
			$url = explode("/", str_replace("//", "/", $url));
			foreach($url as $key => $value)
				$url[$key] = urldecode($value);
			
			if(!isset($url[0]) || (trim($url[0]) == ""))
				$url[0] = "index";
			if(!isset($url[1]) || (trim($url[1]) == ""))
				$url[1] = "index";
			
			if($return === true)
				return $url;
			
			$this->url_controller = $url[0];
			$this->url_action = $url[1];
			$this->url_action_info = array_slice($url, 2);
		}
		
		// function generateURL(): Generates a url from the controller, action and action info provided
		public function generateURL($params, $query = null) {
			if(!is_array($params)) $params = func_get_args();
			$url = call_user_func_array(Array($this, "generateRelativeURL"), $params);
			$query_string = is_array($query) ? http_build_query($query) : null;
			if($query_string === null) {
				$query_string = "";
				foreach($params as $key => $param) {
					if(is_array($param))
						$query_string = http_build_query($param);
				}
			}
			
			if(!is_string($parameter = $this->configuration([ "parameter" ])))
				return $this->getBaseURL() . "/" . $url . (trim($query_string) != "" ? "?" . $query_string : "");
			else return $this->getBaseURL() . "/?" . urlencode($parameter) . "=" . urlencode($url) . (trim($query_string) != "" ? "&" . $query_string : "");
		}
		
		// function generateRelativeURL(): Generates a url relative to the base url from the controller, action and action info provided
		public function generateRelativeURL() {
			$params = func_get_args();
			foreach($params as $key => $value) {
				if((!is_string($value) && !is_numeric($value)) || (trim($value) == "")) unset($params[$key]);
				else $params[$key] = $key == 0 ? str_replace("%2F", "/", urlencode($value)) : urlencode($value);
			} $params = array_values($params);
			if(count($params) <= 2) return strtolower($url = preg_replace("/(\/index)*$/i", "", implode($params, "/"))) == "index" ? "" : $url;
			else return implode($params, "/");
		}
		
		// function message(): Adds a message to messages
		public function message($message, $type = "neutral") {
			if(is_int($message) || is_float($message))
				$message = (string)$message;
			
			if(is_string($message) && (strlen($message) > 0))
				$this->messages[] = Array("type" => $type, "message" => $message);
			return $this->status("Message")->type($type);
		}
		
		// function success(): Adds a success message
		public function success($message) {
			return $this->message($message, "success");
		}
		
		// function error(): Adds an error message
		public function error($message, $error = false) {
			if(($error !== true) || $this->errors())
				return $this->message($message, "error");
			else return $this->status("Message")->type("error");
		}
		
		// function getMessages(): Gets all messages
		public function getMessages() {
			return $this->messages;
		}
		
		// function getController(): Returns the "real" current controller
		public function getController($error = false) {
			$controller = $this->getControllerURL();
			return $this->controller()->getClass($controller, $error);
		}
		
		// function getControllerURL(): Returns the current controller
		public function getControllerURL($up = 0) {
			if(is_string($this->url_controller) && is_int($up) && ($up >= 1))
				return preg_replace("/(\/.*){0,{$up}}$/", "", $this->url_controller);
			elseif(is_string($this->url_controller)) return $this->url_controller;
			else return "index";
		}
		
		// function getAction(): Returns the current action
		public function getAction() {
			if(is_string($this->url_action)) return $this->url_action;
			else return "index";
		}
		
		// function getActionInfo(): Returns all / one action info
		public function getActionInfo($key = null) {
			if(!is_int($key)) return $this->url_action_info;
			elseif(isset($this->url_action_info[$key - 1]) && !empty($value = $this->url_action_info[$key - 1])) return $value;
			else return null;
		}
		
		// function errors(): Checks if errors should be shown
		public function errors() {
			if($this->configuration([ "errors" ]) === true) return true;
			else return false;
		}
		
		// function getCDNURL(): Returns the url of a file on cdn.asteroid.ml
		public function getCDNURL($file = "") {
			foreach($this->events()->triggerR("get_cdn_url", Array($file)) as $return) {
				if(is_string($return))
					return $return;
				elseif($return === false)
					return false;
			}
			
			return rtrim($this->configuration([ "cdn", "url" ]), "/") . "/" . trim($file, "/");
		}
		
		// function getVersion(): Returns the current version number
		public function getVersion() {
			list($major, $minor, $patch) = explode(".", $this->version . "..");
			$v_s = preg_replace("/(\.0)*$/i", "", (int)$major . "." . (int)$minor . "." . (int)$patch);
			if(count(explode(".", $v_s)) <= 2) $v_s .= ".0";
			if(isset($this->version_beta) && is_int($this->version_beta)) $v_s .= "-beta" . $this->version_beta;
			return $v_s;
		}
		
		// function checkVersion(): Compares the version number, usually to see if a package is compatible
		public function checkVersion($version, $operator = ">") {
			if(version_compare($this->version, $version, $operator)) return true;
			else return false;
		}
		
		// function getReport(): Return information about the application
		public function getReport($sensitive = true) {
			$report = new stdClass();
			$report->version = $this->getVersion();
			$report->controllerurl = $this->getControllerURL();
			$report->action = $this->getAction();
			$report->actioninfo = $this->getActionInfo();
			
			$report->libraries = new stdClass();
			foreach($this->libraries as $class => $library)
				if(method_exists($library, "getReport"))
					$report->libraries->{$class} = $library->getReport($sensitive);
			
			foreach($this->events()->triggerR("get_report", Array($report, $sensitive)) as $report)
				if(is_object($report) || is_array($report))
					foreach($report as $k => $v)
						if(!isset($report->{$k}))
							$report->{$k} = $v;
			
			return $report;
		}
		
		// function __debugInfo(): Returns information for the print_r and var_dump functions, so the output isn't really long and to prevent leaking configuration information - only works in PHP 5.6 or later
		public function __debugInfo() {
			return (array)$this->getReport(false);
		}
		
		public function jsonSerialize() {
			return (array)$this->getReport(false);
		}
	}
	