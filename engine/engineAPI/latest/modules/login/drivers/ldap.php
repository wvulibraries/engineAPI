<?php
/**
 * EngineAPI MySQL Login
 * @package EngineAPI\Login
 */

global $loginFunctions;
$loginFunctions['ldap'] = "ldapLogin";

/**
 * Process a LDAP login attempt
 *
 * @todo Remove deprecated use of lc() function
 * @param string $username
 *        The user's username
 * @param string $password
 *        The user's password
 * @return bool
 */
function ldapLogin($username,$password)
{

	$localvars = localvars::getInstance();

    $ldapSearch = new ldapSearch($localvars->get("domain"));
    if($ldapSearch->login($username,$password)){
        $user = $ldapSearch->findUser($username);
        if($user){
	        $groupsClean=array();
	        $groups = $ldapSearch->getGroupsFromUser($user,TRUE);
	        foreach($groups as $group){
	             $group = $ldapSearch->getAttributes($group,'cn');
	             $groupsClean[] = $group['cn'];
	        }

	        session::set("groups",$groupsClean);
	        session::set("ou",getOU($user));
	        session::set("username",strtolower($username));
	        session::set("authType","ldap");

            // Proposed new layout:
            session::set("auth_ldap",array(
                'groups'   => $groups,
                'userDN'   => $user,
                'username' => $username
            ));
            
	        return TRUE;
        }else{
        	return FALSE;
        }
	}else{
        return FALSE;
    }
}

/**
 * Attempts to connect to the given LDAP server
 *
 * @todo Is this needed anymore? (I think ldapSearch replaces it)
 * @param $ldapServer
 *        The hostname of the LDAP server to connect to
 * @return bool|resource
 *         LDAP resource on success, FALSE if error occurred
 */
function ldapConnect($ldapServer) {

	$ldapConn = ldap_connect($ldapServer);

	if (isset($ldapConn)) {
		ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

		return($ldapConn);
	}

	return(FALSE);
}

/**
 * Attempts to disconnect from the given LDAP server
 *
 * @todo Is this needed anymore? (I think ldapSearch replaces it)
 * @param $ldapConn
 *        The LDAP resource provided by ldapConnect()
 * @return bool
 */
function ldapDisconnect($ldapConn) {

	if (is_null($ldapConn)) {
		return(FALSE);
	}

	ldap_unbind($ldapConn);

	return(TRUE);

}

/**
 * Get the user's LDAP groups
 *
 * @todo Merge this with ldapSearch and this is done more efficiently
 * @param $memberOf
 * @return array
 */
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

/**
 * This looks broken...
 *
 * @todo Is this broken?
 * @param $dn
 * @return bool
 */
function getGroup($dn) {
    global $ldapSearch;
    $cn = $ldapSearch->getAttributes($dn, 'cn');
    if($cn){
        return $cn;
    }else{
        return FALSE;
    }
}

/**
 * Get a user's OU
 *
 * @todo Merge this with ldapSearch?
 * @todo This needs to be modified to support nested OUs. (securityOUCheck function will need updated when this is updated)
 * @param $dn
 * @return bool
 */
function getOU($dn) {
	
	$regex = '/.+OU=(.+?)\,.+$/';
	preg_match($regex,$dn,$matches);
	
	if (isset($matches[1])) {
		return($matches[1]);
	}
	
	return(FALSE);
}

/**
 * Check a user's login credentials
 *
 * @todo Merge this with ldapSearch?
 * @param $username
 * @param $password
 * @param $type
 * @param $ldapconn
 * @param $ldapDomain
 * @return bool
 */
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

/**
 * Check a user's groups against a requested one
 *
 * @todo Merge this with ldapSearch?
 * @param $group
 * @param null $userGroups
 * @return bool
 */
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

/**
 * Check a user's OU(s) against a requested one
 *
 * @todo Merge this with ldapSearch?
 * @param string|array $ou
 *        String or array of OU(s) to check
 * @param null $userOU
 * @return bool
 */
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