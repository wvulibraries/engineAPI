<?php
/**
 * EngineAPI ACL - Active Directory
 * Active Directory ACL tools
 *
 * @package EngineAPI\AccessControl
 */

global $accessControl;
$accessControl['ADdomain'] = "accessControl_ad_domain";
$accessControl['ADuser']   = "accessControl_ad_user";
$accessControl['ADgroup']  = "accessControl_ad_group";
$accessControl['ADou']     = "accessControl_ad_ou";
$accessControl['ADauth']   = "accessControl_ad_auth";

/**
 * Check the AD user
 *
 * @param string $value The username to check for
 * @param bool $state
 * @return bool
 */
function accessControl_ad_user($value,$state=FALSE) {
	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}
	
	$username = session::get("username");
	
	if ($value == $username && $state === TRUE) {
		return(TRUE);
	}
	if ($value == $username && $state === FALSE) {
		return(FALSE);
	}
	
	return(FALSE);
}

/**
 * Check that the user auth'd through AD
 *
 * @param $value
 * @param bool $state
 * @return bool
 */
function accessControl_ad_auth($value,$state=FALSE) {	

	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}

	$username = session::get("username");
	
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
 * Check AD group
 *
 * @param string $value
 * @param bool $state
 * @return bool
 */
function accessControl_ad_group($value,$state=FALSE) {
	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}
	
	$usergroups = session::get("groups");
	
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

/**
 * Check AD OU (Org Unit)
 *
 * @param string $value
 * @param bool $state
 * @return bool
 */
function accessControl_ad_ou($value,$state=FALSE) {

	$returnValue = ADcheckLoginStatus();
	if ($returnValue === FALSE) {
		return(FALSE);
	}

	$userou   = session::get("ou");
	
	if ($value == $userou && $state === TRUE) {
		return(TRUE);
	}
	if ($value == $userou && $state === FALSE) {
		return(FALSE);
	}

	return(FALSE);
}

/**
 * Check user's login status
 * @return bool
 */
function ADcheckLoginStatus() {
	
	$authType   = session::get("authType");
	if ($authType != "ldap") {
		return(FALSE);
	}
	
	if(!session::get("username")) {
		return(FALSE);
	}
}

/**
 * Check AD security group
 *
 * @param $group
 * @return bool|null
 */
function checkGroup($group) {

	$usergroups = session::get("groups");
	
	if (!$usergroups) {
		return(NULL);
	}
	
	if (in_array($group,$usergroups)) {
		return(TRUE);
	}
	
	return(FALSE);
}

/**
 * Check AD OUs
 * @param $ous
 * @return bool|null
 */
function checkOUs($ous) {

	$userou = session::get("ou");
	
	if (!$userou) {
		return(NULL);
	}
	
	if (in_array($userou, $ous)) {
		return(TRUE);
	}
	
	return(FALSE);
}

?>