<?php
	/* Asteroid
	 * class Controller
	 * 
	 * Loads a controller.
	 */
	namespace Asteroid;
	class Controller {
		protected $application = null;
		
		// function __construct(): Creates a new Controller object
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function load(): Loads a controller from an object
		public function load($controller, $action = "index", $actioninfo = Array()) {
			if(!is_object($controller) || !($controller instanceof BaseController))
				throw new Exception(__METHOD__, "\$controller must extend from Asteroid\\BaseController.");
			
			if(!is_string($action)) throw new Exception(__METHOD__, "\$action must be a string.");
			if(!is_array($actioninfo)) throw new Exception(__METHOD__, "\$actioninfo must be an array.");
			
			// Give this controller access to the application
			$controller->application = $this->application;
			
			// Rewrite actions
			if(isset($controller->rewrite_actions[$action]) && is_string($controller->rewrite_actions[$action]))
				$action = $controller->rewrite_actions[$action];
			
			// Check if $action exists
			if(!method_exists($controller, $action) && is_string($controller->default_action))
				$action = $controller->default_action;
			if(!method_exists($controller, $action))
				return $this->loadFromClass($this->application->configuration([ "controllers", "error" ]), "_404", array_merge(Array($controller, $action), $actioninfo));
			
			// Call $action
			return call_user_func_array(Array($controller, $action), $actioninfo);
		}
		
		// function loadFromClass(): Loads a controller from it's classname
		public function loadFromClass($class, $action = "index", $actioninfo = Array()) {
			if(!is_string($class) || !class_exists($class))
				throw new Exception(__METHOD__, "\$class must be a string containing an existing class.");
			
			return $this->load(new $class($this->application), $action, $actioninfo);
		}
		
		// function loadFromURL(): Loads a controller from it's url
		public function loadFromURL($url, $action = "index", $actioninfo = Array()) {
			if(!is_string($url))
				throw new Exception(__METHOD__, "\$url must be a string.");
			
			$class = $this->getClass($url);
			if(!is_string($class)) {
				$class = $this->application->configuration([ "controllers", "error" ]);
				$action = "_404";
				$actioninfo = array_merge(Array($class, $action), $actioninfo);
			}
			
			return $this->loadFromClass($class, $action, $actioninfo);
		}
		
		// function getClass(): Gets the classname of a controller from the configuration
		public function getClass($url, $error = false) {
			$class = $this->application->configuration([ "controllers", $url ]);
			if(is_string($class) || ($error !== true)) return $class;
			else return $this->application->configuration([ "controllers", "error" ]);
		}
		
		// function configuration(): Gets the configuration for a controller/controller url
		public function configuration($controller = null, $controllerurl = null) {
			return $this->application->configuration()->controller($controller, $controllerurl);
		}
	}
	