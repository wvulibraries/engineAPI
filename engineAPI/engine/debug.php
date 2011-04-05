<?php

function printENV($engine=NULL) {

	$engine = EngineAPI::singleton();

	if(isnull($engine)) {
		return(FALSE);
	}

	global $engineVars;
	
	print "<p><strong>Engine Variables:</strong>:<br />";
	foreach ($engineVars as $key => $value) {
		print "$key : <em>$value</em> <br />";
	}
	print "</p>";
	
	$localVars = $engine->localVarsExport();
	
	print "<p><strong>Local Variables:</strong>:<br />";
	foreach ($localVars as $key => $value) {
		print "$key : <em>$value</em> <br />";
	}
	print "</p>";
	
	return;
}

/* Stolen from: http://de.php.net/manual/en/function.print-r.php#75872 */
/* Modified to suite our needs */
/* This function still needs a lot of work */
function obsafe_print_r($var, $return = TRUE, $level = 0) {
	$html = false;
	$spaces = "";
	$space = $html ? "&nbsp;" : " ";
	$newline = $html ? "<br />" : "\n";
	for ($i = 1; $i <= 6; $i++) {
		$spaces .= $space;
	}
	$tabs = $spaces;
	for ($i = 1; $i <= $level; $i++) {
		$tabs .= $spaces;
	}
	if (is_array($var)) {
		$title = "Array";
	} elseif (is_object($var)) {
		$title = get_class($var)." Object";
	}
	$output = $title . $newline . $newline;
	foreach($var as $key => $value) {
		if (is_array($value) || is_object($value)) {
			$level++;
			$value = obsafe_print_r($value, true, $html, $level);
			$level--;
		}
		$output .= $tabs . "[" . $key . "] => " . $value . $newline;
	}
	if ($return) return $output;
	else echo $output;
}

function debugBuild($engine=NULL) {

	if (isnull($engine)) {
		return(FALSE);
	}
	 
	global $DEBUG;
	
	$DEBUG = array();
	if(isset($engine->cleanGet['HTML']['debug'])) {
		$DEBUG[$engine->cleanGet['HTML']['debug']] = TRUE;
	}
	
	// The idea with this is that debug messages would appear as a javascript alert
	// instead of printed into the page. 
	$DEBUG['javascript'] = (isset($engine->cleanGet['HTML']['debugjavascript']))?TRUE:FALSE;
	
	return(TRUE);
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