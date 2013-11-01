<?php

// we are loading this automatically because it takes the place of older engineAPI 
// functionality and needs to be loaded to catch those instances where it is/was used.

require_once("eapi_function.php");
$eapif = new eapi_function();

require_once("eapi_includes.php");
$eapii = new eapi_includes();

?>