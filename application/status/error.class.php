<?php
	/* Asteroid
	 * class Error
	 * 
	 * Returned / thrown when there is an error running a controller.
	 */
	namespace Asteroid\Status;
	use Asteroid\BaseStatus;
	class Error extends BaseStatus {
		protected $code = "400";
		protected $message_type = "error";
	}
	