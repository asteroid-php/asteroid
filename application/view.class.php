<?php
	/* Asteroid
	 * class View
	 * 
	 * Handles output.
	 */
	namespace Asteroid;
	class View {
		protected $application = null;
		protected $twig = null;
		protected $title = null;
		protected $view = null;
		
		// function __construct(): Creates a View object - normally only used by Application::view()
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
		}
		
		// function twig(): Returns the Twig object
		public function twig() {
			if(!is_object($this->twig) || (!$this->twig instanceof Twig)) $this->twig = new Twig($this->application);
			if(func_num_args() > 0) return call_user_func_array(Array($this->twig, func_get_arg(0)), array_slice(func_get_args(), 1));
			else return $this->twig;
		}
		
		// function deleteTwig(): For debugging: Delete the Twig object so print_r's aren't too big
		public function deleteTwig() {
			$this->twig = null;
		}
		
		// function view(): Returns the name of the current view for use in views
		public function view() {
			return $this->view;
		}
		
		// function title(): Gets/sets the title of the page
		public function title($title = null) {
			if(is_string($title)) $this->title = $title;
			else return $this->title;
			return $this;
		}
		
		// function render(): Tries to render a twig view, then tries to render a php view
		public function render($view, $data = Array(), $headers = null) {
			if(is_string($view) && file_exists($this->getViewsDirectory() . "/" . $view . ".php") && !file_exists($this->getViewsDirectory() . "/" . $view . ".twig"))
				$this->renderPHP($view, $data, $headers);
			else $this->renderTwig($view, $data, $headers);
		}
		
		// function renderTwig(): Parses a view as twig and renders it and the header / messages / footer views
		public function renderTwig($view, $data = Array(), $headers = null) {
			if(is_string($view) && !is_string($this->view))
				$this->view = $view;
			
			// Auto-detect headers by X-Fancybox header
			if(($headers !== true) && ($headers !== false)) {
				if(is_string($this->application->request()->header("X-Fancybox"))) $headers = false;
				else $headers = true;
			}
			if($headers) $this->renderHeader($data);
			$this->renderMessages($data);
			if(is_array($view)) foreach($view as $view2) echo $this->_renderTwig($view2, $data);
			else echo $this->_renderTwig($view, $data);
			if($headers) $this->renderFooter($data);
			exit("");
		}
		
		protected function _renderTwig($view, $data) {
			// Get the Twig object
			$twig = $this->twig();
			
			// Add data
			if(is_array($data)) {
				$data["title"] = $this->title;
				$data["application"] = $this->application;
				$data["config"] = $this->application->configuration();
				$data["request"] = $this->application->request();
				$data["baseurl"] = $this->application->getBaseURL();
				$data["server"] = $_SERVER;
				$data["environment"] = $_ENV;
			}
			
			return $twig->render($view, $data);
		}
		
		// function parse(): Parses a view a twig but returns it's contents - header, messages and footer are not parsed
		public function parse($view, $data = Array()) {
			// Get the Twig object
			$twig = $this->twig();
			
			// Add data
			if(is_array($data)) {
				$data["application"] = $this->application;
				$data["config"] = $this->application->configuration();
				$data["baseurl"] = $this->application->getBaseURL();
				$data["server"] = $_SERVER;
				$data["environment"] = $_ENV;
			}
			
			return $twig->render($view, $data);
		}
		
		// function renderPHP(): Includes a view (a .php file from the views directory) and the header / messages / footer views
		public function renderPHP($view, $data = Array(), $headers = null) {
			// Check if view exists
			if(!file_exists($this->getViewsDirectory() . "/{$view}.php")) {
				$this->renderString("<p><span class=\"ui-icon ui-icon-notice\"></span> There was an error loading this page: failed to locate view.</p>", $headers);
			}
			
			// Auto-detect headers by X-Fancybox header
			if(($headers !== true) && ($headers !== false)) {
				if(isset($_SERVER["HTTP_X_FANCYBOX"])) $headers = false;
				else $headers = true;
			}
			
			if($headers) $this->renderHeader($data);
			$this->renderMessages($data);
			if(is_array($view)) foreach($view as $view2)
				$this->_require($this->getViewsDirectory() . '/' . $view2 . '.php');
			else $this->_require($this->getViewsDirectory() . '/' . $view . '.php');
			if($headers) $this->renderFooter($data);
			exit("");
		}
		
		// function _require(): Includes a file
		protected function _require() {
			// Add data
			$title = $this->title;
			$application = $this->application;
			$config = $this->application->configuration();
			$request = $this->application->request();
			$baseurl = $this->application->getBaseURL();
			$server = $_SERVER;
			$environment = $_ENV;
			
			extract(func_get_arg(1), EXTR_SKIP);
			require func_get_arg(0);
		}
		
		// function renderString(): Outputs a string 
		public function renderString($view, $headers = null, $data = Array()) {
			// Auto-detect headers by X-Fancybox header
			if(($headers !== true) && ($headers !== false)) {
				if(is_string($this->application->request()->header("X-Fancybox"))) $headers = false;
				else $headers = true;
			}
			
			if(is_string($sv = $this->application->configuration([ "template_string" ]))) {
				if(is_array($view)) $view = implode("\n\t\t\t</div><div class=\"content\">\n\t\t\t\t", $view);
				$this->render($sv, array_merge($data, Array("text" => "<div class=\"content\">\n\t\t\t\t" . $view . "\n\t\t\t</div>")));
			} else {
				if($headers) $this->renderHeader($data);
				$this->renderMessages($data);
				if(is_array($view)) foreach($view as $view2)
					echo "<div class=\"content\">\n\t\t\t\t{$view2}\n\t\t\t</div>\n";
				else echo "<div class=\"content\">\n\t\t\t\t{$view}\n\t\t\t</div>\n";
				if($headers) $this->renderFooter($data);
			} exit("");
		}
		
		public function renderJSON($json, $prettyprint = true) {
			exit(json_encode($json, $prettyprint == true ? JSON_PRETTY_PRINT : 0));
		}
		
		// function getViewsDirectory(): Gets the views directory from the configuration
		protected function getViewsDirectory() {
			return rtrim($this->application->configuration([ "filesystem", "views_dir" ]), "/");
		}
		
		// function renderHeader(): Renders default views
		protected function renderHeader($data = Array()) {
			$view = $this->application->configuration([ "template_header" ]);
			if(is_string($view)) $this->render($view, $data);
		}
		
		protected function renderMessages($data = Array()) {
			$view = $this->application->configuration([ "template_messages" ]);
			if(is_string($view)) $this->render($view, $data);
		}
		
		protected function renderFooter($data = Array()) {
			$view = $this->application->configuration([ "template_footer" ]);
			if(is_string($view)) $this->render($view, $data);
		}
	}
	