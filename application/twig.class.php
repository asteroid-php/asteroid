<?php
	/* Asteroid
	 * class Twig
	 * 
	 * Extension of the Twig parser
	 */
	namespace Asteroid;
	use Twig_Environment;
	use Twig_SimpleTest;
	use Twig_SimpleFilter;
	use Closure;
	use Parsedown;
	class Twig extends Twig_Environment {
		protected $application = null;
		
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			$loader = new TwigLoader($application);
			parent::__construct($loader, Array(
				"cache" => $this->application->configuration([ "filesystem", "cache_dir" ])
			));
			
			// Type tests
			$is_string = new Twig_SimpleTest("string", function($value) {
				if(is_string($value)) return true;
				else return false;
			}); $this->addTest($is_string);
			
			$is_integer = new Twig_SimpleTest("integer", function($value) {
				if(is_int($value)) return true;
				else return false;
			}); $this->addTest($is_integer);
			
			$is_float = new Twig_SimpleTest("float", function($value) {
				if(is_float($value)) return true;
				else return false;
			}); $this->addTest($is_float);
			
			$is_numeric = new Twig_SimpleTest("numeric", function($value) {
				if(is_numeric($value)) return true;
				else return false;
			}); $this->addTest($is_numeric);
			
			$is_number = new Twig_SimpleTest("number", function($value) {
				if(is_int($value) || is_float($value)) return true;
				else return false;
			}); $this->addTest($is_number);
			
			$is_array = new Twig_SimpleTest("array", function($value) {
				if(is_array($value)) return true;
				else return false;
			}); $this->addTest($is_array);
			
			$is_object = new Twig_SimpleTest("object", function($value, $instanceof = null) {
				if(is_object($value) && ($instanceof === null)) return true;
				elseif(is_object($value) && ($value instanceof $instanceof)) return true;
				else return false;
			}); $this->addTest($is_object);
			
			$is_boolean = new Twig_SimpleTest("boolean", function($value) {
				if(is_bool($value)) return true;
				else return false;
			}); $this->addTest($is_boolean);
			
			$is_true = new Twig_SimpleTest("true", function($value) {
				if($value === true) return true;
				else return false;
			}); $this->addTest($is_true);
			
			$is_false = new Twig_SimpleTest("false", function($value) {
				if($value === false) return true;
				else return false;
			}); $this->addTest($is_false);
			
			$is_null = new Twig_SimpleTest("null", function($value) {
				if($value === null) return true;
				else return false;
			}); $this->addTest($is_null);
			
			$is_callable = new Twig_SimpleTest("callable", function($value) {
				if(is_callable($value)) return true;
				else return false;
			}); $this->addTest($is_callable);
			
			$is_function = new Twig_SimpleTest("function", function($value) {
				if(is_callable($value) && is_object($value) && ($value instanceof Closure)) return true;
				else return false;
			}); $this->addTest($is_function);
			
			$call = new Twig_SimpleFilter("call", function($value) {
				$arguments = array_slice(func_get_args(), 1);
				return call_user_func_array($value, $arguments);
			}); $this->addFilter($call);
			
			$filter_var = new Twig_SimpleFilter("count", function($value) {
				if(is_array($value) || is_object($value)) return count($value);
				else return 0;
			}); $this->addFilter($filter_var);
			
			$filter_var = new Twig_SimpleFilter("length", function($value) {
				if(is_string($value)) return strlen($value);
				elseif(is_array($value) || is_object($value)) return count($value);
				else return 0;
			}); $this->addFilter($filter_var);
			
			$filter_var = new Twig_SimpleFilter("filter_var", function($value, $type) {
				if(filter_var($value, $type)) return true;
				else return false;
			}); $this->addFilter($filter_var);
			
			$filter_tag = new Twig_SimpleFilter("filter_tag", function($value, $tag) {
				$html = new HTML($value);
				return $html->filter(Array($tag), Array());
			}); $this->addFilter($filter_tag);
			
			$filter_tags = new Twig_SimpleFilter("filter_tags", function($value, $tags) {
				$html = new HTML($value);
				return $html->filter($tags, Array());
			}); $this->addFilter($filter_tags);
			
			$filter_attr = new Twig_SimpleFilter("filter_attr", function($value, $attribute) {
				$html = new HTML($value);
				return $html->filter(Array(), Array($attribute));
			}); $this->addFilter($filter_attr);
			
			$filter_attrs = new Twig_SimpleFilter("filter_attrs", function($value, $attributes) {
				$html = new HTML($value);
				return $html->filter(Array(), $attributes);
			}); $this->addFilter($filter_attrs);
			
			$substr = new Twig_SimpleFilter("substr", function($value, $start, $length = null) {
				if(!is_string($value) && !is_numeric($value)) return false;
				if(is_int($length)) return substr($value, $start, $length);
				else return substr($value, $start);
			}); $this->addFilter($substr);
			
			$strpos = new Twig_SimpleFilter("strpos", function($value, $needle) {
				if(strpos($value, $needle)) return true;
				else return false;
			}); $this->addFilter($strpos);
			
			$parsedown = new Twig_SimpleFilter("parsedown", function($value, $breaks = false, $markup = true) {
				$parsedown = new Parsedown();
				$parsedown->setBreaksEnabled($breaks);
				$parsedown->setMarkupEscaped($markup);
				return $parsedown->text($value);
			}, Array("is_safe" => Array("html"))); $this->addFilter($parsedown);
			
			$parsedown_line = new Twig_SimpleFilter("parsedown_line", function($value, $breaks = false, $markup = true) {
				$parsedown = new Parsedown();
				$parsedown->setBreaksEnabled($breaks);
				$parsedown->setMarkupEscaped($markup);
				return $parsedown->line($value);
			}, Array("is_safe" => Array("html"))); $this->addFilter($parsedown_line);
			
			$print_r = new Twig_SimpleFilter("print_r", function($value) {
				return print_r($value, true);
			}); $this->addFilter($print_r);
			
			$var_dump = new Twig_SimpleFilter("var_dump", function($value) {
				ob_start();
				var_dump($value);
				return ob_get_clean();
			}); $this->addFilter($var_dump);
			
			$toarray = new Twig_SimpleFilter("toarray", function($value) {
				return (array)$value;
			}); $this->addFilter($toarray);
		}
	}
	