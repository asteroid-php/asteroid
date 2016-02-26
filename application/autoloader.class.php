<?php
	/* Asteroid
	 * Autoloader
	 */
	namespace Asteroid;
	class Autoloader {
		protected $directory = null;
		protected $extension = ".class.php";
		protected $namespace = "Asteroid\\";
		
		public function __construct($namespace = null, $directory = null, $extension = null) {
			if(is_string($directory)) {
				if(file_exists($directory) && is_dir($directory)) $this->directory = rtrim($directory, "/");
				else throw new Exception(__METHOD__ . "(): Directory must exist.");
			} else $this->directory = __DIR__;
			if(is_string($extension)) $this->extension = $extension;
			if(is_string($namespace)) $this->namespace = $namespace;
		}
		
		public function load($class) {
			if(!is_string($this->namespace)) $namespace = "";
			else $namespace = strtolower(trim(str_replace("\\", "/", $this->namespace), "/")) . "/";
			if($namespace == "/") $namespace = "";
			$class = preg_replace("/[^a-z0-9\/]/", "", strtolower(str_replace("\\", "/", $class)));
			if(substr($class, 0, strlen($namespace)) != $namespace) return false;
			$path = rtrim($this->directory, "/") . "/" . substr($class, strlen($namespace)) . $this->extension;
			if(file_exists($path) && is_file($path))
				require_once $path;
		}
		
		public function register() {
			spl_autoload_register(Array($this, "load"));
		}
		
		public function remove() {
			spl_autoload_unregister(Array($this, "load"));
		}
	}
	