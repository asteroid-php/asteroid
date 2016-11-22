<?php
	/* Asteroid
	 * class Message
	 * 
	 * Returned after adding a message.
	 */
	namespace Asteroid\Status;
	use Asteroid\BaseStatus;
	class Message extends BaseStatus {
		protected $mt = null;
		
		public function type($type) {
			if(is_string($type) && !is_string($this->mt))
				$this->mt = $type;
			
			return $this;
		}
		
		public function continuestatus() {
			if($this->mt == "error")
				return $this->application->status("Error")->continue();
		}
	}
	