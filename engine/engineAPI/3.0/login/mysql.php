<?php

global $loginFunctions;
$loginFunctions['mysql'] = "mysqlLogin";

function mysqlLogin($username,$password,$engine=NULL) { 
	
	global $engineDB;
	global $engineVars;

	$engine = EngineAPI::singleton();

	if (!isset($engineVars['mysqlAuthTable'])) {
		$engineVars['mysqlAuthTable'] = "users";
	}
	
	$engineDB = $engine->getPrivateVar("engineDB");
	
	$sql = sprintf("SELECT * FROM %s WHERE username='%s' AND password='%s'",
		$engineDB->escape($engineVars['mysqlAuthTable']),
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