<?php
/**
 * EngineAPI default private config
 * @package EngineAPI
 */

// This file should be set to be readable only by the web server user and the system administrator (or root)

global $engineVarsPrivate;//MySQL Information

$engineVarsPrivate['mysql']['server']   = "localhost";
$engineVarsPrivate['mysql']['port']     = "3306";
$engineVarsPrivate['mysql']['username'] = "username";
$engineVarsPrivate['mysql']['password'] = 'password';

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
);
?>