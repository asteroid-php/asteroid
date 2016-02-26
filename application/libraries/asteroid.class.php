<?php
	/* Asteroid
	 * class Asteroid
	 * 
	 * Adds an "asteroid" controller.
	 */
	namespace Asteroid\Libraries;
	use Asteroid\BaseController;
	use Parsedown;
	class Asteroid extends BaseController {
		// The will be called twice, once when loading the library and once when loading the controller (as they use the same class)
		public function __construct($application) {
			$application->configuration([ "controllers", "asteroid" ], __CLASS__);
		}
		
		public function index() {
			$content = "<tr><td style=\" width: 200px; \">Version</td><td>Asteroid v" . $this->application->getVersion() . "</td></tr>";
			$content .= "<tr><td>GitHub</td><td><a href=\"" . $this->application->generateURL(Array("asteroid", "github")) . "\">asteroid-php/asteroid</a></td></tr>";
			$content .= "<tr><td>Author</td><td><a href=\"https://github.com/samuelthomas2774\">Samuel Elliott</a></td></tr>";
			$content .= "<tr><td>Author Email</td><td><a href=\"mailto:samuel@samuelthomas.ml\">samuel@samuelthomas.ml</a></td></tr>";
			if($this->application->authentication()->loggedin($user) && $user->admin()) $content .= "<tr><td>Configuration</td><td><a href=\"" . $this->application->generateURL(Array("control-panel", "configuration")) . "\"><button class=\"button\" type=\"button\">View configuration</button></a></td></tr>";
			$this->application->view()->title("About Asteroid")->renderString("<p><a href=\"" . $this->application->generateURL(Array("asteroid", "github")) . "\"><button class=\"button\" type=\"button\">GitHub</button></a> <a href=\"" . $this->application->generateURL(Array("asteroid", "readme")) . "\"><button class=\"button\" type=\"button\">README.md</button></a></p><table class=\"no-border\"><tbody>" . $content . "</tbody></table><p><span class=\"ui-icon ui-icon-flag\"></span> You can hide this information by removing `Asteroid\Libraries\Asteroid` from your libraries configuration.</p>");
		}
		
		public function readme() {
			$parsedown = new Parsedown();
			$content = $this->application->filesystem($this->application->configuration([ "filesystem", "root_dir" ]))->read("README.md");
			$this->application->view()->title("README.md")->renderString("<div class=\"markdown\">" . $parsedown->text($content) . "</div>");
		}
		
		public function github() {
			// Redirect to the GitHub repository
			$url = implode("/", func_get_args());
			$this->application->redirect("https://github.com/asteroid-php/asteroid" . (strlen(trim($url)) > 0 ? "/" . $url : ""));
		}
	}
	