<?php
	/* Asteroid
	 * class Account
	 * 
	 * Account controller, shows the logged-in user's account.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class Account extends BaseController {
		public function __construct() {
			if(!$this->application->authentication()->loggedin($user))
				throw $this->application->error("You are not logged in.")->view()->render("blank");
		}
		
		public function index() {
			return $this->application->view()->render("account", Array("user" => $user));
		}
	}
	