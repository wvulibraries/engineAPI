<?php

global $loginFunctions;
$loginFunctions['ldap'] = "ldapLogin";

function ldapLogin($username,$password,$engine=NULL) { 

	$engine = EngineAPI::singleton();
	
	global $engineVars;
	$domain = $engine->localVars("domain");

	$ldapServer = $engineVars['domains'][$domain]['ldapServer'];
	$ldapDomain = $engineVars['domains'][$domain]['ldapDomain'];
	$dn         = $engineVars['domains'][$domain]['dn'];
	$filter     = $engineVars['domains'][$domain]['filter'];
	$attributes = $engineVars['domains'][$domain]['attributes'];

	//print "$username, $ldapServer <br />$ldapDomain <br />$dn         <br />$filter     <br />$attributes";

	// Replace Meta %whatever% ... right now I just have Username. This should probably be expanded. 
	$filter     = preg_replace("/%USERNAME%/",$username,$filter);
	if(is_null($filter)) {
		return(FALSE);
	}

	$ldapconn = ldapConnect($ldapServer);

	if (!securityUserCheck($username,$password,"ldap",$ldapconn,$ldapDomain)) {
		ldapDisconnect($ldapconn);
		return(FALSE);
	}

	$sr         = ldap_search($ldapconn, $dn, $filter, $attributes) or die("<br />Object Not Found!<br />");
	$entry      = ldap_get_entries($ldapconn, $sr);

	ldapDisconnect($ldapconn);

	//	sessionSet("groups")   = (isset($entry[0]["memberof"]))?getGroups($entry[0]["memberof"]):FALSE;
	//	sessionSet("ou")       = (isset($entry[0]["dn"]))?getOU($entry[0]["dn"]):FALSE;
	//	sessionSet("username") = $username;

	$_SESSION['groups']   = (isset($entry[0]["memberof"]))?getGroups($entry[0]["memberof"]):FALSE;
	$_SESSION['ou']       = (isset($entry[0]["dn"]))?getOU($entry[0]["dn"]):FALSE;
	$_SESSION['username'] = $username;
	$_SESSION['authType'] = "ldap";


	return(TRUE);

}

function ldapConnect($ldapServer) {
	
	$ldapConn = ldap_connect($ldapServer);
	
	if (isset($ldapConn)) {
		ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
		
		return($ldapConn);
	}
	
	return(FALSE);
}

function ldapDisconnect($ldapConn) {

	if (is_null($ldapConn)) {
		return(FALSE);
	}

	ldap_unbind($ldapConn);
	
	return(TRUE);
	
}

function getGroups($memberOf) {
	$groups = array();
	for($I=0;$I<$memberOf["count"];$I++) {
		$group = getGroup($memberOf[$I]);
		array_push($groups,$group);
	}
	// Hack to fix MS AD LDAP bug that doesn't return Primary Group
	// Ensures all users are a "Domain User"
	array_push($groups,"Domain Users");
	
	return($groups);
}

function getGroup($dn) {
	
	$regex = '/^CN=(.+?)\,/';
	preg_match($regex,$dn,$matches);
	
	if (isset($matches[1])) {
		return($matches[1]);
	}
	
	return(FALSE);
}

// This needs to be modified to support nested OUs.
// We don't have a need for nested OUs, so i'll save it for later.
// securityOUCheck function will need updated when this is updated
function getOU($dn) {
	
	$regex = '/.+OU=(.+?)\,.+$/';
	preg_match($regex,$dn,$matches);
	
	if (isset($matches[1])) {
		return($matches[1]);
	}
	
	return(FALSE);
}

function securityUserCheck($username,$password,$type,$ldapconn,$ldapDomain) {
	
	if(is_null($username) || is_null($password)) {
		return(FALSE);
	}

	if ($type == "ldap") {
		if(isset($ldapconn) && isset($ldapDomain)) {
			//surpress the warning when it fails to bind
			return(@ldap_bind($ldapconn, $username."@".$ldapDomain, $password));
		}
		return(FALSE);
	}
	
	return(FALSE);
}

function securityGroupCheck($group,$userGroups=NULL) {
	
	if (is_null($userGroups) && isset($_SESSION['groups'])) {
		$userGroups = $_SESSION['groups'];
	}
	
	if (is_null($userGroups) || is_null($group)){
		return(FALSE);
	}
	
	foreach ($userGroups as $userGroup) {
		if ($userGroup == $group) {
			return(TRUE);
		}
	}
	
	return(FALSE);
}

// OU should be either a string or an array. If an array, assume nested
// OUs. [0] is parent down to [n] child. 
function securityOUCheck($ou,$userOU=NULL) {
	
	if (is_null($userOU) && isset($_SESSION['ou'])) {
		$userOU = $_SESSION['ou'];
	}
	
	if (is_null($userOU) || is_null($ou)){
		return(FALSE);
	}
		
	if ($ou == $userOU) {
		return(TRUE);
	}
	
	return(FALSE);
}

?>