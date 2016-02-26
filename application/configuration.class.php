<?php
	/* Asteroid
	 * class Configuration
	 */
	namespace Asteroid;
	use stdClass;
	class Configuration extends Object {
		protected $application = null;
		
		// function __construct(): Creates a configuration object with default configuration
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			$this->title = preg_replace("/^(www\.)/i", "", $_SERVER["HTTP_HOST"]);
			$this->description = "A website made with Asteroid";
			$this->hostname = preg_replace("/^(www\.)/i", "", $_SERVER["HTTP_HOST"]); // Your domain name, without www
			$this->hostnamewww = false; // True if www. should be prepended to the hostname
			$this->path = "/";
			$this->ssl = (isset($_SERVER["HTTPS"]) && (strtoupper($_SERVER["HTTPS"]) != "OFF")) ||
				(isset($_SERVER["REQUEST_SCHEME"]) && (strtoupper($_SERVER["REQUEST_SCHEME"]) == "HTTPS")) ||
				(isset($_SERVER["SERVER_PORT"]) && ($_SERVER["SERVER_PORT"] == 443)) ||
				(isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && (strtoupper($_SERVER["HTTP_X_FORWARDED_PROTO"]) == "HTTPS")) ||
				(isset($_SERVER["HTTP_X_FORWARDED_PORT"]) && ($_SERVER["HTTP_X_FORWARDED_PORT"] == 443))
			? true : false; // This should be the current SSL status,
			$this->https = $this->ssl; // This should be true if SSL should be used
			$this->errors = true;
			
			// Default views, for php views these should all be the name of a view
			// For twig views these (except extends, index and string) should be null (in twig views should extend from a master view)
			$this->template_extends = "theme-default/default"; // The twig file views that extend from "default" extend from this
			$this->template_index = "index";
			$this->template_string = "string";
			$this->template_header = null;
			$this->template_messages = null;
			$this->template_footer = null;
			
			// Template configuration, these options may not apply to third-party templates
			$this->template_background = __DIR__ . "/static/images/background.png"; // Can be a file on this server or a remote url
			$this->template_logo = "https://samuelthomas.ml/index/logo"; // Set to null to remove the title image - if this is null set template_show_title to true
			$this->template_icon = "https://samuelthomas.ml/index/icon"; // Favourites icon (favicon) for this site
			$this->template_apple_touch_icon = "https://samuelthomas.ml/index/apple-touch-icon"; // Apple Touch Icon for this site - will be displayed on the home screen when added to the home screen
			$this->template_show_title = true;
			$this->template_show_description = false;
			$this->footer_text = "Copyright &copy; " . date("Y") . " <i>" . ltrim($_SERVER["HTTP_HOST"], "www.") . "</i>";
			$this->template_page = true;
			$this->template_html_class = "";
			$this->template_css_file = "/static/themes/default/scss/default.scss";
			$this->template_css_additional = Array(
				// To include an additional css file: "{path_relative_to_the_current_page}" => "file"
				// To include an additional css file: "{path_relative_to_the_current_page}"
				// To include inline css: "{css}" => "inline"
			);
			
			// Template features, these options may not apply to third-party templates
			$this->template_fancybuttons = true;
			$this->template_ajaxify = true;
			$this->template_autoupdate = false;
			$this->template_emoji = true;
			
			// Navigation menu items:
			// If this is a function it will be called to get navigation items, allows use of variables not defined yet
			$this->navigation = Array($this, "getNavigation");
			
			// Controllers configuration:
			// Array of enabled controllers
			// Default url is "index", to set a default controller use "index" => "some_controller"
			$this->controllers = Array(
				"index" => "Asteroid\\Controllers\\Index",
				"error" => "Asteroid\\Controllers\\Error",
				"auth" => "Asteroid\\Controllers\\Authentication",
				"account" => "Asteroid\\Controllers\\Account",
				"about" => "Asteroid\\Controllers\\About"
			);
			
			// Controllers configuration (of controllers):
			$this->controllers_configuration = Array();
			
			// Models configuration:
			// Array of enabled models - example: "{model}" => "{class}"
			// Note: models in the Asteroid\Models namespace do not need to be added here to work properly
			// Models will try to load from this array then the models namespace
			$this->models = Array(
				"Object" => "Asteroid\\DatabaseObjects\\Model"
			);
			
			// Libraries configuration:
			// Array of enabled libraries - example: "{class}"
			// Note: libraries always need to be added here, even if they are in the Asteroid\Libraries namespace
			// To "enable" a library just add it's class
			$this->libraries = Array("Asteroid\\Libraries\\Asteroid");
			
			// Filesystem configuration:
			$this->filesystem = new stdClass();
			$this->filesystem->root_dir = $root_dir = dirname(__DIR__);
			$this->filesystem->asteroid_dir = $root_dir;
			$this->filesystem->application_dir = $root_dir . "/application";
			$this->filesystem->views_dir = $root_dir . "/templates";
			$this->filesystem->view_dirs = Array(
				"packagemanager" => $root_dir . "/templates/packagemanager",
				"theme-default" => $root_dir . "/templates/theme-default"
			);
			$this->filesystem->cache_dir = $root_dir . "/cache";
			
			// Captcha configuration:
			$this->captcha = new stdClass();
			$this->captcha->width = 250;
			$this->captcha->height = 70;
			$this->captcha->length = 6;
			$this->captcha->chars = "ABCDEFGHJKLMNPRTUVWXYZ2346789";
			$this->captcha->font_path = $root_dir . "/../../public/static/fonts/captcha.ttf";
			
			// Database configuration:
			// Map of databases - example: "{alias}" => (object)Array("database" => "{dbname}", "hostname" => "{hostname}", "username" => "{username}", "password" => "{password}")
			$this->databases = new stdClass();
			$this->databases->default = new stdClass();
			$this->databases->default->database = "database";
			$this->databases->default->hostname = "127.0.0.1";
			$this->databases->default->username = "root";
			$this->databases->default->password = "";
			
			// Session configuration:
			$this->session = new stdClass();
			$this->session->handler = 1; // 1: file in session->file_directory, 2: table session->database_table in database session->database_name
			$this->session->cookie_name = "session";
			$this->session->cookie_host = "." . ltrim($this->hostname, "www.");
			$this->session->database_name = "default";
			$this->session->database_table = "sessions";
			$this->session->file_directory = $root_dir . "/sessions";
			$this->session->print_excludes = Array("captcha");
			
			// Authentication configuration:
			// To disable authentication set auth->handler to null - not yet fully supported
			// To use Samuel Thomas OAuth go to https://samuelthomas.ml/developer/clients
			$this->auth = new stdClass();
			$this->auth->handler = "Asteroid\\Authentication";
			$this->auth->users = null; // Array of users allowed to access the website; set to null to allow anyone to access the website; set to an empty array to allow anyone to access the website, but only if they are signed in
			$this->auth->admins = Array("samuelthomas2774", "admin"); // An array of users who will be allowed to access administration areas of the website
			$this->auth->users_database = "default";
			$this->auth->users_table = "users";
			
			// OAuth Authentication handler configuration:
			// -- Custom OAuth Classes are not yet supported
			// See https://github.com/samuelthomas2774/oauth-client for information about creating a custom OAuth class
			// Then put the filename and class name here - you can also use one of the included OAuth classes like Facebook ("class.facebook.php", "OAuthFacebook")
			// To use Samuel Thomas OAuth go to http://samuelthomas.ml/developer/clients
			$this->oauth = new stdClass();
			$this->oauth->library_class = "OAuthST";
			$this->oauth->client_id = "";
			$this->oauth->client_secret = "";
			
			// Mail library configuration:
			$this->mail = new stdClass();
			$this->mail->smtp = new stdClass();
			$this->mail->smtp->hostname = "localhost";
			$this->mail->smtp->port = 25;
			$this->mail->smtp->secure = 0; // 0 No SSL / 1 SSL / 2 TLS
			$this->mail->smtp->username = null; // This will attempt to connect to the SMTP server without a username and password - if your SMTP server requires authentication, change this to the username and password
			$this->mail->smtp->password = null;
			//$config->mail->smtp = null; // Uncomment to use the mail() function instead of an SMTP server
			$this->mail->from = "support@example.com"; // The email address you want all emails to come from
			$this->mail->name = "Example.com Support"; // The user-friendly name you want all emails to come from
		}
		
		// function getNavigation(): Returns the default navigation object
		public function getNavigation() {
			$user = $this->application->authentication()->user();
			$controller = $this->application->getControllerURL();
			$action = $this->application->getAction();
			return Array(
				(object)Array("icon" => "ui-icon-home", "href" => $this->application->generateURL("index"), "label" => "Home", "active" => ($controller == "index") && in_array($action, Array("index", "session", "cookies")), "items" => Array(
					Array("href" => $this->application->generateURL("index", "markdown"), "label" => "Markdown", "icon" => "ui-icon-document"),
					$user->admin() ? Array("href" => $this->application->generateURL("index", "session"), "label" => "Session", "icon" => "ui-icon-document") : null,
					$user->admin() ? Array("href" => $this->application->generateURL("index", "cookies"), "label" => "Cookies", "icon" => "ui-icon-document") : null
				)),
				Array("href" => $this->application->generateURL("page2"), "label" => "Page 2 [Markdown]", "icon" => "ui-icon-link", "active" => $controller == "page2", "items" => Array(
					Array("href" => $this->application->generateURL("page2", "page3"), "label" => "Page 3", "icon" => "ui-icon-extlink"),
					Array("href" => $this->application->generateURL("page2", "page4"), "label" => "Page 4", "icon" => "ui-icon-extlink")
				)),
				Array("href" => $this->application->generateURL("page5"), "label" => "Page 5 [HTML]", "icon" => "ui-icon-folder-collapsed", "active" => $controller == "page5", "items" => Array(
					Array("href" => $this->application->generateURL("page5", "page6"), "label" => "Page 6", "icon" => "ui-icon-extlink")
				)),
				Array("href" => $this->application->generateURL("about"), "label" => "About", "icon" => "ui-icon-info", "active" => $controller == "about", "items" => Array(
					Array("href" => $this->application->generateURL("about", "contact"), "label" => "Contact", "icon" => "ui-icon-contact")
				))
			);
		}
		
		// function controller(): Sets/gets configuration for a controller
		public function controller($url = null, $controller = null, $configuration = null) {
			if($url === null) {
				// Get the configuration for the current controller
				$url = $this->application->getControllerURL();
				$c = $this->get([ "controllers_configuration", $url ]);
				
				if(is_array($c) || is_object($c))
					return new Object($c);
				else return $c;
			}
			
			if(($configuration === null) && (is_object($controller) || is_array($controller))) {
				$configuration = $controller;
				$controller = null;
			}
			
			// Make sure this url points to this controller
			if(is_string($controller))
				$this->set([ "controllers", $url ], $controller);
			
			if(is_array($configuration)) $configuration = (object)$configuration;
			if(is_object($configuration)) {
				// Set options for a controller
				foreach($configuration as $key => $value)
					$this->set([ "controllers_configuration", $url, $key ], $value);
			} else {
				$c = $this->get([ "controllers_configuration", $url ]);
				if(is_array($c) || is_object($c))
					return new Object($c);
				else return $c;
			}
		}
		
		// function database(): Modifies the configuration for a database
		public function database($name, $options = null, $value = null) {
			if(is_object($options) || is_array($options))
				// Sets configuration values
				foreach($options as $key => $value)
					$this->set([ "databases", $name, $key ], $value);
			elseif(is_string($options) && (array_key_exists(2, func_get_args())))
				// Sets a configuration value
				$this->set([ "databases", $name, $options ], $value);
			elseif(is_string($options))
				// Get a configuration value
				$this->get([ "databases", $name, $options ]);
			else
				// Gets all configuration values
				$this->get([ "databases", $name ]);
		}
		
		// Configuration functions
		protected function cfgetset($option, $args, $type = null) {
			if(array_key_exists(0, $args) && (!is_string($type) || (strtolower(gettype($args[0])) == $type)))
				$this->set($option, $args[0]);
			else return $this->get($option);
		}
		
		protected function cfgetadd($option, $args, $type = null) {
			if(array_key_exists(0, $args) && (!is_string($type) || (strtolower(gettype($args[0])) == $type))) {
				$array = $this->get($option);
				$array[] = $args[0];
				$this->set($option, $array);
			} else return $this->get($option);
		}
		
		protected function cfgetseta($option, $args, $type = null) {
			if(array_key_exists(0, $args) && array_key_exists(1, $args) && (!is_string($type) || (strtolower(gettype($args[1])) == $type))) {
				$array = $this->get($option);
				$array[$args[0]] = $args[1];
				$this->set($option, $array);
			} else return $this->get($option);
		}
		
		public function title() { return $this->cfgetset([ "title" ], func_get_args(), "string"); }
		public function description() { return $this->cfgetset([ "description" ], func_get_args(), "string"); }
		public function hostname() { return $this->cfgetset([ "hostname" ], func_get_args(), "string"); }
		public function hostnamewww() { return $this->cfgetset([ "hostnamewww" ], func_get_args(), "boolean"); }
		public function path() { return $this->cfgetset([ "path" ], func_get_args(), "string"); }
		public function httpsstatus() { return $this->cfgetset([ "ssl" ], func_get_args(), "boolean"); }
		public function httpsexpected() { return $this->cfgetset([ "https" ], func_get_args(), "boolean"); }
		public function errors() { return $this->cfgetset([ "erros" ], func_get_args(), "boolean"); }
		
		public function template_index() { return $this->cfgetset([ "template_index" ], func_get_args()); }
		public function template_string() { return $this->cfgetset([ "template_string" ], func_get_args()); }
		public function template_header() { return $this->cfgetset([ "template_header" ], func_get_args()); }
		public function template_messages() { return $this->cfgetset([ "template_messages" ], func_get_args()); }
		public function template_footer() { return $this->cfgetset([ "template_footer" ], func_get_args()); }
		
		public function template_background() { return $this->cfgetset([ "template_background" ], func_get_args(), "string"); }
		public function template_logo() { return $this->cfgetset([ "template_logo" ], func_get_args(), "string"); }
		public function template_icon() { return $this->cfgetset([ "template_icon" ], func_get_args(), "string"); }
		public function template_apple_touch_icon() { return $this->cfgetset([ "template_apple_touch_icon" ], func_get_args(), "string"); }
		public function template_show_title() { return $this->cfgetset([ "template_show_title" ], func_get_args(), "boolean"); }
		public function template_show_description() { return $this->cfgetset([ "template_show_description" ], func_get_args(), "boolean"); }
		public function footer_text() { return $this->cfgetset([ "footer_text" ], func_get_args(), "string"); }
		public function template_page() { return $this->cfgetset([ "template_page" ], func_get_args(), "boolean"); }
		public function template_html_class() { return $this->cfgetset([ "template_html_class" ], func_get_args(), "string"); }
		public function template_css_file() { return $this->cfgetset([ "template_css_file" ], func_get_args(), "string"); }
		public function template_css_additional() { return $this->cfgetset([ "template_css_additional" ], func_get_args(), "string"); }
		public function addAdditionalCSS() { return $this->cfgetseta([ "template_css_additional" ], array_reverse(func_get_args()), "string"); }
		
		public function template_fancybuttons() { return $this->cfgetset([ "template_fancybuttons" ], func_get_args(), "boolean"); }
		public function template_ajaxify() { return $this->cfgetset([ "template_ajaxify" ], func_get_args(), "boolean"); }
		public function template_autoupdate() { return $this->cfgetset([ "template_autoupdate" ], func_get_args(), "boolean"); }
		public function template_emoji() { return $this->cfgetset([ "template_emoji" ], func_get_args(), "boolean"); }
		
		public function navigation() { return $this->cfgetset([ "navigation" ], func_get_args()); }
		
		public function filesystem_root() { return $this->cfgetset([ "filesystem", "root_dir" ], func_get_args(), "string"); }
		public function filesystem_views_dir() { return $this->cfgetset([ "filesystem", "views_dir" ], func_get_args(), "string"); }
		public function filesystem_view_dirs() { return $this->cfgetset([ "filesystem", "view_dirs" ], func_get_args(), "array"); }
		public function addViewDirectory() { return $this->cfgetseta([ "filesystem", "view_dirs" ], func_get_args(), "string"); }
		public function filesystem_cache_dir() { return $this->cfgetset([ "filesystem", "cache_dir" ], func_get_args(), "string"); }
	}
	