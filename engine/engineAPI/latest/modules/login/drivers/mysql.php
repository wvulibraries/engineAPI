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
	$enginevars = enginevars::getInstance();
	$engineDB   = db::get('engineDB');

	if (!($engineDB instanceof dbDriver)) {
		errorHandle::newError(__METHOD__."() No engineDB database driver to use!", errorHandle::DEBUG);
		return FALSE;
	}

	$dbTable  = $engineDB->escape($enginevars->get("mysqlAuthTable", "users"));
	$dbResult = $engineDB->query("SELECT * FROM `$dbTable` WHERE `username`='?' AND `password`='?'", array($username, md5($password)));
	if (!$dbResult->rowCount()) return FALSE;

	$_SESSION['groups']   = "";
	$_SESSION['ou']       = "";
	$_SESSION['username'] = $username;
	$_SESSION['authType'] = "mysql";

	return TRUE;
}

?>