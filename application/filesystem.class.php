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
			
			if(is_string($directory) && (substr($directory, 0, 1) == "/") && is_dir($directory))
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
		public function path($path, $real = true) {
			// Replace placeholders
			$path = preg_replace("/(^|\/)~(\/|$)/i", "/" . trim($this->root() . "/", "/"), $path);
			$path = preg_replace_callback("/%([a-zA-Z0-9-]+)%/", function($matches) use($this) {
				if(is_string($value = $this->application->configuration([ "filesystem", $matches[1] . "_dir" ])))
					return "/" . trim($value, "/");
				else return $matches[0];
			}, $path);
			
			if(substr($path, 0, 1) != "/") $path = $this->directory . "/" . trim($path, "/");
			if($real === true) return realpath($path);
			else return $path;
		}
		
		// function read(): Gets the content of a file
		public function read($path) {
			$path = $this->path($path);
			
			if(!$this->file($path) || !$this->readable($path))
				return null;
			
			$content = file_get_contents($path);
			if(is_string($content)) return $content;
			else return null;
		}
		
		// function readable(): Checks if a file is readable
		public function readable($path) {
			$path = $this->path($path);
			
			clearstatcache(true, $path);
			if(is_readable($path)) return true;
			else return false;
		}
		
		// function write(): Sets the content of a file - if $content is null the file will be deleted
		public function write($path, $content) {
			$path = $this->path($path, false);
			
			// Check if this is already a file or nothing already exists
			if(($exists = $this->exists($path)) && !$this->file($path))
				return false;
			if(!$exists || $this->writeable($path)) $writeable = true;
			else $writeable = false;
			
			if(is_string($content) && $writeable && file_put_contents($path, $content)) return true;
			elseif(($content === null) && $this->delete($path)) return true;
			else return false;
		}
		
		// function prepend(): Adds to the file from the start
		public function prepend($path, $content) {
			if(!is_string($content)) $content = "";
			
			if(is_string($old_content = $this->read($path)))
				$content = $content . $old_content;
			
			return $this->write($path, $content);
		}
		
		// function append(): Adds to the file from the end
		public function append($path, $content) {
			if(!is_string($content)) $content = "";
			
			if(is_string($old_content = $this->read($path)))
				$content = $old_content . $content;
			
			return $this->write($path, $content);
		}
		
		// function writeable(): Checks if a file is writeable
		public function writeable($path) {
			$path = $this->path($path);
			
			clearstatcache();
			if(is_writable($path)) return true;
			else return false;
		}
		
		// function target(): Gets the target of a link
		public function target($path) {
			if($this->link($path) && is_string($target = readlink($this->path($path, false))))
				return $target;
			else return null;
		}
		
		// function contents(): Lists the contents of a directory
		public function contents($path) {
			$path = $this->path($path);
			
			if(!$this->directory($path)) return Array();
			else return array_diff(scandir($path), Array("..", "."));
		}
		
		// function exists(): Checks if something exists at $path
		public function exists($path) {
			$path = $this->path($path);
			if(file_exists($path)) return true;
			else return false;
		}
		
		// function type(): Returns what $path is
		public function type($path) {
			if($this->file($path)) return "file";
			elseif($this->directory($path)) return "directory";
			else return "";
		}
		
		// function file(): Checks if $path is a file
		public function file($path) {
			if(is_file($this->path($path))) return true;
			else return false;
		}
		
		// function directory(): Checks if $path is a directory
		public function directory($path) {
			if(is_dir($this->path($path))) return true;
		}
		
		// function link(): Checks if $path is a link
		public function link($path) {
			if(is_link($this->path($path, false))) return true;
			else return false;
		}
		
		// function time(): Gets the time the file was last modified
		public function time($path) {
			$path = $this->path($path);
			
			if($this->file($path) && is_int($time = filemtime($path)))
				return $time;
			else return null;
		}
		
		// function delete(): Deletes a file / directory
		public function delete($path, $checkempty = true) {
			if($this->file($path)) return $this->deleteFile($path);
			elseif($this->directory($path)) return $this->deleteDirectory($path, $checkempty);
			else return false;
		}
		
		// function deleteFile(): Deletes a file
		public function deleteFile($path) {
			$path = $this->path($path);
			
			if($this->file($path) && unlink($path)) return true;
			else return false;
		}
		
		// function deleteDirectory(): Deletes a directory
		public function deleteDirectory($path, $checkempty = true) {
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
	