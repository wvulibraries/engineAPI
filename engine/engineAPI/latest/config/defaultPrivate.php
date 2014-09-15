<?php
/**
 * EngineAPI default private config
 * @package EngineAPI
 */

// This file should be set to be readable only by the web server user and the system administrator (or root)

$engineDB = array(
	'driver'        => 'mysql',
	'driverOptions' => array(
		'host'   => 'localhost',
		'port'   => 3306,
		'user'   => 'root',
		'pass'   => '',
		'dbName' => 'EngineAPI'
	)
);

$privateVars["engineDB"] = array(
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
