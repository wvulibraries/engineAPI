<?php

require_once("/home/engineAPI/phpincludes/engine/engineAPI/latest/engine.php");
$engine = EngineAPI::singleton();

// Turns on verbose debugging in the apache error logs
errorHandle::errorReporting(errorHandle::E_ALL);

?>

<p>Hello World</p>