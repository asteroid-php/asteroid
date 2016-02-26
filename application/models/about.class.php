<?php
	/* Asteroid
	 * class About
	 * 
	 * This is the about model, it handles data for the about controller.
	 */
	namespace Asteroid\Models;
	use Asteroid\Object;
	class About {
		public function getAndValidateContactData($name, $contact, $message) {
			$isEmail = function($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); };
			$filterPhone = function($tel) { return str_replace(Array(" "), "", $tel); };
			$isPhone = function($tel) { return is_numeric($tel) && (strlen("{$tel}") == 11); };
			
			$name = trim(htmlentities($name));
			$contact = trim(htmlentities($contact));
			$message = trim(htmlentities($message));
			
			if((strlen($name) > 255) || (strlen($contact) > 255))
				return new Object(Array("success" => false, "error" => "Name & Email / Phone must be less than 255 characters."));
			elseif(strlen($message) > 1500)
				return new Object(Array("success" => false, "error" => "Message must be less than 1500 characters."));
			elseif(!$isEmail($contact) && !$isPhone($contact = $filterPhone($contact)))
				return new Object(Array("success" => false, "error" => "Email / Phone must be a valid email address or UK phone number."));
			else return new Object(Array("success" => true, "name" => $name, "contact" => $contact, "message" => $message));
		}
		
		public function saveContactData($database, $table, $user_id, $name, $contact, $message) {
			$query = $database->prepare("INSERT INTO `{$table}` (`user_id`, `name`, `contact`, `message`) VALUES (:user_id, :name, :contact, :message)");
			$query->bindValue(":user_id", $user_id);
			$query->bindValue(":name", $name);
			$query->bindValue(":contact", $contact);
			$query->bindValue(":message", $message);
			if($query->execute())
				return new Object(Array("success" => true, "id" => $database->lastInsertId()));
			else return new Object(Array("success" => false, "error" => $database->errorInfo()));
		}
		
		public function sendContactNotification($contact_addresses, $contact_url, $controlpanel_url, $name, $contact, $message, $id) {
			$mail = $this->application->mail();
			foreach($contact_addresses as $email => $name) {
				if(is_string($email)) $mail->addAddress($email, $name);
				else $mail->addAddress($name);
			}
			
			$mail->Subject = "Form Submission (\"" . $contact_url . "\")";
			$mail->variables = Array("{id}" => (int)$id, "{name}" => htmlentities($name), "{contact}" => htmlentities($contact), "{message}" => htmlentities($message));
			
			$mail->Body = "<!DOCTYPE html><html><body>";
			$mail->Body .= "<p><b>Form Submission</b></p><p><br /></p><p>ID: {id}<br />\nName: {name}<br />\nContact: {contact}\nMessage: {message}</pre></div><p><br /><br /></p>You can reply to this form submission in the <a href=\"" . $controlpanel_url . "\">Control Panel</a>.</p>";
			$mail->Body .= "</body></html>\n";
			$mail->AltBody = "Form Submission\n\nID: {id}\nName: {name}\nContact: {contact}\nMessage: {message}\n\nYou can reply to this form submission in the Control Panel.\n";
			
			// Send the email
			return $mail->Send();
		}
	}
	