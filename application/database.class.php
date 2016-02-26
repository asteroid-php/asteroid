<?php
	/* Asteroid
	 * class Database
	 * 
	 * Connects to databases without using a dsn
	 */
	namespace Asteroid;
	use PDO;
	class Database extends PDO {
		public function __construct($hostname, $username, $password, $database) {
			$dsn = self::generateDSN("mysql", Array("host" => $hostname, "dbname" => $database, "charset" => "UTF8"));
			parent::__construct($dsn, $username, $password);
		}
		
		public static function generateDSN($dbtype, $params) {
			$dsn = $dbtype . ":";
			foreach($params as $key => $value) {
				$dsn .= urlencode($key);
				$dsn .= "=";
				$dsn .= urlencode($value);
				$dsn .= ";";
			}
			
			return rtrim($dsn, ";");
		}
	}
	