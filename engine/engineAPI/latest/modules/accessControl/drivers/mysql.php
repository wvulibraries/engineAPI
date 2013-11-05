<?php
/**
 * EngineAPI ACL - MySQL Database
 * MySQL Database acl tools
 *
 * @package EngineAPI\AccessControl
 */

global $accessControl;
$accessControl['MYSQLuser']   = "accessControl_mysql_user";
$accessControl['MYSQLauth']   = "accessControl_mysql_auth";

/**
 * Check MySQL username
 *
 * @param string $value
 * @param bool $state
 * @return bool|null
 */
function accessControl_mysql_user($value,$state=FALSE) {
	$returnValue = mySQLcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}
	
	$username = sessionGet("username");
	
	if ($value == $username && $state === TRUE) {
		return(TRUE);
	}
	if ($value == $username && $state === FALSE) {
		return(FALSE);
	}
	
	return(NULL);
}

/**
 * Check MySQL auth
 *
 * @param string $value [NOT USED]
 * @param bool $state
 * @return bool
 */
function accessControl_mysql_auth($value,$state=FALSE) {	

	$returnValue = mySQLcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}

	$username = sessionGet("username");
	
	if ($username === FALSE && $state === TRUE) {
		return(FALSE);
	}
	
	if ($username === FALSE && $state === FALSE) {
		return(TRUE);
	}
	
	if (isset($username) && $state === TRUE && $username !== FALSE) {
		return(TRUE);
	}
	
	return(FALSE);
}

/**
 * Check MySQL login status
 * @return bool
 */
function mySQLcheckLoginStatus() {
	
	$authType   = sessionGet("authType");
	if ($authType != "mysql") {
		return(FALSE);
	}
	
	if(!sessionGet("username")) {
		return(FALSE);
	}
}

?>