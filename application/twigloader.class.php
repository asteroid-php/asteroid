<?php
	/* Asteroid
	 * class TwigLoader
	 * 
	 * Loads views from the views directory for twig
	 */
	namespace Asteroid;
	use Twig_Environment;
	use Twig_LoaderInterface;
	class TwigLoader implements Twig_LoaderInterface {
		protected $application = null;
		
		// function __construct(): Creates a new TwigLoader object
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function getFilename(): Gets the filename of a view
		protected function getFilename($name) {
			$views_dir = rtrim($this->application->configuration([ "filesystem", "views_dir" ]), "/");
			$extr_dirs = $this->application->configuration([ "filesystem", "view_dirs" ]);
			
			// Search main directory
			if(file_exists($view_filename = $views_dir . "/" . $name . ".twig"))
				return $view_filename;
			
			// Search directories
			foreach($extr_dirs as $alias => $directory) {
				$alias = trim($alias, "/");
				$directory = rtrim($directory);
				if(substr($name . "/", 0, strlen($alias . "/")) == $alias . "/") {
					$view_filename = $directory . "/" . substr($name, strlen($alias . "/")) . ".twig";
					if(file_exists($view_filename))
						return $view_filename;
				}
			}
			
			// View doesn't exist
			return false;
		}
		
		// function getSource(): Returns the source (contents) of a view
		public function getSource($name) {
			foreach($this->application->events()->triggerR("get_view", Array($name)) as $return) {
				if(is_string($return))
					return $return;
				elseif($return === false)
					return false;
			}
			
			if(is_string($view_filename = $this->getFilename($name)))
				return $this->application->filesystem()->read($view_filename);
			else return false;
		}
		
		// function getCacheKey(): Returns the cache key of a view
		public function getCacheKey($name) {
			return $name;
		}
		
		// function isFresh(): Checks if a view has been modified since compilation
		public function isFresh($name, $time) {
			$views_dir = rtrim($this->application->configuration([ "filesystem", "views_dir" ]), "/");
			if(filemtime($views_dir . "/" . $name . ".twig") <= $time) return true;
			else return false;
		}
	}
	