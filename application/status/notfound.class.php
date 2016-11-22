<?php
	/* Asteroid
	 * class NotFound
	 * 
	 * Returned / thrown when a controller / action / ... is not found.
	 */
	namespace Asteroid\Status;
	use Asteroid\BaseStatus;
	class NotFound extends BaseStatus {
		protected $code = "404";
		protected $message = "The page you requested was not found.";
		
		public function continuestatus() {
			$error_controller = $this->application->controller()->getClass("error", false);
			
			if(!is_string($error_controller) || !class_exists($error_controller))
				throw new FatalException(__METHOD__, "Error controller does not exist.");
			elseif(!method_exists($error_controller, "_404"))
				throw new FatalException(__METHOD__, "Error controller does not have a _404 method.");
			
			return $this->application->controller()->loadFromClass($error_controller, "_404", array_merge(Array($this->application->getControllerURL(), $this->application->getAction()), $this->application->getActionInfo()));
		}
	}
	