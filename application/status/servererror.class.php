<?php
	/* Asteroid
	 * class ServerError
	 */
	namespace Asteroid\Status;
	class ServerError extends Error {
		protected $code = "500";
		protected $message = "Internal Server Error";
	}
	