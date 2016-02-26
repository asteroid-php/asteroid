<?php
	/* Asteroid
	 * class Mail
	 * 
	 * Adds some functions to PHPMailer, which should be loaded via composer.
	 */
	namespace Asteroid;
	use PHPMailer;
	class Mail extends PHPMailer {
		protected $application = null;
		
		public function __construct($application) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			parent::__construct();
			
			// Set configuration
			if($this->application->configuration([ "mail", "smtp" ]) !== null) {
				$this->Mailer = "smtp";
				$this->Host = is_string($hostname = $this->application->configuration([ "mail", "smtp", "hostname" ])) ? $hostname : "localhost";
				$this->Port = is_int($port = $this->application->configuration([ "mail", "smtp", "port" ])) ? $port : 25;
				if($this->application->configuration([ "mail", "smtp", "secure" ]) == 1) {
					$this->SMTPSecure = "ssl"; if(!is_int($port)) $this->Port = 465;
				} if($this->application->configuration([ "mail", "smtp", "secure" ]) == 2) {
					$this->SMTPSecure = "tls"; if(!is_int($port)) $this->Port = 587;
				} if(is_string($this->application->configuration([ "mail", "smtp", "username" ]))) {
					$this->SMTPAuth = true;
					$this->Username = $this->application->configuration([ "mail", "smtp", "username" ]);
					$this->Password = $this->application->configuration([ "mail", "smtp", "password" ]);
				}
			}
			
			$this->From = is_string($from = $this->application->configuration([ "mail", "from" ])) ? $from : "support@" . $this->application->getHostname();
			if(is_string($name = $this->application->configuration([ "mail", "name" ]))) $this->FromName = $name;
			
			$this->WordWrap = 75;
			$this->Timeout = 60;
		}
		
		public function render($view, $data = Array()) {
			if(is_array($data))
				$data["recipients"] = array_merge($this->getToAddresses(), $this->getCCAddresses(), $this->getBCCAddresses());
			
			// Get the parsed view without outputting it and exiting the script
			$html = $this->application->view()->parse($view, $data);
			
			$this->msgHTML($html);
			
			// Remove html spaces
			$this->Body = preg_replace("/>(\s+)</", "> <", $this->Body);
		}
	}
	