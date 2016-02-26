<?php
	/* Asteroid
	 * class BasePackage
	 * 
	 * Provides an abstract class all package masters must extend from.
	 */
	namespace Asteroid\PackageManager;
	abstract class BasePackage {
		public $application = null;
		public $autoloader = null;
		public $configuration = null;
		
		public function __construct() {
			
		}
		
		final public function configuration($key) {
			return $this->configuration->get($key);
		}
	}
	