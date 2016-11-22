<?php
	/* Asteroid
	 * class PackageDefinition
	 * 
	 * Reads / writes to the packages.json file and each package's configuration.json file.
	 */
	namespace Asteroid\PackageManager2;
	use Asteroid\Autoloader;
	use Asteroid\Object;
	use Asteroid\Exception;
	class PackageDefinition {
		protected $application = null;
		protected $data = null;
		
		public function __construct($application, $data = null, $directory = null) {
			$this->application = $application;
			$this->data = new Object(Array(
				"name" => null,
				"description" => null,
				"short_description" => null,
				"id" => null,
				"master" => null,
				"setup_controller" => null,
				"commandline_setup_controller" => null,
				"version" => null,
				"update_url" => null,
				"directory" => null,
				"autoloader_prefix" => null,
				"autoloader_extension" => ".class.php",
				"autoloader_directory" => "Contents",
				"templates_directory" => "Views",
				"data_directory" => "Data",
				"define" => null
			), self::getData($data), Array(
				"directory" => $directory
			));
		}
		
		protected static function getData($data) {
			if(is_object($data) && isset($data->package))
				return $data->package;
			elseif(is_array($data) && isset($data["package"]))
				return $data["package"];
			else return $data;
		}
		
		public static function validatePackageJSON($package) {
			if(!is_string($package->get([ "name" ])))
				return false;
			elseif(!is_string($package->get([ "description" ])))
				return false;
			elseif(!is_string($package->get([ "id" ])))
				return false;
			elseif(!is_string($package->get([ "master" ])))
				// We have to wait until the next page load to check if the master exists
				return false;
			else return true;
		}
		
		public function get($name) {
			return $this->data->get($name);
		}
		
		public function __get($name) {
			return $this->data->get($name);
		}
		
		public function __isset($name) {
			return $this->data->check($name);
		}
		
		public function set($name, $value) {
			$this->data->set($name, $value);
		}
		
		public function __set($name, $value) {
			$this->data->set([ $name ], $value);
		}
		
		public function add($name, $value) {
			call_user_func_array(Array($this->data, "add"), func_get_args());
		}
	}
	