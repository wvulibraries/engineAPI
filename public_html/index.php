<?php

require_once("/home/engineAPI/phpincludes/engine/engineAPI/latest/engine.php");
$engine = EngineAPI::singleton();

// Turns on verbose debugging in the apache error logs
errorHandle::errorReporting(errorHandle::E_ALL);

templates::load("distribution");
templates::display("header");
?>

<p>Hello World</p>

<?php
templates::display("footer");
?>