<?php

function is_odd($number) {
	
	if ($number&1) {
		return(TRUE);
	}
	
	return(FALSE);
}

// Replacement for is_int
// Tests strings as well, returns true for an integer. 
// 12	TRUE  (String)
// 5	TRUE  (INT)
// 005	TRUE  (String)
// 5.5	FALSE (FLOAT)
// 5.5	FALSE (String)
// 0.5	FALSE (String)
// test	FALSE (String)
// 1	FALSE (BOOLE Value TRUE)
// 		FALSE (Empty string)

function isint($int) {
	
	if (is_numeric($int) === TRUE && (int)$int == $int) {
		return(TRUE);
	}
	
	return(FALSE);
	
}

// Returns TRUE if string evaluates to "null" as well as normal cases. 
// If strict===FALSE uses the built in is_null() php function
function isnull($var,$strict=TRUE) {
	if ($strict === FALSE) {
		return(is_null($var));
	}

	if (is_array($var)) {
		return(FALSE);
	}
	
	if (is_string($var)) {
		if (strtolower($var) == "null") {
			return(TRUE);
		}
	}
	else if ($var === NULL) {
		return(TRUE);
	}
	
	return(FALSE);
}

// Does NOT return true when test variable is int 0 or string "0"
//
// $strict === FALSE, gets pasted to isnull(). if FALSE uses built in is_null instead
// 		of engineAPI's is_null()
function is_empty($v,$strict=TRUE) {
	
	if (!isset($v)) {
		return(TRUE);
	}
	if (isnull($v,$strict)) {
		return(TRUE);
	}
	if ($v === FALSE) {
		return(TRUE);
	}
	
	if (is_array($v) && empty($v)) {
		return(TRUE);
	}
	else if (is_array($v)) {
		// return array before trim is hit
		return(FALSE);
	}
	
	$v = trim($v);
	
	if ($v == "0" || (is_int($v) && $v == 0)) {
		return(FALSE);
	}
	
	if (empty($v)) {
		return(TRUE);
	}
	
	return(FALSE);
}

/**
 * Returns TRUE if we are running in CLI mode
 * @return bool
 */
function isCLI(){
    return (php_sapi_name() == 'cli' || (@is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}

?>