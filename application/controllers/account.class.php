<?php
	/* Asteroid
	 * class Account
	 * 
	 * Account controller, shows the logged-in user's account.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class Account extends BaseController {
		public function index() {
			if(!$this->application->authentication()->loggedin($user))
				$this->application->error("You are not logged in.")->view()->render("blank");
			
			$this->application->view()->render("account", Array("user" => $user));
		}
	}
	