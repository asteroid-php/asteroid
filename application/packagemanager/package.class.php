<?php
	/* Asteroid
	 * class Package
	 * 
	 * Reads / writes to the packages.json file and each package's configuration.json file.
	 */
	namespace Asteroid\PackageManager;
	use Asteroid\Autoloader;
	use Asteroid\Object;
	use Asteroid\Exception;
	class Package {
		protected $application = null;
		protected $package_directory = null;
		protected $package = null;
		protected $configuration = null;
		private $name = null;
		
		public function __construct($application, $package_directory) {
			$this->application = $application;
			$this->package_directory = $package_directory;
		}
		
		// function path(): Returns the package's location
		public function path() {
			return rtrim($this->application->configuration([ "packagemanager", "directory" ]), "/") . "/" . $this->package_directory;
		}
		
		// function getPackage(): Parses the packages.json file
		public function getPackage() {
			$packages_json = $this->application->configuration([ "packagemanager", "packages_json" ]);
			$packages = (new Packages($this->application))->getPackages();
			if(isset($packages->packages->{$this->package_directory}))
				return new Object($packages->packages->{$this->package_directory});
			else return null;
		}
		
		// function configuration(): Gets / set configuration values
		public function configuration($key, $value = null) {
			if(func_num_args() > 1) {
				$this->configuration(Array());
				$this->configuration->set($key, $value);
				
				$this->application->filesystem($this->path())->write("configuration.json", json_encode($this->data));
			} elseif(func_num_args() == 1) {
				if(!is_object($this->configuration) || !($this->configuration instanceof Object))
					$this->configuration = new Object(json_decode($this->application->filesystem($this->path())->read("configuration.json")));
				
				return $this->configuration->get($key);
			} else return null;
		}
		
		// function get(): Gets a configuration value
		public function get($key) {
			if(!is_object($this->package) || !($this->package instanceof Object))
				$this->package = $this->getPackage();
			
			return $this->package->get($key);
		}
		
		public function __get($name) {
			if(!is_object($this->package) || !($this->package instanceof Object))
				$this->package = $this->getPackage();
			
			return $this->package->get($name);
		}
	}
	