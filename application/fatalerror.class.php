<?php
	/* Asteroid
	 * class FatalError
	 * 
	 * Provides a nice view for showing errors.
	 * Should only be called by the script that runs the application (public/default.php).
	 * Maybe FatalError::show() should just render a view?
	 */
	namespace Asteroid;
	class FatalError {
		protected $exception = null;
		public function __construct($exception) {
			$this->exception = $exception;
		}
		
		// function show(): Prints a html view of a fatal error
		public function show() {
			$error = $this->exception;
			$ca = func_num_args() >= 1;
			$ap = $ca ? func_get_arg(0) : null;
			
			$error_reporting = error_reporting();
			error_reporting(0);
			
			echo "<!DOCTYPE html>";
			echo "<html><head>";
			echo "<title>Fatal error</title>";
			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"/static/fatal-error/default.scss\" />";
			echo "<meta name=\"viewport\" content=\"width=device-width\" />";
			echo "</head><body>";
			echo "<div class=\"wrapper\">";
			echo "<div class=\"main\">";
			echo "<h2>Application error</h2>";
			echo "<p>A fatal error occured when trying to run the application.</p>";
			echo "<p>For more information, contact the site administrator at <a href=\"mailto:" . htmlentities($_SERVER["SERVER_ADMIN"]) . "\">" . htmlentities($_SERVER["SERVER_ADMIN"]) . "</a>.</p>";
			echo "</div>";
			echo "<div class=\"details\">";
			echo "<p>Message: " . htmlentities($error->getMessage()) . "</p>";
			if($error->getCode() != 0) echo "<p>Code: " . (int)$error->getCode() . "</p>";
			echo "<p>File: " . htmlentities($error->getFile()) . "</p>";
			echo "<p>Line: " . htmlentities($error->getLine()) . "</p>";
			if(!empty($backtrace = $error->getTrace())) {
				echo "<p>Backtrace:</p><ol>";
				foreach($backtrace as $key => $trace) {
					$trace = (object)$trace;
					echo "<li>";
					if(isset($trace->class)) {
						echo "<span class=\"class\">";
						echo htmlentities($trace->class);
						echo "</span>";
						echo htmlentities($trace->type);
					} echo "<span class=\"function\">";
					echo "<span class=\"function-name\">" . htmlentities($trace->function) . "</span>";
					echo "(<span class=\"arguments\">";
					foreach($trace->args as $k => $v) {
						if($k != 0) echo ", ";
						echo "<span class=\"argument\">";
						if($ca && ($ap === $v)) echo "<i>\$application</i>";
						elseif(is_object($v) && (substr(get_class($v), 0, 5) == "Twig_")) echo "<i>" . htmlentities(get_class($v)) . "</i>";
						else echo htmlentities(substr($s = var_export($v, true), 0, 2500)) . (strlen($s) > 2500 ? "..." : "");
						echo "</span>";
					} echo "</span>)</span>; in <span class=\"file\" title=\"";
					echo htmlentities($trace->file) . "\">";
					echo (strlen($trace->file) > 30 ? "... " : "") . htmlentities(trim(substr($trace->file, -30)));
					echo "</span> on line <span class=\"line\">" . (int)$trace->line . "</span>.";
					echo "</li>";
				} echo "</ol>";
			} echo "<p><br /></p>";
			echo "<details>";
			echo "<summary>More information</summary>";
			echo "<pre>" . htmlentities(preg_replace(array_keys($strrs = Array(
				"/(Array|Object)\n([ ]*)\(/" => "$1 (",
				"/\n\n/" => "\n",
				"/\(/" => "{",
				"/\)/" => "}",
				"/\[([^\]]*)\] =>/" => "\"$1\" =>",
				"/\"([0-9]*)\" =>/" => "$1 =>"
			)), array_values($strrs), print_r($error, true))) . "</pre>";
			echo "</details></div>";
			echo "</div>";
			echo "</body></html>\n";
			
			error_reporting($error_reporting);
			exit();
		}
	}
	