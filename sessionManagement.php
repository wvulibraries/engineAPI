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
	session_destroy();
}


// Returns true if the variable is assigned the value
// Otherwise False
function sessionSet($variable,$value) {
	
	if(!isset($_SESSION)) {
		return FALSE;
	}
	
	if ($_SESSION[$variable] = $value) {
		return TRUE;
	}
	
	return FALSE;
}

// returns that value of $variable, FALSE if not defined.
function sessionGet($variable) {
	
	if(isset($_SESSION[$variable])) {	
		return($_SESSION[$variable]);
	}
	
	return FALSE;
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
	
	if($form) {
		$output = "<input type=\"hidden\" name=\"engineCSRFCheck\" value=\"".$output."\" />";
	}
	
	return($output);
}

?>