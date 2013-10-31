<?php
/**
 * EngineAPI MySQL Login
 * @package EngineAPI\Login
 */


global $loginFunctions;
$loginFunctions['mysql'] = "mysqlLogin";

/**
 * Process a MySQL (Database-based) login attempt
 * @param string $username
 *        The user's username
 * @param string $password
 *        The user's password
 * @return bool
 */
function mysqlLogin($username,$password) {
	
	global $engineDB;
	

	$engine     = EngineAPI::singleton();
	$enginevars = enginevars::getInstance();

	if (!$enginevars->is_set("mysqlAuthTable")) {
		$enginevars->get("mysqlAuthTable","users");
	}
	
	$engineDB = $engine->getPrivateVar("engineDB");
	
	$sql = sprintf("SELECT * FROM %s WHERE username='%s' AND password='%s'",
		$engineDB->escape($enginevars->get("mysqlAuthTable")),
		$engineDB->escape($username),
		$engineDB->escape(md5($password))
		);
		
	$engineDB->sanitize = FALSE;			
	$sqlResult = $engineDB->query($sql);
	
	if (!$sqlResult['result'] || mysql_num_rows($sqlResult['result']) == 0) {
		return(FALSE);
	}
	
	$_SESSION['groups']   = "";
	$_SESSION['ou']       = "";
	$_SESSION['username'] = $username;
	$_SESSION['authType'] = "mysql";


	return(TRUE);
	
}

?>