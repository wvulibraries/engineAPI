<?php

global $accessControl;
$accessControl['MYSQLuser']   = "accessControl_mysql_user";
$accessControl['MYSQLauth']   = "accessControl_mysql_auth";

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