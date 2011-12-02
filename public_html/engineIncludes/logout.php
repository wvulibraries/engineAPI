<?php
require("/home/ereserves/phpincludes/engine/engineAPI/3.0/engine.php");
$engine = EngineAPI::singleton();

$debug = debug::create();
$debug->password = "eReserves";

if(isset($_SESSION)) {
	
	if ($debug->needed("logout")) {
		print "<pre>";
		var_dump($_SESSION);
		print "</pre>";
	}

	$termed = sessionEnd($engine);
}

?>

<?php
if ($termed) {

	if (!$debug->needed("logout")) {
		header("Location: ".$engineVars['WEBROOT'] );
	}

}
else {
	print "<h1>CSRF Error Check</h1>";
}
?>