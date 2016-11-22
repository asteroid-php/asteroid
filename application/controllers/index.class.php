<?php
	/* Asteroid
	 * class Index
	 * 
	 * This is the index controller, it controls /index
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	use Asteroid\HTML;
	class Index extends BaseController {
		public function index() {
			return $this->application->view()->render($this->application->configuration([ "template_index" ]));
		}
		
		public function test_theme() {
			return $this->application->view()->render("test-template");
		}
		
		public function session($get = null) {
			$session = $this->application->session();
			
			if($get == "id")
				return $this->application->response()->sendJSON(Array(
					"session_cookie" => $name = $this->application->configuration([ "session", "cookie_name" ]),
					"was_session" => is_string($this->application->request()->cookie($name)),
					"id" => $session->getID()
				));
			
			$session = $session->get();
			if(is_object($session))
				$session = (array)$session;
			
			$excludes = $this->application->configuration([ "session", "print_excludes" ]);
			if(is_array($excludes)) foreach($excludes as $exclude)
				if(isset($session[$exclude]))
					$session[$exclude] = null;
			
			return $this->application->view()->renderString("<h2>Session</h2>" . (new HTML($session))->variable());
		}
		
		public function user() {
			$loggedin = $this->application->authentication()->loggedin($user);
			return $this->application->view()->renderString("<h2>User</h2><p>You <b>are" . ($loggedin === true ? "" : " not") . "</b> logged in.</p>" . (new HTML($user))->variable());
		}
		
		public function cookies() {
			$cookies = $this->application->request()->cookie();
			return $this->application->view()->renderString("<h2>Cookies</h2>" . (new HTML($cookies))->variable());
		}
		
		public function captcha() {
			// Generates a captcha string, stores it in the session and generates a captcha graphic file to the browser
			
			// Get captcha options
			$captcha_width = $this->application->configuration([ "captcha", "width" ]); // If this is not set correctly the user may not see the whole captcha!
			$captcha_height = $this->application->configuration([ "captcha", "height" ]);
			$captcha_length = $this->application->configuration([ "captcha", "length" ]);
			$captcha_chars = $this->application->configuration([ "captcha", "chars" ]);
			$captcha_font = $this->application->configuration([ "captcha", "font_path" ]);
			
			if(!is_int($captcha_width)) $captcha_width = 250;
			if(!is_int($captcha_height)) $captcha_height = 70;
			if(!is_int($captcha_length)) $captcha_length = 6;
			if(!is_string($captcha_chars)) $captcha_chars = "ABCDEFGHJKLMNPRTUVWXYZ2346789"; // Excludes I, O, Q, S, 0, 1, 5
			if($captcha_length > strlen($captcha_chars)) $captcha_length = strlen($captcha_chars);
			if(!is_string($captcha_font) || !file_exists($captcha_font)) $captcha_font = __DIR__ . "/../../../../public/static/fonts/captcha.ttf";
			
			$str_captcha = "";
			
			// Create target captcha with letters comming from $str_choice
			for($i = 0; $i < $captcha_length; $i++) {
				do {
					$ipos = rand(0, strlen($captcha_chars) - 1);
					
					// checks that each letter is used only once
				} while(stripos($str_captcha, $captcha_chars[$ipos]) !== false);
				
				$str_captcha .= $captcha_chars[$ipos];
			}
			
			// Store the real captcha string in the session
			$this->application->session()->set("captcha", $str_captcha);
			
			// Check if TrueType is enabled - if not request image from samuelthomas.ml
			if(function_exists("imagefttext")) {
				// Begin to create the image with PHP's GD tools
				$image = imagecreatetruecolor($captcha_width, $captcha_height);
				
				// Add a transparent background
				if(function_exists("imagepng")) {
					imagealphablending($image, false);
					$background = imagecolorallocatealpha($image, 0, 0, 0, 127);
					imagefill($image, 0, 0, $background);
					imagesavealpha($image, true);
				} else {
					$background = imagecolorallocate($image, 255, 255, 255);
					imagefill($image, 0, 0, $background);
				}
				
				for($i = 0; $i < $captcha_length; $i++) {
					$text_colour = imagecolorallocate($image, rand(0, 100), rand(10, 100), rand(0, 100));
					$x = 20 + ($i * 35) + rand(-5, 5);
					$y = 35 + rand(10, 30);
					imagefttext($image, 35, rand(-10, 10), $x, $y, $text_colour, $captcha_font, $str_captcha[$i]);
				}
				
				// Send http-header to prevent image caching
				$this->application->response()->header("Content-Type", function_exists("imagepng") ? "image/png" : "image/jpeg");
				$this->application->response()->header("Pragma", "no-cache");
				$this->application->response()->header("Cache-Control", "no-store, no-cache, proxy-revalidate");
				
				// Send image to browser and destroy image from php cache
				if(function_exists("imagepng")) imagepng($image);
				else imagejpeg($image);
				imagedestroy($image);
			} else {
				// Request a captcha image from samuelthomas.ml
				// This MUST be done here on the server because the request exposes the captcha text
				$request = $this->application->http("GET", "https://samuelthomas.ml/index/captcha/" . $str_captcha);
				$request->execute();
				
				// Send http-header to prevent image caching
				$this->application->response()->header("Content-Type", $request->getHeader("Content-Type"));
				$this->application->response()->header("Pragma", "no-cache");
				$this->application->response()->header("Cache-Control", "no-store, no-cache, proxy-revalidate");
				
				// Send image to browser
				$this->application->response()->add($request->response());
			}
			
			return $this->application->status("Success");
		}
		
		private function image($image) {
			// Gets an image
			if(is_array($image)) {
				// Rewrite to another controller
				$this->application->parseURL(call_user_func_array(Array($this->application, "generateRelativeURL"), $image), false);
				return $this->application->controller()->loadFromURL($this->application->getControllerURL(), $this->application->getAction(), $this->application->getActionInfo());
			} elseif(is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
				// Rewrite to the image url
				$request = $this->application->http("GET", $image);
				$request->execute();
				
				$this->application->response()->header("Content-Type", $request->getHeader("Content-Type"));
				$this->application->response()->add($request->response());
			} elseif(is_string($image) && file_exists($image)) {
				// Output the contents of the image file
				$image = imagecreatefromstring(file_get_contents($image));
				
				imagealphablending($image, false);
				$background = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $background);
				imagesavealpha($image, true);
				
				if(function_exists("imagepng")) {
					$this->application->response()->header("Content-Type", "image/png");
					imagepng($image);
				} else {
					$this->application->response()->header("Content-Type", "image/jpeg");
					imagejpeg($image);
				}
			} else {
				// Create a 1x1 transparent image
				$image = imagecreatetruecolor(1, 1);
				
				imagealphablending($image, false);
				$background = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $background);
				imagesavealpha($image, true);
				
				// Send image to browser and destroy image from php cache
				$this->application->response()->header("Content-Type", "image/png");
				imagepng($image);
				imagedestroy($image);
			}
			
			return $this->application->status("Success");
		}
		
		public function background() {
			return $this->image($this->application->configuration([ "template_background" ]));
		}
		
		public function logo() {
			return $this->image($this->application->configuration([ "template_logo" ]));
		}
		
		public function icon() {
			return $this->image($this->application->configuration([ "template_icon" ]));
		}
		
		public function apple_touch_icon() {
			return $this->image($this->application->configuration([ "template_apple_touch_icon" ]));
		}
	}
	