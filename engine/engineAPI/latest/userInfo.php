<?php
/**
 * General user functions
 * @package EngineAPI\userInfo
 */

/**
 * $ipRanges is an array of valid IP ranges, same type that is used for security
 * $checkIP is option, if provided it checks against checkip, otherise it uses the clients remote_addr
 *
 * returns true if the clients IP is in this range, otherwise false
 *
 * @param $ipRanges IP range(s) to check against
 * @param string|null $checkIP The IP address to check
 * @return bool
 */
function userInfoIPRangeCheckArray($ipRanges,$checkIP = NULL) {
	
	if(isnull($checkIP) && isset($_SERVER['REMOTE_ADDR'])) {
		//WTF can't you use $_SERVER in the parameter 
		//assignments to the function? *sigh*
		$checkIP = $_SERVER['REMOTE_ADDR'];
	}
	
	// Break up the clients IP Address
	$remoteAddr = array();
	$remoteAddr = explode(".",$checkIP);
	
	$ipFound = FALSE; 
	
	foreach ($ipRanges as $key=>$ip) {
		$ipFound = userInfoIPRangeCheck($ip,$checkIP);
		if ($ipFound === TRUE) {
			break;
		}
	}
	
	if ($ipFound === TRUE) {
		return(TRUE);
	}
	
	return(FALSE);
	
}

/**
 * userInfo IP Range Check
 *
 * @param string $ip
 * @param string $checkIP The IP address to check
 * @return bool
 */
function userInfoIPRangeCheck($ip,$checkIP = NULL) {

	if(isnull($checkIP) && isset($_SERVER['REMOTE_ADDR'])) {
		$checkIP = $_SERVER['REMOTE_ADDR'];
	}
	
	if ($checkIP == $ip) {
		return(TRUE);
	}
	
	// Break up the clients IP Address
	$remoteAddr = array();
	$remoteAddr = explode(".",$checkIP);
	
	$ipFound = FALSE; 

	$ipQuads = array();
	$ipQuads = explode(".",$ip);
		
	for ($I = 0;$I <= 3;$I++) {
												
		if (preg_match("/\-/",$ipQuads[$I])) {
			
			// Contains a range of numbers
			list($min,$max) = explode("-",$ipQuads[$I]);

			if ($remoteAddr[$I] < $min || $remoteAddr[$I] > $max) {	
				break;
			}
		}
		elseif ($ipQuads[$I] == "*") {
			// Quad is a wild Character	
			//continue;
		} 
		else {
			// Quad is an exact number
			if ($ipQuads[$I] != $remoteAddr[$I]) {
				break;
			}
		}

		if ($I == 3) {
			$ipFound = TRUE;
		}
	}
	
	return($ipFound);
	
}

/**
 * IP range check
 *
 * @param $ipRanges
 * @param string|null $checkIP The IP address to check
 * @return bool
 */
function ipRangeCheck($ipRanges,$checkIP = NULL) {
	if(is_array($ipRanges)) {
		return(userInfoIPRangeCheckArray($ipRanges,$checkIP));
	}
	return(userInfoIPRangeCheck($ipRanges,$checkIP));
}

/**
 * Determine if the user is on or off campus
 *
 * @param string $checkIP The IP to check, defaults to $_SERVER['REMOTE_ADDR']
 * @return bool
 */
function onCampus($checkIP = NULL) {
	
	global $engineVars;
	
	if(isnull($checkIP) && isset($_SERVER['REMOTE_ADDR'])) {
		//WTF can't you use $_SERVER in the parameter 
		//assignments to the function? *sigh*
		$checkIP = $_SERVER['REMOTE_ADDR'];
	}
	
	$ipFound = ipRangeCheck($engineVars['onCampus'],$checkIP);
	
	if ($ipFound === TRUE) {
		return(TRUE);
	}
			
	return(FALSE);
	
}

?>