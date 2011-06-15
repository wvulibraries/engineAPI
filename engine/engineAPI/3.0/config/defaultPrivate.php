<?php

// This file should be set to be readable only by the web server user and the system administrator (or root)

global $engineVarsPrivate;//MySQL Information

$engineVarsPrivate['mysql']['server']   = "localhost";
$engineVarsPrivate['mysql']['port']     = "3306";
$engineVarsPrivate['mysql']['username'] = "systems";
$engineVarsPrivate['mysql']['password'] = 'Te$t1234';

$engineVarsPrivate["privateVars"]["engineDB"]["file"]     = "errorHandle.php";
$engineVarsPrivate["privateVars"]["engineDB"]["function"] = "__construct()";

?>