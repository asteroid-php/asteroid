<?php
	/* Asteroid
	 * class Session
	 * 
	 * Provides more manageable sessions than php.
	 */
	namespace Asteroid;
	use stdClass;
	class Session {
		protected $id = null;
		protected $application = null;
		protected $data = null;
		
		// function __construct(): Creates a new session object
		public function __construct($application, $id) {
			if(is_object($application) && ($application instanceof Application))
				$this->application = $application;
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			if(is_string($id)) $this->id = $id;
			else throw new Exception(__METHOD__, "\$id must be a string.");
		}
		
		// function getFilename(): Gets the filename of the session
		public function getFilename() {
			return rtrim($this->application->configuration([ "session", "file_directory" ]), "/") . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $this->id);
		}
		
		// function get(): Gets session data
		public function get($name = Array()) {
			//$database = $this->application->database($this->application->configuration([ "session", "database" ]));
			$table = $this->application->configuration([ "session", "table" ]);
			
			//foreach($this as $key => $value)
				//if(!in_array($key, Array("application", "id"))) unset($this->{$key});
			
			// Get session from the database
			/*$query = $database->prepare("SELECT * FROM `{$table}` WHERE `session_id` = :session_id");
			$query->bindValue(":session_id", $this->id);
			$query->execute();
			$data = $query->fetch(PDO::FETCH_OBJ);
			if(!isset($data->session_id)) return null;*/
			if(!file_exists($filename = $this->getFilename())) {
				$this->data = new Object();
				return null;
			} $data = $this->application->filesystem()->read($filename);
			
			$decoded = unserialize($data);
			$this->data = $decoded;
			
			if((is_object($this->data) && !($this->data instanceof Object)) || is_array($this->data))
				$this->data = new Object($this->data);
			if(!is_object($this->data)) $this->data = new Object();
			
			return $this->data->get($name);
		}
		
		// function set(): Sets session data
		public function set($name, $value) {
			$this->get(Array());
			$this->data->set($name, $value);
			
			$encoded = serialize($this->data);
			
			// Set session in the database
			if($this->application->configuration([ "session", "handler" ]) == 2) {
				$query = $database->prepare("INSERT INTO `{$table}` (`session_id`, `session_data`, `session_created`, `session_updated`) VALUES (:session_id, :session_data, :session_updated, NULL) ON DUPLICATE KEY UPDATE `session_data` = :session_data, `session_updated` = :session_updated");
				$query->bindValue(":session_id", $this->id);
				$query->bindValue(":session_data", $encoded);
				$query->bindValue(":session_updated", date("Y-m-d H:i:s"));
				$success = $query->execute();
			} else {
				$success = $this->application->filesystem()->write($this->getFilename(), $encoded);
			}
			
			if($success) return true;
			else throw new Exception(__METHOD__, "Failed to update session." . $this->getFilename());
		}
		
		// function cookie(): Gets the session id from a cookie
		public static function cookie($application, $name) {
			if(is_object($application) && ($application instanceof Application)) {}
			else throw new Exception(__METHOD__, "\$application must be an instance of Application.");
			
			if(is_string($name)) {}
			else throw new Exception(__METHOD__, "\$name must be a string.");
			
			if(!is_string($id = $application->request()->cookie($name))) {
				// Generate a session id and save it in cookies
				$id = hash("sha256", time() + uniqid(mt_rand(0, time()), true));
				$application->response()->cookie($name, $id);
				
				// Check if this session id already exists
				// ...
			}
			
			// Create a new session
			$session = new Session($application, $id);
			$session->get(Array());
			$session->set([ "__session" ], $id);
			return $session;
		}
	}
	