<?php
	/* Asteroid
	 * class Controller
	 * 
	 * This is the package manager controller.
	 */
	namespace Asteroid\PackageManager;
	use Asteroid\BaseController;
	class Controller extends BaseController {
		public $default_action = "error";
		
		public function __construct($application) {
			$this->application = $application;
			
			// Setup views
			$this->application->configuration([ "template_header" ], null);
			$this->application->configuration([ "template_messages" ], null);
			$this->application->configuration([ "template_footer" ], null);
			
			// An administrator must be logged in to access this
			if(!$this->application->authentication()->loggedin($user) || !in_array($user->username, (array)$application->configuration([ "packagemanager", "users" ])))
				$this->application->error("You don't have permission to access this.")->view()->render("packagemanager/default", Array(
					"permission" => false
				));
			
			// Do some checks
			$filesystem = $this->application->filesystem();
			if(!$filesystem->readable($this->application->configuration([ "packagemanager", "packages_json" ])))
				$this->application->error("Packages.json is not readable.")->view()->render("packagemanager/default");
			if(!$filesystem->readable($this->application->configuration([ "packagemanager", "directory" ])))
				$this->application->error("Packages directory is not readable.")->view()->render("packagemanager/default");
			if(!$filesystem->writeable($this->application->configuration([ "packagemanager", "packages_json" ])))
				$this->application->error("Packages.json is not writeable.");
			if(!$filesystem->writeable($this->application->configuration([ "packagemanager", "directory" ])))
				$this->application->error("Packages directory is not writeable.");
		}
		
		public function index() {
			$packages = (new Packages($this->application))->getPackages();
			$this->application->view()->render("packagemanager/packages", Array(
				"packages" => $packages->packages
			));
		}
		
		public function details($package_directory = null) {
			$package = new Package($this->application, $package_directory);
			if(!is_object($package)) return $this->error();
			
			$filesystem = $this->application->filesystem($this->application->configuration([ "packagemanager", "directory" ]) . "/" . $package_directory);
			$package_configuration = $filesystem->read("configuration.json");
			
			$this->application->view()->render("packagemanager/details", Array(
				"package" => $package,
				"package_directory" => $package_directory,
				"package_configuration" => $package_configuration
			));
		}
		
		public function configuration($package_directory = null) {
			$package = new Package($this->application, $package_directory);
			if(!is_object($package)) return $this->error();
			
			if(is_string($new_configuration = $this->application->request()->post("configuration"))) {
				// Validate new configuration and format it
				// Wrap this in square brackets so it will always return an array upon success - if an error occured this will return false - but what if $new_configuration == "false"?
				$decoded = json_decode("[" . $new_configuration . "]");
				if(is_array($decoded)) $new_configuration = json_encode($decoded[0], JSON_PRETTY_PRINT);
				else $this->application->error("Configuration must be valid JSON.")->view()->render("packagemanager/default");
				
				$filesystem = $this->application->filesystem($this->application->configuration([ "packagemanager", "directory" ]) . "/" . $package_directory);
				if($filesystem->write("configuration.json", $new_configuration))
					$this->application->success("Updated configuration.")->view()->render("packagemanager/default");
				else $this->application->error("Failed to update configuration.")->view()->render("packagemanager/default");
			} else $this->application->error("Invalid request.")->view()->render("packagemanager/default");
		}
		
		public function enable($package_directory = null) {
			$packages_json = $this->application->configuration([ "packagemanager", "packages_json" ]);
			$packages = (new Packages($this->application))->getPackages();
			
			if(!isset($packages->packages->{$package_directory}))
				return $this->error();
			
			$packages->packages->{$package_directory}->enabled = true;
			$name = $packages->packages->{$package_directory}->name;
			if(!$this->application->filesystem()->write($packages_json, $content = json_encode($packages, JSON_PRETTY_PRINT)))
				$this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
			else $this->application->success("**" . $name . "** is now enabled.")->view()->render("packagemanager/default");
		}
		
		public function disable($package_directory = null) {
			$packages_json = $this->application->configuration([ "packagemanager", "packages_json" ]);
			$packages = (new Packages($this->application))->getPackages();
			
			if(!isset($packages->packages->{$package_directory}))
				return $this->error();
			
			$packages->packages->{$package_directory}->enabled = false;
			$name = $packages->packages->{$package_directory}->name;
			if(!$this->application->filesystem()->write($packages_json, $content = json_encode($packages, JSON_PRETTY_PRINT)))
				$this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
			else $this->application->success("**" . $name . "** is now disabled.")->view()->render("packagemanager/default");
		}
		
		public function delete($package_directory = null) {
			$packages_json = $this->application->configuration([ "packagemanager", "packages_json" ]);
			$packages = (new Packages($this->application))->getPackages();
			
			if(!$this->application->filesystem($this->application->configuration([ "packagemanager", "directory" ]))->deleteDirectory($package_directory)) {
				$this->application->error("Failed to remove package contents from the filesystem.");
				return;
			} else $this->application->success("Removed package contents from the filesystem.");
			
			if(!isset($packages->packages->{$package_directory}))
				return $this->error();
			
			$name = $packages->packages->{$package_directory}->name;
			$id = $packages->packages->{$package_directory}->id;
			$master = $packages->packages->{$package_directory}->master;
			unset($packages->ids->{$id}); unset($packages->masters->{$master});
			unset($packages->packages->{$package_directory});
			if(!$this->application->filesystem()->write($packages_json, $content = json_encode($packages, JSON_PRETTY_PRINT)))
				$this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
			else $this->application->message("**" . $name . "** has been deleted.", "warning")->view()->render("packagemanager/default");
		}
		
		// Install a package by an uploading an astp (zip) file
		public function upload() {
			if(!ini_get("file_uploads")) $this->application->error("File uploads are disabled.");
			if(isset($_FILES["file"]["tmp_name"]))
				(new Installer($this->application))->installFromAstp($_FILES["file"]["tmp_name"]);
			$this->application->view()->render("packagemanager/uploader", Array(
				"description" => "Upload a .astp or .zip package",
				"action" => $this->application->generateURL("packages", "upload"),
				"accept" => ".astp,.zip"
			));
		}
		
		// Install a package by entering a url
		public function url() {
			if(is_string($url = $this->application->request()->post("url")))
				(new Installer($this->application))->installFromURL($url);
			$this->application->view()->render("packagemanager/default");
		}
		
		// 404 Error
		public function error() {
			$this->application->response()->code(404);
			$this->application->error("The page you requested was not found.");
			$this->application->view()->render("packagemanager/default");
		}
	}
	