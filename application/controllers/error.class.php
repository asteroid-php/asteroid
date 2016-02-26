<?php
	/* Asteroid
	 * class Error
	 * 
	 * This is the error controller. It must have at least a _404 method that will be shown when a controller/method cannot be found or when an action returns "_404".
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class Error extends BaseController {
		public function _404() {
			$this->application->error("The page you requested was not found.")->view()->render("blank");
		}
	}
	