<?php
	/* Asteroid
	 * class Controller
	 * 
	 * Loads a controller.
	 */
	namespace Asteroid;
	use ReflectionMethod;
	class Controller {
		protected $application = null;
		
		// function __construct(): Creates a new Controller object
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function load(): Loads a controller from an object
		public function load($controller, $action = "index", $actioninfo = Array(), $variables = Array()) {
			if(!is_object($controller) || !($controller instanceof BaseController))
				throw new Exception(__METHOD__, "\$controller must extend from Asteroid\\BaseController.");
			
			if(!is_string($action)) throw new Exception(__METHOD__, "\$action must be a string.");
			if(!is_array($actioninfo)) throw new Exception(__METHOD__, "\$actioninfo must be an array.");
			
			// Give this controller access to the application and current configuration
			$controller->application = $this->application;
			$controller->configuration = $this->application->configuration()->controller();
			
			// Does the action need to be swapped with the first action info?
			if(isset($actioninfo[0]) && in_array($actioninfo[0], $controller->action_swap)) {
				$real_action = $actioninfo[0];
				$actioninfo[0] = $action;
				$action = $real_action;
			}
			
			// Rewrite actions
			if(isset($controller->rewrite_actions[$action]) && is_string($controller->rewrite_actions[$action]))
				$action = $controller->rewrite_actions[$action];
			
			// Check if this action should be handled by a subcontroller
			if(isset($controller->subcontrollers[$action]) && is_string($controller->subcontrollers[$action]))
				return $this->application->subcontroller($controller->subcontrollers[$action], null, in_array($action, $controller->action_swap), $this->getSubcontrollerVariables($controller));
			
			// If $action does not exist, check if a default action exists instead
			if(!method_exists($controller, $action) && is_string($controller->default_action))
				$action = $controller->default_action;
			
			// Replace - with _
			$action = str_replace("-", "_", $action);
			
			// Check if $action exists or is a magic method
			if(!method_exists($controller, $action) || (substr($action, 0, 2) == "__"))
				return $this->loadFromClass($this->application->configuration([ "controllers", "error" ]), "_404", array_merge(Array($controller, $action), $actioninfo));
			
			// Give this controller it's variables
			foreach($variables as $key => $value)
				if(!isset($controller->{$key}))
					$controller->{$key} = $value;
			
			// Make sure the controller gets something for each parameter
			$reflection = new ReflectionMethod($controller, $action);
			while(count($actioninfo) < $reflection->getNumberOfRequiredParameters())
				$actioninfo[] = null;
			
			// Call $action
			return call_user_func_array(Array($controller, $action), $actioninfo);
		}
		
		// function loadFromClass(): Loads a controller from it's classname
		public function loadFromClass($class, $action = "index", $actioninfo = Array(), $variables = Array()) {
			if(!is_string($class) || !class_exists($class))
				throw new Exception(__METHOD__, "\$class must be a string containing an existing class.");
			
			return $this->load(new $class($this->application, $this->application->configuration()->controller()), $action, $actioninfo, $variables);
		}
		
		// function loadFromURL(): Loads a controller from it's url
		public function loadFromURL($url, $action = "index", $actioninfo = Array(), $variables = Array()) {
			if(!is_string($url))
				throw new Exception(__METHOD__, "\$url must be a string.");
			
			$class = $this->getClass($url);
			if(is_object($class))
				return $this->loadFromObject($class, $action, $actioninfo);
			elseif(!is_string($class)) {
				$class = $this->application->configuration([ "controllers", "error" ]);
				$action = "_404";
				$actioninfo = array_merge(Array($class, $action), $actioninfo);
			}
			
			return $this->loadFromClass($class, $action, $actioninfo, $variables);
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
		
		// function getSubcontrollerVariables(): Gets the variables to be passed to a subcontroller
		protected function getSubcontrollerVariables($controller) {
			$variables = Array();
			
			// Give the subcontroller the parent controller
			$variables["parent"] = $controller;
			
			if(!is_array($controller->subcontroller_variables))
				return $variables;
			
			foreach($controller->subcontroller_variables as $key => $value)
				if(is_string($key))
					$variables[$key] = $value;
				elseif(is_string($value)) {
					$key = $value;
					$value = $controller->{$key};
					$variables[$key] = $value;
				}
			
			return $variables;
		}
	}
	