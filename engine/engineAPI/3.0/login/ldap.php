<?php
global $loginFunctions;
$loginFunctions['ldap'] = "ldapLogin";

function ldapLogin($username,$password,$engine=NULL)
{
    global $ldapSearch;

	// Load the ldapSearch module
	require EngineAPI::$engineDir.'/modules/ldapSearch/ldapSearch.php'; // (I'm not sure if the autoloader is set yet)
	$ldapSearch = new ldapSearch(EngineAPI::singleton()->localVars("domain"));

	if($ldapSearch->login($username,$password)){

        $user = $ldapSearch->findUser($username);
        if($user){
	        $groupsClean=array();
	        $groups = $ldapSearch->getGroupsFromUser($user,TRUE);
	        foreach($groups as $group){
	             $group = $ldapSearch->getAttributes($group,'cn');
	             $groupsClean[] = $group['cn'];
	        }

			$_SESSION['groups']   = $groupsClean;
			$_SESSION['ou']       = getOU($user);
			$_SESSION['username'] = $username;
			$_SESSION['authType'] = "ldap";
	        
	        return TRUE;
        }else{
        	return FALSE;
        }
	}else{
        return FALSE;
    }
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
    global $ldapSearch;
    $cn = $ldapSearch->getAttributes($dn, 'cn');
    if($cn){
        return $cn;
    }else{
        return FALSE;
    }
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

function securityUserCheck($username,$password,$type,$ldapconn,$ldapDomain)
{

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