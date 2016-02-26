<?php
	/* Asteroid
	 * class HTML
	 * 
	 * Filters html tags and escapes html.
	 */
	namespace Asteroid;
	class HTML {
		protected $text = null;
		
		public function __construct($text) {
			$this->text = $text;
		}
		
		// function escape(): Encodes a variable for being output as html
		public function escape() {
			$var = $this->text;
			if($var === null) return "null";
			elseif($var === true) return "true";
			elseif($var === false) return "false";
			elseif(is_string($var)) return htmlentities($var, ENT_QUOTES, "UTF-8");
			elseif(is_int($var) || is_float($var)) return (string)$var;
			elseif(is_array($var)) return "[Array " . count($var) . "]";
			elseif(is_object($var)) return "[Object " . htmlentities(get_class($var)) . " " . count((array)$var) . "]";
		}
		
		// function filter(): Removes / escapes bad attributes and tags
		protected $tags = Array("script", "style");
		protected $attrs = Array("on[^=]*", "style");
		public function filter($var, $tags = null, $attrs = null) {
			if($var === null) return "null";
			elseif($var === true) return "true";
			elseif($var === false) return "false";
			elseif(is_string($var)) {
				if(is_array($tags)) self::$tags = $tags;
				else self::$tags = Array("script", "style");
				if(is_array($attrs)) self::$attrs = $attrs;
				elseif(is_string($attrs)) self::$attrs = explode("|", $attrs);
				else self::$attrs = Array("on[^=]*", "style");
				return preg_replace_callback("/<([^ >]*)?([^\">]*\"[^\"]*\")*>/i", "HTML::filterCallback", $var);
			} elseif(is_int($var) || is_float($var)) return (string)$var;
			elseif(is_array($var)) return "[Array " . count($var) . "]";
			elseif(is_object($var)) return "[Object " . htmlentities(get_class($var)) . " " . count($var) . "]";
		}
		
		protected static function filterCallback($matches) {
			list($match, $tag) = $matches;
			$attributes = isset($matches[2]) ? $matches[2] : "";
			
			// Check tag
			foreach(self::$tags as $key => $value) {
				if(is_string($key)) {
					$a = $value;
					$bad_tag = $key;
				} else {
					$a = "filter";
					$bad_tag = $value;
				}
				
				if(($tag == $bad_tag) || ($tag == "/" . $bad_tag)) {
					if(($a == "k") || ($a == "keep")) {}
					elseif(($a == "r") || ($a == "remove")) return "";
					else return htmlentities($match);
				}
			}
			
			// Attributes
			if((trim($attributes) != "") && (substr($tag, 0, 1) != "/")) {
				// Check attributes
				$attributes = preg_replace("/ *(" . implode("|", self::$attrs) . ")( *= *\"[^\"]*\"| *= *'[^']*'| *= *[a-z0-9]*)?/i", "", $attributes);
				
				// Add quotes to attribute values
				$attributes = preg_replace("/([^=>]*) *= *([a-z0-9]+)/i", "$1=\"$2\"", $attributes);
			}
			
			return "<{$tag}{$attributes}>";
		}
		
		// function variable(): Outputs a variable as a html table
		public function variable() {
			$var = $this->text;
			if(!isset($d)) static $d = Array();
			foreach($d as $key => $value)
				if(($var === $value) && (is_string($var) || is_array($var) || is_object($var))) return "<p class=\"recursion\"><i>Recursion</i></p>";
			$d[] = $var;
			if(!isset($count)) static $count = 0;
			
			if($var === null) return "<p class=\"null\"><i>null</i></p>";
			elseif($var === true) return "<p class=\"boolean true\"><i>true</i></p>";
			elseif($var === false) return "<p class=\"boolean false\"><i>false</i></p>";
			elseif(is_string($var)) return "<p class=\"string\">" . (new self($var))->escape() . "</p>";
			elseif(is_int($var)) return "<p class=\"number\">" . (string)$var . "</p>";
			elseif(is_float($var)) return "<p class=\"number float\">" . (string)$var . "</p>";
			elseif(is_array($var) || is_object($var)) {
				$r = "<div class=\"" . (is_object($var) ? "object" : "array") . "\">";
				$r .= "<p style=\"padding-top: 10px;\">" . (new self($var))->escape() . "</p>";
				$r .= "<div class=\"table-responsive\"><table class=\"table table-responsive\"><tbody>";
				$r .= "<tr><td style=\"min-width:100px;width:200px;\"><b>Key</b></td>";
				$r .= "<td><b>Value</b></td></tr>";
				foreach($var as $key => $value) {
					$r .= "<tr><td style=\"min-width:100px;width:200px;\">";
					$r .= (new self($key))->escape();
					$r .= "</td><td" . (is_array($value) || is_object($value) ? " style=\"padding:0px;\"" : "") . ">";
					$count++;
					$r .= (new self($value))->variable();
					$count = $count - 1;
					$r .= "</td></tr>";
				} if(count($var) < 1) {
					$r .= "<tr><td colspan=\"2\">";
					$r .= "<i>Empty</i>";
					$r .= "</td></tr>";
				} return $r . "</tbody></table></div></div>";
			} elseif(is_resource($var)) return "<p class=\"resource\"><i>Resource</i> " . get_resource_type($var) . "</p>";
			else return "<p>Unknown variable type.</p>";
			
			if($count == 0)
				// End
				$d = Array();
		}
	}
	