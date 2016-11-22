<?php
	/* Asteroid
	 * class Controller
	 * 
	 * This is the package manager controller.
	 */
	namespace Asteroid\PackageManager2;
	use Asteroid\BaseController;
	class Controller extends BaseController {
		public $default_action = "error";
		public $action_swap = Array("details", "setup", "configuration", "enable", "require", "disable", "backup", "backups", "get-backup", "restore", "restore-backup", "delete-backup", "delete");
		public $rewrite_actions = Array("require" => "_require", "get-backup" => "get_backup", "restore-backup" => "restore_backup", "delete-backup" => "delete_backup");
		
		public function __construct($application) {
			$this->application = $application;
			
			// Setup views
			$this->application->configuration([ "template_header" ], null);
			$this->application->configuration([ "template_messages" ], null);
			$this->application->configuration([ "template_footer" ], null);
			
			// An administrator must be logged in to access this
			if(!$this->application->packagemanager->admin())
				return $this->application->error("You don't have permission to access this.")->view()->render("packagemanager/default", Array("permission" => false));
			
			// Do some checks
			$filesystem = $this->application->packagemanager->filesystem();
			if(!$filesystem->readable($this->application->configuration([ "packagemanager", "packages_json" ])))
				throw $this->application->error("Packages.json is not readable.")->view()->render("packagemanager/default");
			if(!$filesystem->readable())
				throw $this->application->error("Packages directory is not readable.")->view()->render("packagemanager/default");
			if(!$filesystem->writeable($this->application->configuration([ "packagemanager", "packages_json" ])))
				$this->application->error("Packages.json is not writeable.");
			if(!$filesystem->writeable())
				$this->application->error("Packages directory is not writeable.");
		}
		
		public function index() {
			$packages = $this->application->packagemanager->packages();
			return $this->application->view()->render("packagemanager/packages-2", Array(
				"packages" => $packages
			));
		}
		
		public function details($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			$configuration_json = $package->filesystem("data")->read("configuration.json");
			
			return $this->application->view()->render("packagemanager/details-2", Array(
				"package" => $package,
				"configuration" => $configuration_json
			));
		}
		
		public function setup($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			$setup_controller = $package->definition->get([ "setup_controller" ]);
			$full_directory = $package->directory;
			
			// Check if this package has a setup controller
			if(!is_string($setup_controller))
				return $this->application->error("This package does not have a setup controller.")->view()->render("packagemanager/default");
			
			// Make sure this package is loaded
			if(!$package->load())
				return $this->application->error("Failed to load this package.")->view()->render("packagemanager/default");
			
			if(class_exists($setup_controller))
				return $this->application->subcontroller($setup_controller, Array(), 1);
			elseif($this->application->controller()->getClass($setup_controller) !== null)
				return $this->application->redirect($this->application->generateURL($setup_controller));
			else return $this->application->error("Setup controller was not found. **" . $setup_controller . "** must be an existing controller class or url.")->view()->render("blank");
		}
		
		public function configuration($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if(is_string($new_configuration = $this->application->request()->post("configuration"))) {
				// Validate new configuration and format it
				// Wrap this in square brackets so it will always return an array upon success - if an error occured this will return false - but what if $new_configuration == "false"?
				$decoded = json_decode("[" . $new_configuration . "]");
				if(is_array($decoded)) $new_configuration = json_encode($decoded[0], JSON_PRETTY_PRINT);
				else return $this->application->error("Configuration must be valid JSON.")->view()->render("packagemanager/default");
				
				$filesystem = $package->filesystem("data");
				if($filesystem->write("configuration.json", $new_configuration))
					return $this->application->success("Updated configuration.")->view()->render("packagemanager/default");
				else return $this->application->error("Failed to update configuration.")->view()->render("packagemanager/default");
			} else return $this->application->error("Invalid request.")->view()->render("packagemanager/default");
		}
		
		public function enable($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if($package->enable())
				return $this->application->success("**{$package->name}** is now enabled.")->view()->render("packagemanager/default");
			else return $this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
		}
		
		public function _require($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if($package->enable(true))
				return $this->application->success("**{$package->name}** is now enabled and required.")->view()->render("packagemanager/default");
			else return $this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
		}
		
		public function disable($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if($package->disable())
				return $this->application->success("**{$package->name}** is now disabled.")->view()->render("packagemanager/default");
			else return $this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
		}
		
		public function backup($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if($package->backupData($path))
				return $this->application->success("**{$package->name}**'s data was backed up to {$path}.")->view()->render("packagemanager/default");
			else return $this->application->error("Failed to backup this package's data.")->view()->render("packagemanager/default");
		}
		
		public function backups($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			$backups = $package->getBackupList();
			return $this->application->view()->render("packagemanager/backups", Array("package" => $package, "backups" => $backups));
		}
		
		public function get_backup($id = null, $backup_file = null, $format = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			$path = $package->getBackupPath($backup_file);
			$this->application->response()->header("Content-Type", $format == "zip" ? "application/zip" : "application/octet-stream");
			$this->application->response()->header("Content-Disposition", "attachment, filename=\"backup.astpbackup" . ($format == "zip" ? ".zip" : "") . "\"");
			return $this->application->response()->sendFile($path);
		}
		
		// Install a package by an uploading an astp (zip) file
		public function restore($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if(!ini_get("file_uploads"))
				return $this->application->error("File uploads are disabled.");
			
			if(isset($_FILES["file"]["tmp_name"])) {
				if($package->restoreFromBackup($_FILES["file"]["tmp_name"]))
					$this->application->success("Successfully restored data from backup.");
				else $this->application->error("Failed to restored data from backup.");
			}
			
			return $this->application->view()->render("packagemanager/uploader", Array(
				"description" => "Upload an .astpbackup or .zip file",
				"action" => $this->application->generateURL("packages", $id, "restore"),
				"accept" => ".astpbackup,.zip"
			));
		}
		
		public function restore_backup($id = null, $backup_file = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			$path = $package->getBackupPath($backup_file);
			if($package->restoreFromBackup($path))
				return $this->application->success("Successfully restored data from backup.")->view()->render("packagemanager/default");
			else return $this->application->error("Failed to restored data from backup.")->view()->render("packagemanager/default");
		}
		
		public function delete_backup($id = null, $backup_file = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			$path = $package->getBackupPath($backup_file);
			if($this->application->filesystem()->delete($path))
				return $this->application->success("The backup file {$backup_file} was deleted.")->view()->render("packagemanager/default");
			else return $this->application->error("Failed to delete backup.")->view()->render("packagemanager/default");
		}
		
		public function delete($id = null) {
			$package = $this->application->packagemanager->package($id);
			if(!is_object($package)) return $this->error();
			
			if(!$package->packagemanager)
				return $this->application->error("This package was not installed by Package Manager.")->view()->render("packagemanager/default");
			
			if($package->backupData($path))
				$this->application->success("**{$package->name}**'s data was backed up to {$path}.");
			else return $this->application->error("Failed to backup this package's data.")->view()->render("packagemanager/default");
			
			if($package->filesystem("autoloader")->directory() && !$package->filesystem("autoloader")->delete())
				return $this->application->error("Failed to delete package /Contents directory.")->view()->render("packagemanager/default");
			elseif($package->filesystem("templates")->directory() && !$package->filesystem("templates")->delete())
				return $this->application->error("Failed to delete package /Views directory.")->view()->render("packagemanager/default");
			elseif($package->filesystem("translations")->directory() && !$package->filesystem("translations")->delete())
				return $this->application->error("Failed to delete package /Translations directory.")->view()->render("packagemanager/default");
			elseif($package->filesystem("data")->directory() && !$package->filesystem("data")->delete())
				return $this->application->error("Failed to delete package /Data directory.")->view()->render("packagemanager/default");
			elseif($package->filesystem()->directory() && !$package->filesystem()->delete())
				return $this->application->error("Failed to delete package root directory.")->view()->render("packagemanager/default");
			else $this->application->success("Removed package contents from the filesystem.");
			
			$packages = $this->application->packagemanager->getPackagesJSON();
			
			$packages->set([ "packages", $package->id ], null);
			if(is_array($load_packages = $packages->get([ "load" ])))
				$packages->set([ "load" ], array_filter($load_packages, function($value) use($package) {
					return $package->id == $value;
				}));
			if(is_array($require_packages = $packages->get([ "require" ])))
				$packages->set([ "require" ], array_filter($require_packages, function($value) use($package) {
					return $package->id == $value;
				}));
			$packages->set([ "define", $package->id ], null);
			
			if(!$packages->save())
				return $this->application->error("Failed to update packages.json file.")->view()->render("packagemanager/default");
			else return $this->application->message("**" . $name . "** has been deleted.", "warning")->view()->render("packagemanager/default");
		}
		
		// Install a package by an uploading an astp (zip) file
		public function upload() {
			if(!ini_get("file_uploads"))
				return $this->application->error("File uploads are disabled.");
			
			if(isset($_FILES["file"]["tmp_name"]))
				$this->application->packagemanager->installFromAstp($_FILES["file"]["tmp_name"], true);
			
			return $this->application->view()->render("packagemanager/uploader", Array(
				"description" => "Upload an .astp or .zip package",
				"action" => $this->application->generateURL("packages", "upload"),
				"accept" => ".astp,.zip"
			));
		}
		
		// Install a package by entering a url
		public function url() {
			if(is_string($url = $this->application->request()->post("url")))
				$this->application->packagemanager->installFromURL($url, true);
			return $this->application->redirectWithMessages("packages");
		}
		
		// 404 Error
		public function error() {
			// Return a http success code to prevent ajaxify refreshing the page
			if($this->application->request()->header("X-Requested-With") != "xmlhttprequest")
				$this->application->response()->code(404);
			
			$this->application->error("The page you requested was not found.");
			return $this->application->view()->render("packagemanager/default");
		}
	}
	