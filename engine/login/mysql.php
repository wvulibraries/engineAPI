<?php

global $loginFunctions;
$loginFunctions['mysql'] = "mysqlLogin";

function mysqlLogin($username,$password,$engine) { 
	
	global $engineDB;
	
	$sql = sprintf("SELECT * FROM users WHERE username='%s' AND password='%s'",
		$engineDB->escape($username),
		$engineDB->escape(md5($password))
		);
		
	$engineDB->sanitize = FALSE;			
	$sqlResult = $engineDB->query($sql);
	
	if (mysql_num_rows($sqlResult['result']) == 0) {
		return(FALSE);
	}
	
	$_SESSION['groups']   = "";
	$_SESSION['ou']       = "";
	$_SESSION['username'] = $username;
	$_SESSION['authType'] = "mysql";


	return(TRUE);
	
}

?>