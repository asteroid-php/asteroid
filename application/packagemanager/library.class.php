<?php
	/* Asteroid
	 * class Library
	 * 
	 * Sets everything up for the package manager.
	 */
	namespace Asteroid\PackageManager;
	use Asteroid\Exception;
	class Library {
		protected $application = null;
		protected $loader = null;
		
		protected $directory = null;
		protected $packages_json = null;
		protected $users = Array();
		
		public function __construct($directory, $users = null, $packages_json = null) {
			if(!is_string($directory) || (!is_dir($directory) && !mkdir($directory)))
				throw new Exception(__METHOD__, "\$directory must be a string containing a valid directory.");
			else $this->directory = $directory;
			
			if(is_array($users)) $this->users = $users;
			else $this->users = Array();
			
			if(is_string($packages_json) && is_file($packages_json))
				$this->packages_json = $packages_json;
			else $this->packages_json = rtrim($directory, "/") . "/packages.json";
		}
		
		public function init($application) {
			$this->application = $application;
			
			if(empty($this->users))
				$this->users = (array)$application->configuration([ "auth", "admins" ]);
			
			$application->configuration([ "controllers", "packages" ], "Asteroid\\PackageManager\\Controller");
			$application->configuration([ "packagemanager", "directory" ], $this->directory);
			$application->configuration([ "packagemanager", "packages_json" ], $this->packages_json);
			$application->configuration([ "packagemanager", "users" ], $this->users);
			
			if(!is_object($this->loader))
				$this->loader = new Loader($this->application);
			
			// Don't load any packages inside the package manager or authentication handler
			if(!in_array($application->getController(), Array("Asteroid\\PackageManager\\Controller", $application->configuration([ "auth", "handler" ]))))
				$this->loader->loadPackages($this->directory, $this->packages_json);
		}
	}
	