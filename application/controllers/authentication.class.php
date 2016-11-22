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
			return $this->application->redirect($this->application->generateURL("account"));
		}
		
		public function login() {
			// Save next url
			if(is_string($next = $this->application->request()->query("next")))
				$this->application->session()->set([ "auth", "next" ], $next);
			else $this->application->session()->set([ "auth", "next" ], null);
			
			// Redirect to the login dialog
			$oauth = $this->application->authentication()->oauth();
			$oauth->loginRedirect($this->application->generateURL($this->application->getControllerURL(), "code"), $this->application->configuration([ "oauth", "client_scope" ]));
			
			return $this->application->status("Success");
		}
		
		public function code() {
			$oauth = $this->application->authentication()->oauth();
			if(is_string($code = $this->application->request()->query("code")) && is_string($state = $this->application->request()->query("state"))) {
				try {
					$oauth->getAccessTokenFromCode($this->application->generateURL($this->application->getControllerURL(), "code"), $code, $state);
					$user = $oauth->userProfile();
					if(!is_string($oauth->accessToken()) || !isset($user->id))
						return $this->application->error("Unknown error.")->view()->render("blank");
					
					$this->application->success("Logged in as {$user->name}.");
				} catch(\Exception $error) {
					//
					$this->application->error($error->getMessage());
				}
			} else {
				switch($this->application->request()->query("error")) {
					default: $error = "Unknown error."; break;
					case "access_denied": $error = "You canceled logging in."; break;
				}
				
				$this->application->error($error);
			}
			
			// Redirect to next url or home
			if(is_string($next = $this->application->session()->get([ "auth", "next" ]))) {
				if($this->application->errors())
					$this->application->message("Redirecting to **" . $next . "**");
				
				$this->application->events()->bind("render_body", function() use($next) {
					return "<script data-ajaxify=\"\">$(document).ready(function() { var messages = []; $(\".messages > *\").each(function() { messages.push({ class: $(this).attr(\"class\"), html: $(this).html() }); }); History.replaceState({ messages: messages }, null, \"" . htmlentities($next) . "\"); });</script>";
				});
				
				return $this->application->view()->render("blank");
			} else return $this->application->redirectWithMessages("index");
		}
		
		public function logout() {
			// Delete the access token
			if($this->application->authentication()->loggedin()) {
				$this->application->authentication()->oauth()->accessToken(null);
				return $this->application->success("You have been logged out.")->view()->render("blank");
			} else
				// The user was never logged in!
				return $this->application->error("You are not logged in.")->view()->render("blank");
		}
	}
	