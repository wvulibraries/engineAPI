<?php

function debugBuild() {

	global $cleanGet;
	 
	$DEBUG = array();
	if(isset($cleanGet['HTML']['debug'])) {
		$DEBUG[$cleanGet['HTML']['debug']] = TRUE;
	}
	
	// The idea with this is that debug messages would appear as a javascript alert
	// instead of printed into the page. 
	$DEBUG['javascript'] = (isset($cleanGet['HTML']['debugjavascript']))?TRUE:FALSE;
	
	return($DEBUG);
}

function debugNeeded($file) {
	
	global $DEBUG;

	if (isset($DEBUG[$file]) || isset($DEBUG["all"])) {
		return(TRUE);
	}
	
	return(FALSE);
}

// Needs updated to handle debugjavascript == true
function debugDisplay($file,$type,$count=1,$message="Debug Message",$array=NULL,$return=FALSE) {
	
	$output  = "<h1>$file -- $type -- $count</h1>";
	$output .= "<h3>$message</h3>";
	if(!isnull($array)) {
		$output .= "<pre>";
		if(!$return) {
			$output .= print_r($array,TRUE);
		}
		else {
			$output .= obsafe_print_r($array);
		}
		$output .= "</pre>";
	}
	$output .= "<hr />";

	if($return) {
		return($output);
	}

	print $output;

	return;
	
}

?>