<?php

$engineDir = "/home/library/phpincludes/engine/engineAPI";
include($engineDir ."/engine.php");
$engine = EngineAPI::singleton();

if(isset($_SESSION)) {
	
	if (debugNeeded("logout")) {
		debugDisplay("logout","\$_SESSION",1,"Contents of the \$_SESSION array.",$_SESSION);
	}

	$termed = sessionEnd();
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