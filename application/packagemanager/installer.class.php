<?php
	/* Asteroid
	 * class Installer
	 * 
	 * Installs packages.
	 */
	namespace Asteroid\PackageManager;
	use ZipArchive;
	class Installer {
		protected $application = null;
		
		public function __construct($application) {
			$this->application = $application;
		}
		
		// function installFromURL(): Installs a package from a url
		public function installFromURL($url) {
			if(!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower(substr($url, 0, 6)), Array("http:/", "https:"))) {
				$this->application->error("Invalid URL.");
				return;
			}
			
			$path = rtrim($this->application->configuration([ "packagemanager", "directory" ]), "/") . "/tmp-" . time() . "-" . hash("sha256", $url) . ".astp";
			$filehandle = fopen($path, "w"); $curl = curl_init();
			curl_setopt_array($curl, Array(CURLOPT_URL => $url, CURLOPT_FILE => $filehandle, CURLOPT_FOLLOWLOCATION => true));
			$success = curl_exec($curl); curl_close($curl); fclose($filehandle);
			if(!$success) {
				$this->application->error("Failed to download package .astp.");
				return;
			}
			
			$this->installFromAstp($path);
			if(!$this->application->filesystem()->delete($path))
				$this->application->error("Failed to delete downloaded file **" . $path . "**");
		}
		
		// function installFromAstp(): Loads a package
		public function installFromAstp($path) {
			$filesystem = $this->application->filesystem();
			if(!$filesystem->file($path)) {
				$this->application->error("Could not find the .astp file.");
				return;
			}
			
			$zip = new ZipArchive();
			if(($error = $zip->open($path)) !== true) {
				$this->application->error("Error parsing .astp file: " . $error);
				return;
			}
			
			// Generate a directory name using the hash of the package contents and the current time
			$directoryname = time() . "-" . hash("sha256", $filesystem->read($path));
			$directory = rtrim($this->application->configuration([ "packagemanager", "directory" ]), "/") . "/" . $directoryname;
			if($filesystem->exists($directory)) {
				$this->application->error("Failed generating a directory name for this package.");
				return;
			}
			
			// Extract the .astp file to this directory
			if($zip->extractTo($directory))
				$this->application->success("Copied package contents to **" . $directory . "**.");
			
			// Setup this new package
			$this->setup($directory, $directoryname);
		}
		
		// function setup(): Adds package info to the packages.json file
		public function setup($path, $directory) {
			$packages_json = $this->application->configuration([ "packagemanager", "packages_json" ]);
			$packages = (new Packages($this->application))->getPackages();
			
			$package_json = rtrim($path, "/") . "/package.json";
			$package = json_decode($this->application->filesystem()->read($package_json));
			
			if(!isset($package->package)
				|| !isset($package->package->name) || !is_string($name = $package->package->name)
				|| !isset($package->package->description) || !is_string($package->package->description)
				|| !isset($package->package->id) || !is_string($package->package->id)
				|| !isset($package->package->master) || !is_string($package->package->master) // We have to wait until the next page load to check if the master exists
			) {
				// Rollback the entire process
				$this->application->error("Invalid package.json.");
				if(!$this->application->filesystem()->delete($path))
					$this->application->error("Failed to delete invalid package at **" . $path . "**.");
				return;
			} else $this->application->success("Package.json is valid.");
			
			if(isset($packages->ids->{$package->package->id}) || isset($packages->masters->{$package->package->master})) {
				// Rollback the entire process
				$this->application->error("Another package with the same id or master is already installed.");
				if(!$this->application->filesystem()->delete($path))
					$this->application->error("Failed to delete invalid package at **" . $path . "**.");
				return;
			}
			
			$package->package->enabled = true;
			
			$packages->packages->{$directory} = $package->package;
			$packages->ids->{$package->package->id} = $directory;
			$packages->masters->{$package->package->master} = $directory;
			if(!$this->application->filesystem()->write($packages_json, $content = json_encode($packages, JSON_PRETTY_PRINT))) {
				// Rollback the entire process
				$this->application->error("Failed to update packages.json file.");
				if(!$this->application->filesystem()->delete($path))
					$this->application->error("Failed to delete invalid package at **" . $path . "**.");
				return;
			}
			
			$this->application->success("**" . $name . "** has been installed.");
			if(isset($packages->packages->{$directory}->setup_controller) && is_string($setup_url = $packages->packages->{$directory}->setup_controller))
				$this->application->message("Go to **" . $this->application->generateURL($setup_url) . "** to setup this package.");
		}
	}
	