<?php

$engineDir = "/Path/To/phpincludes/engineAPI/engine";
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

	if (!debugNeeded("logout")) {
		global $engineVars;
		header("Location: ".$engineVars['WEBROOT'] );
	}

}
else {
 print "<h1>CSRF Error Check</h1>";
}
?>