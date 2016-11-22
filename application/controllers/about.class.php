<?php
	/* Asteroid
	 * class About
	 * 
	 * This is the about controller.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class About extends BaseController {
		public function index() {
			if(is_string($about = $this->configuration->get([ "about_file" ])) && $this->application->filesystem()->file($about))
				$about = $this->application->filesystem()->read($about);
			else $about = $this->configuration->get([ "about_text" ]);
			
			$class = $this->configuration->get([ "parsedown_class" ]);
			$parsedown = new $class();
			$parsedown->setBreaksEnabled(true);
			
			return $this->application->view()->title("About")->render("about/about", Array("about" => $about));
		}
		
		public function contact($save = false) {
			if($save == "save") {
				$model = $this->application->model("About");
				$data = $model->getAndValidateContactData($this->application->request()->post("name"), $this->application->request()->post("contact"), $this->application->request()->post("message"));
				if($data->get([ "success" ]) !== true)
					return $this->application->error($data->get([ "error" ]))->redirectWithMessages($this->application->getControllerURL(), "contact");
				
				list($name, $contact, $message) = $data->geta([ "name" ], [ "contact" ], [ "message" ]);
				
				$database = $this->application->database($this->configuration->get([ "contact_database" ]));
				$table = $this->configuration->get([ "contact_table" ]);
				$user_id = $this->application->authentication()->loggedin($user) === true ? $user->getID() : null;
				$data = $model->saveContactData($database, $table, $user_id, $name, $contact, $message);
				if($data->get([ "success" ]) !== true)
					return $this->application->error($this->application->errors() ? implode(", ", (array)$data->get([ "error" ])) : null)->error("Unknown error saving form response.")->redirectWithMessages($this->application->getControllerURL());
				
				$id = $data->get([ "id" ]);
				
				// Success!
				// Send an email to contact_addresses
				$contact_addresses = $this->configuration->get([ "contact_addresses" ]);
				$contact_url = $this->application->generateURL($this->application->getControllerURL());
				$controlpanel_url = $this->application->generateURL("control-panel");
				$mail = $model->sendContactNotification($contact_addresses, $contact_url, $controlpanel_url, $name, $contact, $message, $id);
				
				// Return success, even if the email couldn't send
				return $this->application->success("**Success!** Form response saved")->message($this->application->errors() ? ($mail === true ? "Sent notification email" : "**Error sending email:** " . $mail) : null)->redirectWithMessages($this->application->getControllerURL());
			}
			
			// Show the about/contact view
			return $this->application->view()->title("Contact")->render("about/contact");
		}
		
		public function facebook() {
			if(!is_string($url = $this->application->configuration()->controller()->get([ "facebook_url" ])))
				return "_404";
			
			$ext_url = implode("/", func_get_args());
			if(strlen($ext_url) > 0) $ext_url = "/" . $ext_url;
			return $this->application->redirect("https://facebook.com/" . trim($url, "/") . $ext_url);
		}
		
		public function twitter() {
			if(!is_string($url = $this->application->configuration()->controller()->get([ "twitter_url" ])))
				return "_404";
			
			$ext_url = implode("/", func_get_args());
			if(strlen($ext_url) > 0) $ext_url = "/" . $ext_url;
			return $this->application->redirect("https://twitter.com/" . trim($url, "/") . $ext_url);
		}
		
		public function github() {
			if(!is_string($url = $this->application->configuration()->controller()->get([ "github_url" ])))
				return "_404";
			
			$ext_url = implode("/", func_get_args());
			if(strlen($ext_url) > 0) $ext_url = "/" . $ext_url;
			return $this->application->redirect("https://github.com/" . trim($url, "/") . $ext_url);
		}
		
		public function terms() {
			if(is_string($terms = $this->configuration->get([ "terms_file" ])) && $this->application->filesystem()->file($terms))
				$this->configuration->set([ "terms_text" ], $this->application->filesystem()->read($terms));
			
			if(!is_string($terms = $this->configuration->get([ "terms_text" ])))
				return "_404";
			
			$class = $this->configuration->get([ "parsedown_class" ]);
			$parsedown = new $class();
			$parsedown->setBreaksEnabled(true);
			
			return $this->application->view()->title("Terms of Service")->renderString("<h2>Terms of Service</h2>" . $parsedown->text($terms));
		}
		
		public function privacy() {
			if(is_string($privacy = $this->configuration->get([ "privacy_file" ])) && $this->application->filesystem()->file($privacy))
				$this->configuration->set([ "privacy_text" ], $this->application->filesystem()->read($privacy));
			
			if(!is_string($privacy = $this->configuration->get([ "privacy_text" ])))
				return "_404";
			
			$class = $this->configuration->get([ "parsedown_class" ]);
			$parsedown = new $class();
			$parsedown->setBreaksEnabled(true);
			
			return $this->application->view()->title("Privacy Policy")->renderString("<h2>Privacy Policy</h2>" . $parsedown->text($privacy));
		}
	}
	