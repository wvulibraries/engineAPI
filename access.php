<?php

global $engineVars;

/* This needs fixed so that it is more modular. 

if (isset($accessControl)) {
	
	include all the files in directory "modules/accessControl/"
	
}

each files should contain an init that looks at $accessControl['%Something%'], 
determines if it exists. If its conditions are true or false and return an array $access.
$access['access'] = True || False if client should be granted access
$access['check'] = True || False if access should be checked.

*/

function accessDefined($accessControl) {

	if (debugNeeded("access")) {
		debugDisplay("access","\$_SESSION",1,"",$_SESSION);
	}

	$access = array();
	$access['access'] = FALSE;
	$access['check']  = FALSE;

	if (isset($accessControl['IP'])) {

		$allowIPRules = array();
		$denyIPRules  = array();

		//Check for IP access Restrictions
		foreach ($accessControl['IP'] as $ip => $allow) {
			switch($allow) {
				case 1:
				$allowIPRules[] = $ip;
				break;
				case 0:
				default:
				$denyIPRules[] = $ip;
				break;
			}
		}
		if(sizeof($allowIPRules) > 0 || sizeof($denyIPRules) > 0) {
			$access['access'] = checkIPAccess($denyIPRules,$allowIPRules);
			$access['check']  = TRUE;
		}
	}

	// This needs done after every method. Probably should move all these functions into 
	// a while loop
	if($access['check'] && !$access['access']) {
		return($access);
	}

	if (debugNeeded("access")) {
		debugDisplay("access","Access Chain",1,"Passed IP Check",NULL);
	}
	
	//Check for AD Access Restrictions
	if (isset($accessControl['AD'])) {
		
		// The following checks require the user to be logged in
		if(!sessionGet("username")) {
			//ob_end_clean();
			//header("Location: ".$engineVars['loginPage'] );
			//exit;

			if (debugNeeded("access")) {
				debugDisplay("access","Access Chain",2,"Not Logged In",NULL);
			}

			$access['access'] = FALSE;
			$access['check']  = TRUE;
			return($access);
		}
		
		$userRules  = array();
		$groupRules = array();
		$ouRules    = array();
		
		// Most General
		if (isset($accessControl['AD']['OU'])) {
			foreach ($accessControl['AD']['OU'] as $ou => $allow) {
				switch($allow) {
					case 1:
					$ouRules['allow'][] = $ou;
					break;
					case 0:
					default:
					$ouRules['deny'][] = $ou;
					break;
				}
			}
		}
		
		// Group rules override OU Rules
		if (isset($accessControl['AD']['Groups'])) {
			foreach ($accessControl['AD']['Groups'] as $group => $allow) {
				switch($allow) {
					case 1:
					$groupRules['allow'][] = $group;
					break;
					case 0:
					default:
					$groupRules['deny'][] = $group;
					break;
				}
			}
		}
		
		//Most specific. User rules override Group and OU Rules
		if (isset($accessControl['AD']['Usernames'])) {
			foreach ($accessControl['AD']['Usernames'] as $username => $allow) {
				switch($allow) {
					case 1:
					$userRules['allow'][] = $username;
					break;
					case 0:
					default:
					$userRules['deny'][] = $username;
					break;
				}
			}
		}
				
		if((isset($userRules['allow'])  && sizeof($userRules['allow'])  > 0) || 
		   (isset($groupRules['allow']) && sizeof($groupRules['allow']) > 0) || 
		   (isset($ouRules['allow'])    && sizeof($ouRules['allow'])    > 0) || 
		   (isset($userRules['deny'])   && sizeof($userRules['deny'])   > 0) || 
		   (isset($groupRules['deny'])  && sizeof($groupRules['deny'])  > 0) || 
	       (isset($ouRules['deny'])     && sizeof($ouRules['deny']))) {
			$access['access'] = checkADAccess($userRules,$groupRules,$ouRules);
			$access['check']  = TRUE;
		}
	}
	

	if (debugNeeded("access")) {
		debugDisplay("access","Access Chain",3,"Final Return",NULL);
	}

	return($access);

}

function checkADAccess($userRules,$groupRules,$ouRules) {

	$access = NULL;
	
	/* 1
	/* Check for individual user rules. These take precidence over all other AD access Rules
	*/
	
	if(isset($userRules['allow']) && sizeof($userRules['allow']) > 0) {
		if(checkUsers($userRules['allow'])) {
			return(TRUE);
		}
	}
	if(isset($userRules['deny']) && sizeof($userRules['deny']) > 0) {
		if(checkUsers($userRules['deny'])) {
			return(FALSE);
		}
	}

	/* 2
	/* User was not found in user rules (or user specific rules are not defined)
	/* Check Group Rules if group rules are defined
	*/
	if(isset($groupRules['allow']) && sizeof($groupRules['allow']) > 0) {
		if(checkGroups($groupRules['allow'])) {
			return(TRUE);
		}
	}
	if(isset($groupRules['deny']) && sizeof($groupRules['deny']) > 0) {
		if(checkGroups($groupRules['deny'])){
			return(FALSE);
		}
	}

	/* 3
	/* Check OU Rules
	*/
	if(isset($ouRules['allow']) && sizeof($ouRules['allow']) > 0) {
		if(checkOUs($ouRules['allow'])){
			return(TRUE);
		}
	}
	if(isset($ouRules['deny']) && sizeof($ouRules['deny']) > 0) {
		if(checkOUs($ouRules['deny'])) {
			return(FALSE);
		}
	}
	
	return(FALSE);
	
}

/*
/* checkUsers, checkGroups, and checkOUs functions should probably be merged into
/* a single function "checkLDAPArrays"
*/

// returns true is access should be granted
// returns false if access should be denied
// returns null if unable to determine
function checkUsers($users) {

	$username = sessionGet("username");
	
	if (debugNeeded("access")) {
		debugDisplay("access","Username",1,"Logged in as User: $username",NULL);
	}
	
	if (!$username) {
		return(NULL);
	}
	
	if (in_array($username, $users)) {
		return(TRUE);
	}
	
	return(FALSE);
}

// returns true is access should be granted
// returns false if access should be denied
// returns null if unable to determine
//
// $groups is an array, gets each group the user is 
// in to the groups in $groups array
function checkGroups($groups) {

	$usergroups = sessionGet("groups");
	
	if (!$usergroups) {
		return(NULL);
	}
	
	foreach ($usergroups as $usergroup) {
		if (in_array($usergroup, $groups)) {
			return(TRUE);
		}
	}
	
	return(FALSE);
}

// returns true is access should be granted
// returns false if access should be denied
// returns null if unable to determine
//
// $group is a string. Checks to see if the user belongs to the group provided.
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

// returns true is access should be granted
// returns false if access should be denied
// returns null if unable to determine
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

function checkIPAccess($denyIPRules,$allowIPRules) {

	$access = FALSE;

	if(sizeof($allowIPRules) > 0) {
		$access = checkIPs($allowIPRules,TRUE);
	}

	if(sizeof($denyIPRules) > 0) {
		$access = checkIPs($denyIPRules,FALSE);
	}

	return($access);
}

function checkIPs($ips,$allow) {
		
	$remoteAddr = array();
	$remoteAddr = explode(".",$_SERVER['REMOTE_ADDR']);
	
	$ipFound = FALSE;
	
	$ipFound = ipRangeCheck($ips);
	
	if (!$allow && !$ipFound) {
		// IP Not in range(s)
		// Deny only IPs in the range(s)
		return(TRUE);
	}
	
	if ($allow && $ipFound) {		
		// IP in range(s)
		// Allow only IPs in the range(s)
		return(TRUE);
	}
	
	return(FALSE);
}

?>
