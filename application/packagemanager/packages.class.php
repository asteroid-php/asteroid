<?php
	/* Asteroid
	 * class Packages
	 * 
	 * Reads / writes to the packages.json file.
	 */
	namespace Asteroid\PackageManager;
	use Asteroid\Autoloader;
	use Asteroid\Object;
	use Asteroid\Exception;
	class Packages {
		protected $application = null;
		
		public function __construct($application) {
			$this->application = $application;
		}
		
		// function getPackages(): Parses the packages.json file
		public function getPackages() {
			$packages_json = $this->application->configuration([ "packagemanager", "packages_json" ]);
			$packages = json_decode($this->application->filesystem()->read($packages_json));
			if(!is_object($packages)) $packages = (object)Array("packages" => (object)Array(), "ids" => (object)Array(), "masters" => (object)Array());
			return $packages;
		}
	}
	