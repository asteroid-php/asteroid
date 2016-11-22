<?php
	/* Asteroid
	 * class Success
	 * 
	 * Returned / thrown when a controller runs successfully.
	 */
	namespace Asteroid\Status;
	use Asteroid\BaseStatus;
	class Success extends BaseStatus {
		protected $code = "200";
	}
	