<?php

function webHelper_errorMsg($error) {
	
	$output = "<p class=\"errorMessage\">$error</p>";
	
	return($output);
	
}

function webHelper_successMsg($message) {
	
	$output = "<p class=\"successMessage\">$message</p>";
	
	return($output);
	
}

?>