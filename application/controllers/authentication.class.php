<?php
	/* Asteroid
	 * class Authentication
	 * 
	 * The authentication controller.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class Authentication extends BaseController {
		public function index() {
			$this->application->redirect($this->application->generateURL("account"));
		}
		
		public function login() {
			// Redirect to the login dialog
			$oauth = $this->application->authentication()->oauth();
			$oauth->loginRedirect($this->application->generateURL($this->application->getControllerURL(), "code"), Array("user", "user:email"));
		}
		
		public function code() {
			$oauth = $this->application->authentication()->oauth();
			if(is_string($code = $this->application->request()->query("code")) && is_string($state = $this->application->request()->query("state"))) {
				try {
					$oauth->getAccessTokenFromCode($this->application->generateURL($this->application->getControllerURL(), "code"), $code, false);
					$user = $oauth->userProfile();
					if(!is_string($oauth->accessToken()) || !isset($user->id))
						$this->application->error("Unknown error.")->view()->render("blank");
					
					$this->application->success("Logged in as {$user->name}.")->view()->render("blank");
				} catch(\Exception $error) {
					//
					$this->application->error($error->getMessage())->view()->render("blank");
				}
			} elseif(is_string($error = $this->application->request()->query("error")) || true) {
				switch($error) {
					default: $error = "Unknown error."; break;
					case "access_denied": $error = "You canceled logging in."; break;
				}
				
				$this->application->error($error)->view()->render("blank");
			}
		}
		
		public function logout() {
			// Delete the access token
			if($this->application->authentication()->loggedin()) {
				$this->application->authentication()->oauth()->session("token", null);
				$this->application->success("You have been logged out.")->view()->render("blank");
			} else
				// The user was never logged in!
				$this->application->error("You are not logged in.")->view()->render("blank");
		}
	}
	