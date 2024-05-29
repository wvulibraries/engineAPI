<?php

/**
 * Starts the session
 * This exists to provide a 'standard' way of dealing with sessions.
 * If we ever start using a module to handle sessions we can modify these functions and not have to touch any of the code.
 *
 * @return void
 */
function sessionStart() {
	session_name("EngineAPI"); // apparently setting this is important
	session_start();

	$userCSRF = sessionGet("CSRF");
	if ($userCSRF == FALSE || $userCSRF == "engineCSRF not set") {
		sessionSetCSRF();
	}
}

/**
 * Ends the session
 * This should only be called when doing a log off.
 *
 * @return bool
 */
function sessionEnd() {
	$engine = EngineAPI::singleton();
	
	if (isset($engine->cleanGet["MYSQL"]["csrf"]) && sessionCheckCSRF($engine->cleanGet["MYSQL"]["csrf"])) {
		$_SESSION = array();
	
		if (ini_get("session.use_cookies")) {
			
			$secure = FALSE;
			if (!empty($_SERVER["HTTPS"])) {
				$secure = TRUE;
			}
			
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$secure, $params["httponly"]
				);
		}
	
		session_destroy();
		
		return(TRUE);
	}
	
	return(FALSE);
}


/**
 * Sets the given variable in the session
 * Returns true if the variable is assigned the value, otherwise False
 *
 * @param string $variable
 * @param mixed $value
 * @return bool
 */
function sessionSet($variable,$value) {
	if(!isset($_SESSION)) return FALSE;

	if (is_string($variable) === TRUE) {
		if ($_SESSION[$variable] = $value) {
			return TRUE;
		}
	}
	if (is_array($variable) === TRUE && count($variable) > 1) {
		$arrayLen = count($variable);
		$count    = 0;
		
		foreach ( $variable as $V ) { 
			$count++;
			if ($count == 1) { 
				$_SESSION[$V] = array(); 
				$prevTemp = &$_SESSION[$V]; 
			} 
			else { 
				if ($count == $arrayLen) {
					// $prevTemp[$V] = $value;
					if ($prevTemp[$V] = $value) {
						return TRUE;
					}
				}
				else {
					$prevTemp[$V] = array(); 
					$prevTemp = &$prevTemp[$V]; 
				}
			} 
		}
	}
	
	return FALSE;
}

/**
 * Retrieves the requested variable from the session
 * returns that value of $variable, NULL if not defined.
 *
 * @param string $variable
 * @return mixed|null
 */
function sessionGet($variable) {
	
	if (is_string($variable) === TRUE) {
		if(isset($_SESSION[$variable])) {	
			return($_SESSION[$variable]);
		}
	}
	if (is_array($variable) === TRUE && count($variable) > 1) {
		$arrayLen = count($variable);
		$count    = 0;
		
		foreach ( $variable as $V ) { 
			$count++;
			if ($count == 1) { 
				if (isset($_SESSION[$V])) {
					$prevTemp = &$_SESSION[$V];
				} 
				else {
					return(NULL);
				}
				
			} 
			else { 
				if (!isset($prevTemp[$V])) {
					return(NULL);
				}
				else {
					$prevTemp = &$prevTemp[$V]; 
				}
				if ($count == $arrayLen) {
					return($prevTemp);
				}
			} 
		}
	}
	
	return(NULL);
}

/**
 * delete a variable from the session
 *
 * @param string $variable
 * @return bool TRUE if unset, FALSE otherwise
 */
function sessionDelete($variable) {
	unset($_SESSION[$variable]);
	return isset($_SESSION[$variable]);
}

/**
 * Returns the session id
 *
 * @return string
 */
function sessionID(){
    return session_id();
}

/**
 * Sets CSRF token in session
 *
 * @see http://en.wikipedia.org/wiki/Cross-site_request_forgery
 * @return bool TRUE if set, FALSE otherwise
 */
function sessionSetCSRF() {
	$md5time = md5(uniqid(rand(), true));
	return sessionSet("CSRF",$md5time);
}

/**
 * Checks the CSRF token
 *
 * @param string $csrf The supplied token to be checked
 * @return bool TRUE if tokens match, FALSE otherwise
 */
function sessionCheckCSRF($csrf) {
	$userCSRF = sessionGet("CSRF");
	return $csrf == $userCSRF;
}

/**
 * Retrieves CSRF token from session
 * @param bool $form If TRUE, token will be wrapped inside hidden <input> tag. Otherwise, raw token will be returned
 * @return mixed|null|string
 */
function sessionInsertCSRF($form = TRUE) {
	$csrf = sessionGet("CSRF");
	if(!$csrf) return "engineCSRF not set";

	return $form
		? '<input type="hidden" name="engineCSRFCheck" value="'.$csrf.'" />'
		: $csrf;
}

?>