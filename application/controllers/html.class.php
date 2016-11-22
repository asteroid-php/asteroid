<?php
	/* Asteroid
	 * class HTML
	 * 
	 * HTML controller, allows creating pages with html.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	class HTML extends BaseController {
		public $default_action = "index";
		
		public function index() {
			$configuration = $this->application->configuration()->controller();
			$title = $configuration->get([ "pages", $this->application->getAction(), "title" ]);
			$content = $configuration->get([ "pages", $this->application->getAction(), "content" ]);
			
			if(is_string($content)) $content = Array($content);
			if(!is_array($content))
				// This page doesn't exist
				// Return false, triggering the "not found" page
				return "_404";
			
			return $this->application->view()->title(is_string($title) ? $title : "")->renderString(implode("\n</div><div class=\"content\">\n", $content));
		}
	}
	