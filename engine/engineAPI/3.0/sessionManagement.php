<?php

/* 
This exists to provide a 'standard' way of dealing with sessions. If we ever start using a
module to handle sessions we can modify these functions and not have to touch any of the code.
*/

function sessionStart() {
	
	session_name("engineCMS"); // apparently setting this is important
	session_start();
	
	$userCSRF = sessionGet("CSRF");
	if ($userCSRF == FALSE || $userCSRF == "engineCSRF not set") {
		sessionSetCSRF();
	}
	

}

// This should only be called when doing a log off. 
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


// Returns true if the variable is assigned the value
// Otherwise False
function sessionSet($variable,$value) {
	
	if(!isset($_SESSION)) {
		return FALSE;
	}
	
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

// returns that value of $variable, NULL if not defined.
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
					return(FALSE);
				}
				
			} 
			else { 
				if (!isset($prevTemp[$V])) {
					return(FALSE);
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

// delete a variable from the session
function sessionDelete($variable) {
	unset($_SESSION[$variable]);
	return(TRUE);
}

/* 
Functions to prevent Cross Site Request Forgeries 
*/

function sessionSetCSRF() {
	$md5time = md5(uniqid(rand(), true));
	$return = sessionSet("CSRF",$md5time);
	
	return($return);
}

function sessionCheckCSRF($CSRF) {

	$userCSRF = sessionGet("CSRF");
	
	if ($userCSRF == FALSE || $userCSRF == "engineCSRF not set") {
		return(FALSE);
	}
	
	if ($CSRF == $userCSRF) {
		return(TRUE);
	}
	
	return(FALSE);
	
}

function sessionInsertCSRF($form = TRUE) {
	$output = sessionGet("CSRF");
	
	if (!$output) {
		$output = "engineCSRF not set";
	}
	
	if($form === TRUE) {
		$output = "<input type=\"hidden\" name=\"engineCSRFCheck\" value=\"".$output."\" />";
	}
	
	return($output);
}

?>