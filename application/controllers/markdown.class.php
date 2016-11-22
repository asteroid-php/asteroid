<?php
	/* Asteroid
	 * class Markdown
	 * 
	 * Markdown controller, allows creating pages with markdown.
	 */
	namespace Asteroid\Controllers;
	use Asteroid\BaseController;
	use Parsedown;
	class Markdown extends BaseController {
		public $default_action = "index";
		
		public function index() {
			$configuration = $this->application->configuration()->controller();
			$directory = $configuration->get([ "directory" ]);
			
			$title = $configuration->get([ "pages", $this->application->getAction(), "title" ]);
			$content = $configuration->get([ "pages", $this->application->getAction(), "content" ]);
			$breaks = $configuration->get([ "pages", $this->application->getAction(), "line_breaks" ]);
			$data = $configuration->get([ "pages", $this->application->getAction(), "data" ]);
			
			if(is_string($content)) $content = Array($content);
			if(!is_array($content) && is_string($directory) && is_dir($directory)) {
				$directory = rtrim($directory, "/");
				$path = $directory . "/" . trim(implode("/", array_merge(Array($this->application->getAction()), $this->application->getActionInfo())), "/") . ".md";
				
				$file = $this->application->filesystem()->read($path);
				if(!is_string($file)) return "_404";
				
				if(preg_match("/^((([^:\n]*): *([^\n]*)\n)+\n)?((.|\n)*)$/i", $file, $matches)) {
					preg_match_all("/([^:\n]*): *([^\n]*)\n/i", $matches[1], $vars_, PREG_SET_ORDER);
					$vars = Array();
					foreach($vars_ as $key => $value)
						$vars[str_replace("  ", " ", strtolower($value[1]))] = $value[2];
					
					if(isset($vars["title"]) && is_string($vars["title"]))
						$title = $vars["title"];
					if(isset($vars["line breaks"]))
						$breaks = (bool)$vars["line breaks"];
					if(isset($vars["boundary"]))
						$boundary = $vars["boundary"];
					if(isset($vars["data"]))
						$data = json_decode($vars["data"], true);
					
					$content = isset($boundary) ? explode($boundary, $matches[5]) : Array($matches[5]);
				} else $content = Array($file);
			} elseif(!is_array($content))
				// This page doesn't exist
				// Return false, triggering the "not found" page
				return "_404";
			
			$parsedown = new Parsedown();
			if($breaks === true) $parsedown->setBreaksEnabled(true);
			foreach($content as $key => $value)
				if(is_string($content[$key]))
					$content[$key] = $parsedown->text($content[$key]);
			
			return $this->application->view()->title(is_string($title) ? $title : "")->renderString("<div class=\"markdown\">" . implode("\n</div></div><div class=\"content\"><div class=\"markdown\">\n", $content) . "</div>", is_array($data) ? $data : Array());
		}
	}
	