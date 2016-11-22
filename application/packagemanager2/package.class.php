<?php
	/* Asteroid
	 * class Package
	 * 
	 * Reads / writes to the packages.json file and each package's configuration.json file.
	 */
	namespace Asteroid\PackageManager2;
	use Asteroid\Autoloader;
	use Asteroid\Object;
	use Asteroid\Exception;
	use Asteroid\JSON;
	use ZipArchive;
	class Package {
		protected $application = null;
		protected $id = null;
		protected $definition = null;
		protected $master = null;
		protected $packagemanager = false;
		
		protected $name = null;
		//protected $filesystem = null;
		protected $filesystem_root = null;
		protected $filesystem_contents = null;
		protected $filesystem_templates = null;
		protected $filesystem_data = null;
		protected $path = null;
		protected $directory = null;
		protected $enabled = null;
		
		public function __construct($application, $id, $definition) {
			$this->application = $application;
			
			if(is_object($definition) && ($definition instanceof PackageDefinition))
				$this->definition = $definition;
			elseif(is_string($definition))
				$this->definition = $this->loadFromDirectory($definition);
			else throw new Exception(__METHOD__, "\$definition must be an Asteroid\\PackageManager2\\PackageDefinition object or a string containing the directory of a package to create a definition from.");
			
			if(!is_object($this->definition))
				throw new Exception(__METHOD__, "Invalid package definition.");
			elseif(!is_string($id) && !is_string($id = $this->definition->get([ "id" ])))
				throw new Exception(__METHOD__, "Invalid package id.");
			
			$this->id = $id;
			$this->packagemanager = !$this->application->packagemanager->hasLoadedPackagesJSON();
		}
		
		protected function loadFromDirectory($directory) {
			$package_json_path = rtrim($directory, "/") . "/package.json";
			$package_json = $this->application->filesystem()->read($package_json_path);
			return new PackageDefinition($this->application, json_decode($package_json), $directory);
		}
		
		// function definition(): Gets this package's definition
		public function definition() {
			return $this->definition;
		}
		
		// function master(): Gets this package's master
		public function master() {
			if(is_object($this->master))
				return $this->master;
			else return null;
		}
		
		// function id(): Gets this package's id
		public function id() {
			return $this->id;
		}
		
		// function __get(): Gets this package's id, definition or master
		public function __get($name) {
			if($name == "id") return $this->id;
			elseif($name == "definition") return $this->definition;
			elseif($name == "name") return $this->definition->get([ "name" ]);
			elseif($name == "master") return $this->master;
			elseif($name == "packagemanager") return $this->packagemanager;
			elseif($name == "filesystem") return $this->filesystem();
			elseif($name == "filesystem_contents") return $this->filesystem("autoloader");
			elseif($name == "filesystem_templates") return $this->filesystem("templates");
			elseif($name == "filesystem_data") return $this->filesystem("data");
			elseif($name == "packagejson") return $this->getPackageJSON();
			elseif($name == "configuration") return $this->getConfigurationJSON();
			elseif($name == "configurationjson") return $this->getConfigurationJSON();
			elseif($name == "path") return $this->path();
			elseif($name == "directory") return $this->path();
			elseif($name == "enabled") return $this->automaticallyLoads();
			else return null;
		}
		
		public function __isset($name) {
			if($this->__get($name) !== null)
				return true;
			else return false;
		}
		
		// function path(): Returns the package's location
		public function path($n = null) {
			$root = $this->definition->get([ "directory" ]);
			$d = $this->definition->get([ $n . "_directory" ]);
			if(!is_string($d)) return $root;
			return $this->application->filesystem($root)->path($d);
		}
		
		// function filesystem(): Returns a filesystem object in this package's directory
		public function filesystem($n = null) {
			return $this->application->filesystem($this->path($n));
		}
		
		public function getPackageJSON() {
			return $this->filesystem->json("package.json");
		}
		
		public function getConfigurationJSON() {
			return $this->filesystem("data")->json("configuration.json");
		}
		
		// function load(): Loads this package
		public function load() {
			if(is_object($this->master))
				return true;
			
			$filesystem = $this->filesystem;
			
			// Check if package master exists
			if(class_exists($this->definition->get([ "master" ]))) {
				$this->application->error("Failed to load package **{$this->name}** [**{$this->id}**; **{$this->path}**]: Package master already exists.");
				return false;
			}
			
			// Check if composer should be loaded
			$composer = null;
			if($filesystem->file("composer.json")) {
				if($filesystem->file("vendor/autoload.php"))
					$composer = require_once $filesystem->path("vendor/autoload.php");
				else {
					$this->application->error("Failed to load package **{$this->name}** [**{$this->id}**; **{$this->path()}**]: A composer.json file exists but composer has not been run. Run " . (!$filesystem->file("composer.phar") ? "`curl -sS https://getcomposer.org/installer | php` and " : "") . "`php composer.phar install` in **{$this->path}** to use this package.", true);
					return false;
				}
			}
			
			// Register autoloader
			$autoloader = $this->autoloader = new Autoloader($this->definition->get([ "autoloader_prefix" ]), $filesystem->path($this->definition->get([ "autoloader_directory" ])), $this->definition->get([ "autoloader_extension" ]));
			
			// Load package master
			$autoloader->load($master_class = $this->definition->get([ "master" ]));
			if(!class_exists($this->definition->get([ "master" ]))) {
				$this->application->error("Failed to load package **{$this->name}** [**{$this->id}**; **{$this->path}**]: Could not find package master.");
				return false;
			}
			
			$autoloader->register();
			
			// Create master
			$configuration = $this->getConfigurationJSON();
			$configuration->autosave(false);
			$master = $this->master = new $master_class($this->application, $this, $configuration);
			$master->application = $this->application;
			$master->package = $this;
			$this->autoloader = $master->autoloader = $autoloader;
			$this->composer = $master->composer = $composer;
			$this->filesystem_root = $master->root_dir = $filesystem;
			$this->filesystem_contents = $master->contents_dir = $this->application->filesystem($this->path("autoloader"));
			$this->filesystem_templates = $master->templates_dir = $this->application->filesystem($templates_dir = $this->path("templates"));
			$this->filesystem_data = $master->data_dir = $this->application->filesystem($this->path("data"));
			
			// Register the ready event
			if(method_exists($master, "ready"))
				$this->application->events()->bind("ready", Array($master, "ready"));
			
			// Add views
			$this->application->configuration()->addViewDirectory($this->id, $templates_dir);
			
			// Get configuration
			if($this->filesystem_data->file("configuration.json"))
				$master->configuration = $configuration = JSON::createFromFile($this->filesystem_data->path("configuration.json"));
			else $master->configuration = $configuration = new JSON();
			$configuration->autosave(false, $this->filesystem_data->path("configuration.json"));
			
			// Init package
			if($master->init($this->application) === false)
				return false;
			else return true;
		}
		
		public function loaded() {
			if(is_object($this->master))
				return true;
			else return false;
		}
		
		public function automaticallyLoads($check_required = true) {
			$packages = $this->application->packagemanager->getPackagesJSON();
			
			if(is_array($packages->load) && in_array($this->id, $packages->load))
				return true;
			elseif(is_array($packages->require) && in_array($this->id, $packages->require) && ($check_required === true))
				return true;
			else return false;
		}
		
		public function isRequired() {
			$packages = $this->application->packagemanager->getPackagesJSON();
			
			if(is_array($packages->require) && in_array($this->id, $packages->require))
				return true;
			else return false;
		}
		
		public function enable($require = false) {
			$packages = $this->application->packagemanager->getPackagesJSON();
			$package = $this;
			
			if(is_array($load_packages = $packages->get([ "load" ])) && !in_array($this->id, $load_packages))
				$packages->add([ "load" ], $this->id);
			if(is_array($require_packages = $packages->get([ "require" ])) && !in_array($this->id, $require_packages))
				$packages->add([ "require" ], $this->id);
			if(is_array($require_packages = $packages->get([ "require" ])) && in_array($this->id, $require_packages) && ($require !== true))
				$packages->set([ "require" ], array_values(array_filter($require_packages, function($value) use($package) {
					return $package->id != $value;
				})));
			
			return $packages->save();
		}
		
		public function disable() {
			$packages = $this->application->packagemanager->getPackagesJSON();
			
			if(is_array($load_packages = $packages->get([ "load" ])) && in_array($this->id, $load_packages))
				$packages->set([ "load" ], array_values(array_filter($load_packages, function($value) {
					return $this->id != $value;
				})));
			if(is_array($require_packages = $packages->get([ "require" ])))
				$packages->set([ "require" ], array_values(array_filter($require_packages, function($value) {
					return $this->id != $value;
				})));
			
			return $packages->save();
		}
		
		public function getBackupList() {
			return $this->application->packagemanager->getBackupList($this->id);
		}
		
		public function getBackupPath($backup_file) {
			$path = $this->application->packagemanager->path() . "/__backup";
			$path .= "/" . md5($this->id);
			
			return $this->application->filesystem($path)->path($backup_file);
		}
		
		public function backupData(&$path = null) {
			if(!is_string($path))
				$path = $this->application->packagemanager->generateBackupPath($this->id);
			
			$zip = new ZipArchive();
			if(($error = $zip->open($path, ZipArchive::CREATE)) !== true) {
				$this->application->error("Error parsing .astpbackup file: " . $error);
				return false;
			}
			
			$this->backupDataMeta($zip);
			$this->backupDataAddDirectory($zip, $this->filesystem("data"));
			
			$zip->close();
			return true;
		}
		
		protected function backupDataMeta(&$zip) {
			$zip->addEmptyDir("__backup_meta");
			$zip->addFromString("__backup_meta/package.json", json_encode(Array(
				"package" => Array(
					"id" => $this->id,
					"name" => $this->name,
					"version" => $this->definition->get([ "version" ]),
					"update_url" => $this->definition->get([ "update_url" ])
				)
			), JSON_PRETTY_PRINT));
			$zip->addFromString("__backup_meta/asteroid.json", json_encode(Array(
				"application" => Array(
					"host" => $this->application->configuration([ "host" ]),
					"path" => $this->application->configuration([ "path" ]),
					"url" => $this->application->generateURL("index"),
					"title" => $this->application->configuration([ "title" ]),
					"version" => $this->application->getVersion()
				)
			), JSON_PRETTY_PRINT));
			$zip->addFromString("__backup_meta/backup.json", json_encode(Array(
				"packagemanager" => Array("version" => 2),
				"backup" => Array("version" => 1)
			), JSON_PRETTY_PRINT));
			if($this->filesystem->file("composer.lock"))
				$zip->addFile("__backup_meta/composer.lock", $this->filesystem->path("composer.lock"));
		}
		
		protected function backupDataAddDirectory(&$zip, $filesystem, $current_path = "") {
			foreach($filesystem->contents() as $name) {
				if($filesystem->file($name) || $filesystem->link($name))
					$zip->addFromString(ltrim($current_path . "/", "/") . $name, $filesystem->read($name));
				elseif($filesystem->directory($name)) {
					$zip->addEmptyDir(ltrim($current_path . "/", "/") . $name);
					$this->backupDataAddDirectory($zip, $filesystem->filesystem($name), ltrim($current_path . "/", "/") . $name);
				}
			}
		}
		
		public function restoreFromBackup($path, $backup = true) {
			$zip = new ZipArchive();
			if(($error = $zip->open($path)) !== true) {
				$this->application->error("Error parsing .astpbackup file: " . $error);
				return false;
			}
			
			// Check backup format version
			$backup_json = $zip->getFromName("__backup_meta/backup.json");
			$backup = new Object(json_decode($backup_json));
			if($backup->get([ "backup", "version" ]) != 1)
				// Unknown backup format version
				return false;
			
			// Check package id
			$package_json = $zip->getFromName("__backup_meta/package.json");
			$package = new Object(json_decode($package_json));
			if($package->get([ "package", "id" ]) != $this->id)
				// Package id does not match
				return false;
			
			// Backup and delete the current data
			if($backup === true) $this->backupData();
			$this->filesystem("data")->delete("");
			
			// Extract the .zip file to this directory
			if($zip->extractTo($this->path("data")))
				return true;
			else return false;
		}
	}
	