<?php

// This file should be set to be readable only by the web server user and the system administrator (or root)

global $engineVarsPrivate;//MySQL Information

$engineVarsPrivate['mysql']['server']   = "localhost";
$engineVarsPrivate['mysql']['port']     = "3306";
$engineVarsPrivate['mysql']['username'] = "systems";
$engineVarsPrivate['mysql']['password'] = 'Te$t1234';

$engineVarsPrivate["privateVars"]["engineDB"][0]["file"]     = "userAuth.php";
$engineVarsPrivate["privateVars"]["engineDB"][0]["function"] = "__construct";

$engineVarsPrivate["privateVars"]["engineDB"][1]["file"]     = "stats.php";
$engineVarsPrivate["privateVars"]["engineDB"][1]["function"] = "__construct";

$engineVarsPrivate["privateVars"]["engineDB"][1]["file"]     = "mysql.php";
$engineVarsPrivate["privateVars"]["engineDB"][1]["function"] = "mysqlLogin";

?>