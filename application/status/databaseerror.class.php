<?php
	/* Asteroid
	 * class NotFound
	 * 
	 * Returned / thrown when there is a problem connecting to / using a database.
	 */
	namespace Asteroid\Status;
	use Asteroid\BaseStatus;
	class DatabaseError extends BaseStatus {
		protected $message = "**Database error:** Unknown error.";
		protected $error = $message;
		
		public function message($message) {
			$this->message = "**Database error:** " . $message;
			return $this;
		}
		
		public function error($message) {
			$this->error = $message;
			return $this;
		}
		
		public function continuestatus() {
			$this->application->error($this->error, true);
		}
	}
	