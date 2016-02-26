<?php
	/* Asteroid
	 * class Loader
	 * 
	 * Loads packages installed by the package manager.
	 */
	namespace Asteroid\PackageManager;
	use Asteroid\Autoloader;
	use Asteroid\Object;
	use Asteroid\Exception;
	class Loader {
		protected $application = null;
		
		public function __construct($application) {
			$this->application = $application;
		}
		
		// function loadPackages(): Parses the packages.json file and loads packages
		public function loadPackages($directory, $packages_json) {
			$packages = json_decode($this->application->filesystem()->read($packages_json));
			if(!is_object($packages)) $packages = (object)Array("packages" => Array());
			
			foreach($packages->packages as $package_directory => $package) {
				$package = new Object($package);
				$package->directory = $package_directory;
				$package_directory = rtrim($directory, "/") . "/" . $package_directory;
				$this->loadPackage($package, $package_directory);
			}
		}
		
		// function loadPackage(): Loads a package
		public function loadPackage($package, $directory) {
			$directory = rtrim($directory, "/");
			
			// Is this package enabled?
			if($package->enabled === false) return;
			
			// Check if package master exists
			if(class_exists($package->master)) {
				$this->application->error("Failed to load package **{$package->name}** [**{$package->id}**; **{$package->directory}**]: Package master already exists.");
				return;
			}
			
			// Check if composer should be loaded
			if($this->application->filesystem($directory)->file("composer.json")) {
				if($this->application->filesystem($directory)->file("vendor/autoload.php"))
					require_once $directory . "/vendor/autoload.php";
				else {
					if($this->application->errors())
						$this->application->error("Failed to load package **{$package->name}** [**{$package->id}**; **{$package->directory}**]: A composer.json file exists but composer has not been run. Run " . (!$this->application->filesystem($directory)->file("composer.phar") ? "`curl -sS https://getcomposer.org/installer | php` and " : "") . "`php composer.phar install` in **{$directory}** to use this package.");
					return;
				}
			}
			
			// Register autoloader
			$autoloader = new Autoloader("", $directory . "/Contents");
			$autoloader->register();
			
			// Load package master
			$autoloader->load($package->master);
			if(!class_exists($package->master)) {
				$this->application->error("Failed to load package **" . $package->name . "** [" . $package->id . "; " . $package->directory . "]: Could not find package master.");
				return;
			}
			
			// Create master
			$master = new $package->master($this->application);
			$master->package = $package;
			$master->application = $this->application;
			$master->autoloader = $autoloader;
			
			// Add views
			if(is_string($package->id))
				$this->application->configuration()->addViewDirectory($package->id, $directory . "/Views");
			
			// Get configuration
			$configuration = json_decode($this->application->filesystem($directory)->read("configuration.json"));
			if(is_object($configuration)) $configuration = new Object($configuration);
			$master->configuration = $configuration;
			
			// Init package
			$master->init($this->application);
		}
	}
	