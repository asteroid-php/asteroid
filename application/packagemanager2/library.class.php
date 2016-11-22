<?php
	/* Asteroid
	 * Package Manager v2
	 * 
	 * Sets everything up for Package Manager 2.
	 */
	namespace Asteroid\PackageManager2;
	use Asteroid\Object;
	use Asteroid\JSON;
	use Asteroid\Exception;
	use ZipArchive;
	class Library {
		protected $application = null;
		
		protected $directory = null;
		protected $packages_json = "packages.json";
		protected $users = null;
		
		protected $packages = Array();
		protected $load_packages = Array();
		protected $required_packages = Array();
		protected $packages_json_loaded = false;
		
		public function __construct($directory = null) {
			if(is_object($directory)) return;
			
			if(is_string($directory)) $this->directory = $directory;
		}
		
		public function init($application) {
			if(is_object($this->application))
				return false;
			if($application->defined("packagemanager"))
				throw new Exception(__METHOD__, "Package Manager has already been setup on this application.");
			
			$this->application = $application;
			$application->define("packagemanager", $this);
			$application->define("package", function($id) use($application) {
				$packagemanager = $application->packagemanager;
				
				if($packagemanager->defined($id))
					return false;
				
				$package = $packagemanager->package($id);
				
				if(func_num_args() > 1)
					return call_user_func_array(Array($package->master, func_get_arg(1)), array_slice(func_get_args(), 2));
				else return $package;
			});
			
			$application->define("definepackage", function($id, $definition = null) use($application) {
				return $application->packagemanager->define($id, $definition);
			});
			$application->define("loadpackage", function($id) use($application) {
				return $application->packagemanager->load($id);
			});
			
			$application->configuration([ "controllers", "packages" ], "Asteroid\\PackageManager2\\Controller");
			$application->configuration([ "packagemanager", "directory" ], $this->directory);
			$application->configuration([ "packagemanager", "packages_json" ], $this->packages_json);
			$application->configuration([ "packagemanager", "users" ], $this->users);
			
			$this->filesystem = $application->filesystem($this->directory);
			$packages = $this->getPackagesJSON();
			
			if(!in_array($application->getController(), Array("Asteroid\\PackageManager2\\Controller", $application->configuration([ "auth", "handler" ]))) && !in_array($application->getControllerURL(), Array($commandline ? "packagemanager" : "packages", "auth")))
				$load_packages = true;
			else $load_packages = false;
			
			// Don't load any packages inside the package manager or authentication handler
			if((is_array($packages->load) || is_object($packages->load)) && $load_packages)
				$this->load_packages = (array)$packages->load;
			if((is_array($packages->require) || is_object($packages->require)) && $load_packages)
				$this->required_packages = (array)$packages->require;
			
			if(is_array($packages->get([ "packages" ])) || is_object($packages->get([ "packages" ])))
				foreach($packages->get([ "packages" ]) as $id => $directory)
					$this->define($id, $this->filesystem->path($directory));
			if(is_array($packages->get([ "define" ])) || is_object($packages->get([ "define" ])))
				foreach($packages->get([ "define" ]) as $id => $definition) {
					if(is_object($definition))
						$this->define($id, $definition);
					elseif(is_string($definition) && class_exists($definition))
						$this->define($id, new $definition_class);
					else $application->error("Failed to load package **{$id}**: \$definition must be an object or a class.", true);
				}
			
			$this->packages_json_loaded = true;
		}
		
		public function getPackagesJSON() {
			$packages = $this->filesystem->json($this->packages_json);
			if(!$packages->check([ "load" ], "array"))
				$packages->set([ "load" ], Array());
			if(!$packages->check([ "require" ], "array"))
				$packages->set([ "require" ], Array());
			return $packages;
		}
		
		public function hasLoadedPackagesJSON() {
			return $this->packages_json_loaded;
		}
		
		public function ready() {
			if(!in_array($this->application->getController(), Array("Asteroid\\PackageManager2\\Controller", get_class($this->application->authentication()->controller()))) && !in_array($this->application->getControllerURL(), Array("packages", "auth")))
				foreach($this->required_packages as $key => $id)
					if(!$this->defined($id) || !$this->load($id))
						$e = $this->application->error("Required package \"{$id}\" was not defined or failed to load.");
			
			if(isset($e))
				return false;
		}
		
		public function admin() {
			// An administrator must be logged in to access this
			if(!$this->application->authentication()->loggedin($user))
				return false;
			
			if(is_array($users = $this->application->configuration([ "packagemanager", "users" ])) || is_object($users)) {
				if(in_array($user->username, (array)$users)) return true;
				else return false;
			} else return $user->admin();
		}
		
		public function path($id = null, $n = null) {
			if(is_string($id) && $this->defined($id))
				return $this->package($id)->path($n);
			elseif(is_string($id))
				return null;
			else return $this->application->configuration([ "packagemanager", "directory" ]);
		}
		
		public function filesystem($id = null, $n = null) {
			return $this->application->filesystem($this->path($id, $n));
		}
		
		public function generateBackupPath($id) {
			$filesystem = $this->filesystem();
			$path = $filesystem->path() . "/__backup";
			$path .= "/" . md5($id);
			$path .= "/" . date("Y-m-d-H-i-s") . ".astpbackup";
			$filesystem->createFileDirectory($path);
			return $path;
		}
		
		public function getBackupList($id) {
			$path = $this->path() . "/__backup";
			$path .= "/" . md5($id);
			return $this->application->filesystem($path)->contents();
		}
		
		public function packages() {
			return $this->packages;
		}
		
		public function package($id) {
			if(isset($this->packages[$id]))
				return $this->packages[$id];
			else return null;
		}
		
		public function defined($id, &$package = null) {
			if(is_object($package = $this->package($id)))
				return true;
			else return false;
		}
		
		public function details($id) {
			if($this->defined($id))
				return $this->package($id)->get();
			else return null;
		}
		
		public function definition($id) {
			if($this->defined($id))
				return $this->package($id)->definition();
			else return null;
		}
		
		public function define($id, $definition = null) {
			if(is_string($id) && !is_string($definition) && !is_object($definition)) {
				$package_json = $this->application->filesystem($id)->read("package.json");
				$package_ = new Object(json_decode($package_json));
				
				$package = new Package($this->application, $package_->get([ "package", "id" ]), $definition);
				return $this->define($package->getID(), $package);
			}
			
			if($this->defined($id))
				throw new Exception(__METHOD__, "The package \"{$id}\" has already been defined.");
			
			if(is_object($definition) && ($definition instanceof Package))
				$package = $definition;
			else $package = new Package($this->application, $id, $definition);
			$this->packages[$id] = $package;
			
			if(in_array($id, $this->load_packages) || in_array($id, $this->required_packages))
				$package->load();
			
			return $package;
		}
		
		public function load($id) {
			if($this->defined($id))
				return $this->package($id)->load();
			else return false;
		}
		
		public function loaded($id) {
			if($this->defined($id))
				return $this->package($id)->loaded();
			else return false;
		}
		
		public function master($id) {
			if($this->defined($id))
				return $this->package($id)->master();
			else return null;
		}
		
		// function installFromURL(): Installs a package from a url
		public function installFromURL($url, $enable = false) {
			if(!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower(substr($url, 0, 6)), Array("http:/", "https:"))) {
				$this->application->error("Invalid URL.");
				return false;
			}
			
			$filesystem = $this->filesystem()->filesystem("__downloads");
			$path = time() . "-" . hash("sha256", $url) . ".astp";
			
			$filehandle = $filesystem->handle($path, "w");
			$curl = curl_init();
			curl_setopt_array($curl, Array(
				CURLOPT_URL => $url,
				CURLOPT_FILE => $filehandle,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => Array(
					"User-Agent: Asteroid v" . $this->application->getVersion() . " / Package Manager v2"
				)
			));
			
			$success = curl_exec($curl);
			curl_close($curl);
			fclose($filehandle);
			
			if(!$success) {
				$this->application->error("Failed to download package .astp.");
				return false;
			}
			
			$success = $this->installFromAstp($path);
			
			if(!$this->application->filesystem()->delete($path))
				$this->application->error("Failed to delete downloaded file **" . $path . "**.");
			
			return $success;
		}
		
		// function installFromAstp(): Installs a package from a file
		public function installFromAstp($path, $enable = false) {
			$filesystem = $this->filesystem();
			if(!$filesystem->file($path)) {
				$this->application->error("Could not find the .astp file.");
				return false;
			}
			
			$zip = new ZipArchive();
			if(($error = $zip->open($path)) !== true) {
				$this->application->error("Error parsing .astp file: " . $error);
				return false;
			}
			
			// Generate a directory name using the hash of the package contents and the current time
			$directoryname = time() . "-" . hash("sha256", $filesystem->read($path));
			$directory = $this->path() . "/" . $directoryname;
			if($filesystem->exists($directory)) {
				$this->application->error("Failed generating a directory name for this package.");
				return false;
			}
			
			// Extract the .astp file to this directory
			if($zip->extractTo($directory))
				$this->application->success("Copied package contents to **" . $directory . "**.");
			
			// Setup this new package
			return $this->setupPackage($directory, $enable);
		}
		
		// function setupPackage(): Adds package info to the packages.json file
		protected function setupPackage($directory, $enable = false) {
			$packages = $this->getPackagesJSON();
			
			$filesystem = $this->application->filesystem($directory);
			$package_details = $filesystem->json("package.json");
			
			if(!is_string($name = $package_details->get([ "package", "name" ]))
				|| !is_string($package_details->get([ "package", "description" ]))
				|| !is_string($package_id = $package_details->get([ "package", "id" ]))
				|| !is_string($package_details->get([ "package", "master" ])) // We have to wait until the next page load to check if the master exists
			) {
				// Rollback the entire process
				$this->application->error("Invalid package.json.");
				$package_details = null;
				if(!$filesystem->delete())
					$this->application->error("Failed to delete invalid package at **" . $directory . "**.");
				return false;
			} else $this->application->success("Package.json is valid.");
			
			if($this->defined($package_id)) {
				// Rollback the entire process
				$this->application->error("Another package with the same id is already installed.");
				$package_details = null;
				if(!$filesystem->delete())
					$this->application->error("Failed to delete invalid package at **" . $path . "**.");
				return false;
			}
			
			$package = $this->define($package_id, $directory);
			
			$packages->set([ "packages", $package->id ], $directory);
			if(($enable === true) || ($enable == "require"))
				$packages->add([ "load" ], $package->id);
			if($enable == "require")
				$packages->add([ "require" ], $package->id);
			
			if(!$packages->save()) {
				// Rollback the entire process
				$this->application->error("Failed to update packages.json file.");
				if(!$filesystem->delete())
					$this->application->error("Failed to delete invalid package at **" . $path . "**.");
				return false;
			}
			
			$this->application->success("**" . $name . "** has been installed.");
			if(is_string($package->definition->setup_controller))
				$this->application->message("Go to **[" . $this->application->generateRelativeURL("packages", $package->id, "setup") . "](" . $this->application->generateURL("packages", $package->id, "setup") . ")** to setup this package.");
			
			return true;
		}
	}