<?php
	/* Asteroid
	 * class NoAuth
	 * 
	 * The noauth controller.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class NoAuth extends BaseController {
		public function index() {
			return $this->application->error("This website does not support authentication.")->view()->render("blank");
		}
		
		public function login() {
			return $this->application->error("This website does not support authentication.")->view()->render("blank");
		}
		
		public function logout() {
			return $this->application->error("This website does not support authentication.")->view()->render("blank");
		}
	}
	