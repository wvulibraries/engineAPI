<?php

$engineDir = "/home/library/phpincludes/engineAPI/engine";
include($engineDir ."/engine.php");
$engine = new EngineCMS();

if(isset($_SESSION)) {
	
	if (debugNeeded("logout")) {
		debugDisplay("logout","\$_SESSION",1,"Contents of the \$_SESSION array.",$_SESSION);
	}

	$termed = sessionEnd($engine);
}

?>

<?php
if ($termed) {

	$logoutRedirect = $engineVars['WEBROOT'];
	if (isset($engine->cleanGet['HTML']['redirect'])) {
		$logoutRedirect = $engine->cleanGet['HTML']['redirect'];
	}

	if (!debugNeeded("logout")) {
		global $engineVars;
		header("Location: ".$logoutRedirect );
	}

}
else {
 print "<h1>CSRF Error Check</h1>";
}
?>