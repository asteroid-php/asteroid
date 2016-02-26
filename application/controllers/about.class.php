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
			$this->application->view()->title("About")->render("about/about");
		}
		
		public function contact($save = false) {
			if($save == "save") {
				$model = $this->application->model("About");
				$data = $model->getAndValidateContactData($this->application->request()->post("name"), $this->application->request()->post("contact"), $this->application->request()->post("message"));
				if($data->get([ "success" ]) != true)
					$this->application->view()->renderJSON(Array("success" => false, "error" => $data->get([ "error" ])));
				
				list($name, $contact, $message) = $data->geta([ "name" ], [ "contact" ], [ "message" ]);
				
				$database = $this->application->database($this->application->configuration()->controller()->get([ "contact_database" ]));
				$table = $this->application->configuration()->controller()->get([ "contact_table" ]);
				$user_id = $this->application->authentication()->loggedin($user) == true ? $user->getID() : null;
				$data = $model->saveContactData($database, $table, $user_id, $name, $contact, $message);
				if($data->get([ "success" ]) != true)
					$this->application->view()->renderJSON(Array("success" => false, "error" => $this->application->errors() ? implode(", ", $querydata->get([ "error" ])) : null));
				
				$id = $data->get([ "id" ]);
				
				// Success!
				// Send an email to $config->about->contact_addresses
				$contact_addresses = $this->application->configuration()->controller()->get([ "contact_addresses" ]);
				$contact_url = $this->application->generateURL($this->application->getControllerURL(), "contact");
				$controlpanel_url = $this->application->generateURL("control-panel");
				$sent = $model->sendContactNotification($contact_addresses, $contact_url, $controlpanel_url, $name, $contact, $message, $id);
				
				// Return success, even if the email couldn't send
				$this->application->view()->renderJSON(Array("success" => true, "mailStatus" => $sent ? "Sent!" : "Error Sending: " . $mail->ErrorInfo));
			} else {
				// Show the about/contact view
				$this->application->view()->title("Contact")->render("about/contact");
			}
		}
		
		public function github() {
			if(!is_string($url = $this->application->configuration()->controller()->get([ "github_url" ])))
				return "_404";
			
			$ext_url = implode("/", func_get_args());
			if(strlen($ext_url) > 0) $ext_url = "/" . $ext_url;
			$this->application->redirect("https://github.com/" . trim($url, "/") . $ext_url);
		}
	}
	