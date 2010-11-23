<?php


global $engineVars;

//templates
if(!isset($localVars['exclude_template'])) {
	include($engineVars['currentTemplate']."/templateFooter.php");
}

// Clears all the output so far. Uncomment for testing
//ob_clean();

//send output to the client. This should most likely ALWAYS be the last line
ob_flush();

?>