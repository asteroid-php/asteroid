<?php
	/* Asteroid
	 * interface Authentication
	 * 
	 * Defines what custom authentication classes must do.
	 */
	namespace Asteroid;
	interface AuthenticationInterface {
		// function __construct(): Creates a new {class} object
		// $application argument is the application object
		public function __construct($application);
		
		// function controller(): Gets the authentication controller
		// The authentication controller gets the /auth url
		public function controller();
		
		// function user(): Returns the current user or null if not authenticated
		public function user();
		
		// function loggedin(): Checks if the user is logged in
		public function loggedin(&$user = null);
	}
	