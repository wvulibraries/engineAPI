<?php

global $accessControl;
$accessControl['ADdomain'] = "accessControl_ad_domain";
$accessControl['ADuser']   = "accessControl_ad_user";
$accessControl['ADgroup']  = "accessControl_ad_group";
$accessControl['ADou']     = "accessControl_ad_ou";
$accessControl['ADauth']   = "accessControl_ad_auth";

/* 
Functions ad_user and ADcheckLoginStatus should be more generalized. They could be used
for any authentication method
*/
function accessControl_ad_user($value,$state=FALSE) {
	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}
	
	$username = sessionGet("username");
	
	if (debugNeeded("access")) {
		debugDisplay("access","Username",1,"Logged in as User: $username",NULL);
	}
	
	if ($value == $username && $state === TRUE) {
		return(TRUE);
	}
	if ($value == $username && $state === FALSE) {
		return(FALSE);
	}
	
	return(FALSE);
}

function accessControl_ad_auth($value,$state=FALSE) {	

	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}

	$username = sessionGet("username");
	
	if (debugNeeded("access")) {
		debugDisplay("access","accessControl_ad_auth",2,"Username: '$username'",NULL);
	}
	
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

function accessControl_ad_group($value,$state=FALSE) {
	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}
	
	$usergroups = sessionGet("groups");
	
	foreach ($usergroups as $key=>$usergroup) {
		
		if ($usergroup == $value && $state === TRUE) {
			return(TRUE);
		}
		if ($usergroup == $value && $state === FALSE) {
			return(FALSE);
		}
	}
	
	return(FALSE);
}

function accessControl_ad_ou($value,$state=FALSE) {

	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}

	$userou   = sessionGet("ou");
	
	if ($value == $userou && $state === TRUE) {
		return(TRUE);
	}
	if ($value == $userou && $state === FALSE) {
		return(FALSE);
	}

	return(FALSE);
}

function ADcheckLoginStatus() {
	
	$authType   = sessionGet("authType");
	if ($authType != "ldap") {
		return(FALSE);
	}
	
	if(!sessionGet("username")) {
		
		if (debugNeeded("access")) {
			debugDisplay("access","Access Chain",2,"Not Logged In",NULL);
		}
		
		return(FALSE);
	}
}

function checkGroup($group) {

	$usergroups = sessionGet("groups");
	
	if (!$usergroups) {
		return(NULL);
	}
	
	if (in_array($group,$usergroups)) {
		return(TRUE);
	}
	
	return(FALSE);
}

function checkOUs($ous) {

	$userou = sessionGet("ou");
	
	if (!$userou) {
		return(NULL);
	}
	
	if (in_array($userou, $ous)) {
		return(TRUE);
	}
	
	return(FALSE);
}

?>