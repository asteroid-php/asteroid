<?php
	/* Asteroid
	 * class BaseController
	 * 
	 * Provides an abstract class all controllers must extend from.
	 */
	namespace Asteroid;
	abstract class BaseController {
		public $application = null;
		public $default_action = null;
		public $rewrite_actions = Array();
		
		public function __construct() {
			
		}
		
		public function index() {
			throw new Exception(__METHOD__, "The controller " . get_class($this) . " did not assign an index action.");
		}
	}
	