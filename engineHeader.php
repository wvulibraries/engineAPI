<?php

global $engineDir;

include_once($engineDir."/includes.php");

//Turn on Output Buffering
ob_start('displayTemplate');

global $engineVars;

if ($engineVars['log']) {
	$engineDB = new engineDB($engineVars['mysql']['username'],$engineVars['mysql']['password'],"engineCMS");
	engineLog(); // access log
}

global $localVars; //Variables defined in the page calling engine
global $accessControl;

/* Check for access on individual page */
$access = accessDefined($accessControl);
if ($access['check'] && !$access['access']) {
	// Access is denied. 
	
	if(isset($accessControl['reDirect'])) {
		ob_end_clean();
		header( 'Location: '.$accessControl['reDirect'] ) ;
		exit;
	}
	else {
		ob_end_clean();
		header( 'Location: '.$engineVars['loginPage'].'?page='.$_SERVER['PHP_SELF'] ) ;
		exit;
	}
	
	include($engineDir."/defaultAccessDenied.php");
	
	exit;
}

foreach ($localVars as $key => $value) {
	$engineVars['localVars'][$key] = $value;	
}

// we have to give this an initial default value, so that the dbtables recurse insert doesn't barf
$engineVars['currentTemplate'] = $engineVars['tempDir'] ."/". $engineVars['templateDefault'];

// dbTables is a list of all the tables used in an application. Listing both Production 
// and development tables. Look for it, recursively, from where it is. Should fall back to the 
// template directory so that we can grab a "master" set of tables for a site.
$dbt = array();
recurseInsert("dbTableList.php","php");
$dbTables = $dbt;
unset($dbt);

$openDB_Database = null;
$openDB_Username = $engineVars['mysql']['username'];
$openDB_Password = $engineVars['mysql']['password'];

if (isset($dbTables["engineDBInfo"]["database"])) {
	$openDB_Database = $dbTables["engineDBInfo"]["database"];
	if (isset($dbTables["engineDBInfo"]["username"])) {
		$openDB_Username = $dbTables["engineDBInfo"]["username"];
	}
	if (isset($dbTables["engineDBInfo"]["password"])) {
		$openDB_Password = $dbTables["engineDBInfo"]["password"];
	}
}

if(isset($localVars['openDB_Database'])) {
	
	$openDB_Database = $localVars['openDB_Database'];
	
	if (isset($localVars['openDB_Username'])) {
		$openDB_Username = $dbTables["engineDBInfo"]["username"];
	}
	if (isset($localVars['openDB_Password'])) {
		$openDB_Password = $dbTables["engineDBInfo"]["password"];
	}	
}

if(!isnull($openDB_Database)) {
	$engineVars['openDB'] = new engineDB($openDB_Username,$openDB_Password,$openDB_Database);
}

if(!isset($localVars['exclude_template'])) {
	$engineVars['currentTemplate'] = $engineVars['tempDir'] ."/". ((isset($localVars['engineTemplate'])) ? $localVars['engineTemplate'] : $engineVars['templateDefault']);

	include($engineVars['currentTemplate'] ."/templateHeader.php");
}

?>