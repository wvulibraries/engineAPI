<?php

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
function mysqlLogin($username, $password) {
	
	global $engineDB;
	global $engineVars;

	$engine = EngineAPI::singleton();

	if (!isset($engineVars['mysqlAuthTable'])) {
		$engineVars['mysqlAuthTable'] = "users";
	}
	
	$engineDB = $engine->getPrivateVar("engineDB");
	
	$username = $engineDB->escape($username); // Escaping username
	$password = $engineDB->escape(md5($password)); // Escaping and hashing password
	
	$sql = sprintf("SELECT * FROM %s WHERE username='%s' AND password='%s'",
		$engineDB->escape($engineVars['mysqlAuthTable']),
		$username,
		$password
	);
	
	$engineDB->sanitize = FALSE;			
	$sqlResult = $engineDB->query($sql);
	
	if (!$sqlResult['result'] || mysqli_num_rows($sqlResult['result']) == 0) {
		return FALSE;
	}
	
	// Fetching user data if login successful
	$userData = mysqli_fetch_assoc($sqlResult['result']);
	
	// Setting session variables
	$_SESSION['groups']   = "";
	$_SESSION['ou']       = "";
	$_SESSION['username'] = $userData['username']; // Storing username from database
	$_SESSION['authType'] = "mysql";

	return TRUE;
}


?>