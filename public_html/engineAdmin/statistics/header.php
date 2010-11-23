<?
$engineDir = "/home/library/phpincludes/engineAPI/engine";
include($engineDir ."/engine.php");
$engine = new EngineCMS();

$engine->localVars('pageTitle',"Engine Log Viewer");

// $engine->eTemplate("load","distribution");
$engine->eTemplate("load","systems1colMenu");
$engine->eTemplate("include","header");
?>
