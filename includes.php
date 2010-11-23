<?php

include_once($engineDir."/template.php");
include_once($engineDir."/vars.php");
include_once($engineDir."/helperFunctions.php");
include_once($engineDir."/access.php");
include_once($engineDir."/sessionManagement.php");
include_once($engineDir."/ldap.php");
include_once($engineDir."/debug.php");
include_once($engineDir."/mysql.php");
include_once($engineDir."/log.php");
include_once($engineDir."/filelist.php");
include_once($engineDir."/stats.php");
include_once($engineDir."/userInfo.php");

//Web Helpers
// These 'web helper' includes need to be made more modular ... 
// they should only be loaded if they are actually being used
global $engineVars;
$webHelper_dirHandle = @opendir($engineVars['webHelper']) or die("Unable to open $path");
while (false !== ($file = readdir($webHelper_dirHandle))) {
	if ($file != "." && $file != ".." && $file) {
		$fileChunks = array_reverse(explode(".", $file));
		$ext= $fileChunks[0];
		if ($ext == "php") {
			include_once($engineVars['webHelper']."/".$file);
		}
	}
}


// Setup Functions
sessionStart();


// Sets up a clean PHP_SELF variable to use. 
phpSelf();

// Cross Site Request Forgery Check
if(!empty($_POST)) {
	if(!sessionCheckCSRF($_POST["engineCSRFCheck"])) {
		echo "CSRF Check Failed. Possible Cross Site Request Forgery Attack!";
		exit;
	}	
}

// Get clean $_POST
$cleanPost = array();
if(isset($_POST)) {
	foreach ($_POST as $key => $value) {
		$cleanKey                      = htmlSanitize($key);
		$cleanPost['HTML'][$cleanKey]  = htmlSanitize($value);
		$cleanPost['MYSQL'][$cleanKey] = dbSanitize($value);
	}
}



// Get clean $_GET
$cleanGet = array();
if(isset($_GET)) {
	foreach ($_GET as $key => $value) {
		$cleanKey                     = htmlSanitize($key);
		$cleanGet['HTML'][$cleanKey]  = htmlSanitize($value);
		$cleanGet['MYSQL'][$cleanKey] = dbSanitize($value);
	}
}

$DEBUG = debugBuild();

if (debugNeeded("includes")) {
	debugDisplay("includes","\$cleanPost",1,"Contents of the \$cleanPost array.",$cleanPost);
}

if (debugNeeded("includes")) {
	debugDisplay("includes","\$cleanGet",1,"Contents of the \$cleanGet array.",$cleanGet);
}

?>