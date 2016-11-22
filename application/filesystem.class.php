<?php
	/* Asteroid
	 * class Filesystem
	 * 
	 * Provides access to the filesystem php runs on.
	 */
	namespace Asteroid;
	class Filesystem {
		protected $application = null;
		protected $directory = null;
		
		// function __construct(): Creates a new filesystem object
		public function __construct($application, $directory = null, $root_dir = null) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			if(!is_string($root_dir))
				$root_dir = $this->root();
			
			if(is_string($directory) && (substr($directory, 0, 1) == "/") && !$this->directory($directory) && !$this->createDirectory($directory))
				throw new Exception(__METHOD__, "Failed to create directory {$directory}.");
			
			if(is_string($directory) && (substr($directory, 0, 1) == "/") && $this->directory($directory))
				$this->directory = "/" . trim(realpath($directory), "/");
			elseif(is_string($root_dir = $this->root()) && (substr($root_dir, 0, 1) == "/"))
				$this->directory = "/" . trim(trim($root_dir, "/") . "/" . trim($directory, "/"), "/");
			else $this->directory = "/";
		}
		
		// function root(): Gets the root directory
		public function root() {
			$root_dir = $this->application->configuration([ "filesystem", "root_dir" ]);
			if(is_string($root_dir) && (substr($root_dir, 0, 1) == "/")) return "/" . trim($root_dir, "/");
			else return "/";
		}
		
		// function path(): Gets the real path of a file/directory
		public function path($path = "", $real = true) {
			foreach($this->application->events()->triggerR("filesystem_get_path", Array($path, $real, $this->directory, $this)) as $return)
				if(is_string($return)) return $return;
			
			// Replace placeholders
			$path = preg_replace("/(^|\/)~(\/|$)/i", "/" . trim($this->root() . "/", "/"), $path);
			$application = $this->application;
			$path = preg_replace_callback("/%([a-zA-Z0-9-]+)%/", function($matches) use($application) {
				if(is_string($value = $application->configuration([ "filesystem", $matches[1] . "_dir" ])))
					return "/" . trim($value, "/");
				else return $matches[0];
			}, $path);
			
			if(substr($path, 0, 1) != "/") $path = $this->directory . "/" . trim($path, "/");
			if(($real === true) && file_exists($path)) return realpath($path);
			else return $path;
		}
		
		// function filesystem(): Returns a new filesystem object with the path relative to this object
		public function filesystem($path) {
			return new self($this->application, $this->path($path));
		}
		
		// function handle(): Returns a file handle
		public function handle($path, $mode = null) {
			foreach($this->application->events()->triggerR("filesystem_get_handle", Array($path, $mode, $this->directory, $this)) as $return)
				if(is_resource($return) && (get_resource_type($return) == "stream")) return $return;
				elseif($return === false) return null;
			
			$path = $this->path($path, false);
			return fopen($path, $mode);
		}
		
		// function read(): Gets the content of a file
		public function read($path) {
			foreach($this->application->events()->triggerR("filesystem_read", Array($path, $this->directory, $this)) as $return)
				if(is_string($return)) return $return;
				elseif($return === false) return null;
			
			$path = $this->path($path);
			
			if(!$this->file($path) || !$this->readable($path))
				return null;
			
			$content = file_get_contents($path);
			if(is_string($content)) return $content;
			else return null;
		}
		
		// function readable(): Checks if a file is readable
		public function readable($path = "") {
			foreach($this->application->events()->triggerR("filesystem_is_readable", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			
			clearstatcache(true, $path);
			if(is_readable($path)) return true;
			else return false;
		}
		
		// function write(): Sets the content of a file - if $content is null the file will be deleted
		public function write($path, $content) {
			foreach($this->application->events()->triggerR("filesystem_write", Array($path, $content, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path, false);
			
			if(!$this->createFileDirectory($path))
				return false;
			
			// Check if this is already a file or nothing already exists
			if(($exists = $this->exists($path)) && !$this->file($path))
				return false;
			if(!$exists || $this->writeable($path)) $writeable = true;
			else $writeable = false;
			
			if(is_string($content) && $writeable && file_put_contents($path, $content)) return true;
			elseif(($content === null) && $this->delete($path)) return true;
			else return false;
		}
		
		public function createFileDirectory($path) {
			$parts = explode("/", $directory);
	        $file = array_pop($parts);
			return $this->createDirectory(implode("/", $parts));
		}
		
		public function createDirectory($path) {
			foreach($this->application->events()->triggerR("filesystem_create_directory", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$directory = $this->path($path, false);
			
			$parts = explode("/", $directory);
	        $directory = "";
	        foreach($parts as $part)
	            if(!is_dir($directory .= "/{$part}") && !mkdir($directory))
					return false;
			
			return true;
		}
		
		// function prepend(): Adds to the file from the start
		public function prepend($path, $content) {
			foreach($this->application->events()->triggerR("filesystem_prepend", Array($path, $content, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if(!is_string($content)) $content = "";
			
			if(is_string($old_content = $this->read($path)))
				$content = $content . $old_content;
			
			return $this->write($path, $content);
		}
		
		// function append(): Adds to the file from the end
		public function append($path, $content) {
			foreach($this->application->events()->triggerR("filesystem_append", Array($path, $content, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if(!is_string($content)) $content = "";
			
			if(is_string($old_content = $this->read($path)))
				$content = $old_content . $content;
			
			return $this->write($path, $content);
		}
		
		// function writeable(): Checks if a file is writeable
		public function writeable($path = "") {
			foreach($this->application->events()->triggerR("filesystem_is_writeable", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			
			clearstatcache();
			if(is_writable($path)) return true;
			else return false;
		}
		
		// function target(): Gets the target of a link
		public function target($path = "") {
			foreach($this->application->events()->triggerR("filesystem_get_target", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if($this->link($path) && is_string($target = readlink($this->path($path, false))))
				return $target;
			else return null;
		}
		
		// function contents(): Lists the contents of a directory
		public function contents($path = "") {
			foreach($this->application->events()->triggerR("filesystem_get_contents", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			
			if(!$this->directory($path)) return Array();
			else return array_diff(scandir($path), Array("..", "."));
		}
		
		// function exists(): Checks if something exists at $path
		public function exists($path = "") {
			foreach($this->application->events()->triggerR("filesystem_check_exists", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			if(file_exists($path)) return true;
			else return false;
		}
		
		// function type(): Returns what $path is
		public function type($path = "") {
			foreach($this->application->events()->triggerR("filesystem_get_type", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if($this->file($path)) return "file";
			elseif($this->directory($path)) return "directory";
			else return "";
		}
		
		// function file(): Checks if $path is a file
		public function file($path = "") {
			foreach($this->application->events()->triggerR("filesystem_is_file", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if(is_file($this->path($path))) return true;
			else return false;
		}
		
		// function directory(): Checks if $path is a directory
		public function directory($path = "") {
			foreach($this->application->events()->triggerR("filesystem_is_directory", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if(is_dir($this->path($path))) return true;
			else return false;
		}
		
		// function link(): Checks if $path is a link
		public function link($path = "") {
			foreach($this->application->events()->triggerR("filesystem_is_link", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if(is_link($this->path($path, false))) return true;
			else return false;
		}
		
		// function time(): Gets the time the file was last modified
		public function time($path = "") {
			foreach($this->application->events()->triggerR("filesystem_last_modified", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			
			if($this->file($path) && is_int($time = filemtime($path)))
				return $time;
			else return null;
		}
		
		// function delete(): Deletes a file / directory
		public function delete($path = "", $checkempty = true) {
			foreach($this->application->events()->triggerR("filesystem_delete", Array($path, $checkempty, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			if($this->file($path) || $this->link($path)) return $this->deleteFile($path);
			elseif($this->directory($path)) return $this->deleteDirectory($path, $checkempty);
			else return false;
		}
		
		// function deleteFile(): Deletes a file
		public function deleteFile($path) {
			foreach($this->application->events()->triggerR("filesystem_delete_file", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			
			if($this->file($path) && unlink($path)) return true;
			else return false;
		}
		
		// function deleteDirectory(): Deletes a directory
		public function deleteDirectory($path = "", $checkempty = true) {
			foreach($this->application->events()->triggerR($checkempty === true ? "filesystem_delete_directory_recursive" : "filesystem_delete_directory", Array($path, $this->directory, $this)) as $return)
				if(is_bool($return)) return $return;
			
			$path = $this->path($path);
			
			// Check the directory is empty, if not empty the directory
			if($checkempty === true)
				foreach($this->contents($path) as $name)
					if(!$this->delete($path . "/" . $name))
						return false;
			
			if($this->directory($path) && rmdir($path)) return true;
			else return false;
		}
	}
	