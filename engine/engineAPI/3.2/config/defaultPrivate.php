<?php

// This file should be set to be readable only by the web server user and the system administrator (or root)
// We are refactoring engine to use the .env files so instead of setting the values directly
// in this file we are pulling them from the enviroment.

global $engineVarsPrivate; // MySQL Information

$engineVarsPrivate['mysql']['server']   = getenv("DATABASE_HOST");
$engineVarsPrivate['mysql']['port']     = getenv("DATABASE_PORT");
$engineVarsPrivate['mysql']['username'] = getenv("DATABASE_USER");
$engineVarsPrivate['mysql']['password'] = getenv("DATABASE_PASSWORD");
$engineVarsPrivate['mysql']['database'] = getenv("DATABASE_NAME");

$engineVarsPrivate["privateVars"]["engineDB"] = array(
	array(
		'file'     => 'auth.php',
		'function' => '__construct',
	),
	array(
		'file'     => 'errorHandle.php',
		'function' => 'recordError',
	),
	array(
		'file'     => 'stats.php',
		'function' => '__construct',
	),
	array(
		'file'     => 'mysql.php',
		'function' => 'mysqlLogin',
	),
	// array(
	// 	'file'     => 'ldap.php',
	// 	'function' => 'ldapLogin',
	// ),
);
?>
