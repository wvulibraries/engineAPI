<?php


function displayTemplate($content) {
	
	global $engineVars;

	//local var replacements
	$content = preg_replace_callback("/\{local\s+?var=\"(.+?)\"\}/","localMatches",$content);
	
	//engine Replacements	
	$content = preg_replace_callback("/\{engine\s+(.+?)\}/","engineMatches",$content);
	
	//add a line break, \n, after <br /> ... makes the source a touch prettier. 
	$content = str_replace("<br />","<br />\n",$content);
	
	return($content);
}

function localMatches($matches) {
	global $localVars;
	return($localVars[$matches[1]]);
	
	//Things to impliment 
	// Email obsfucation
	// All Uppercase
	// All Lowercase 
	// Title Case (cap first letter of each word)
	
}

function engineMatches($matches) {

	$attPairs  = split("\" ",$matches[1]);

	foreach ($attPairs as $pair) {
		list($attribute,$value) = split("=",$pair);
		$temp[$attribute] = str_replace("\"","",$value);
	}

	if (isset($temp['name'])) {
		$output = handleMatch($temp);
		return($output);
	}
	else {
		return("name attribute missing");
	}
	
	return("Error");
	
}

function handleMatch($attPairs) {
	
	global $engineVars;
	
	$output = "Error: handleMatch in template.php";
	switch($attPairs['name']) {
		case "include":
		    $output = recurseInsert($attPairs['file'],$attPairs['type']);
			break;
		case "date":
		    $output = tempDate($attPairs);
			break;
		case "session":
		    $output = sessionGet($attPairs['var']);
			break;
		case "filelist":
		    $output = filelist($attPairs['dir'],$attPairs['temp']);
			break;
		case "filetemplate":
		    global $engineVars;
		    $output = $engineVars['fileList']->getAttribute($attPairs);
			break;
		case "insertCSRF":
		    $output = sessionInsertCSRF();
			break;
		case "email":
		    $output = $engineVars['emailSender'][$attPairs['type']];
			break;
		case "function":
		    $output = $attPairs['function']($attPairs);
			break;
		default:
		    $output = "Error: name function '".$attPairs['name']."' not found.";
	}
	return($output);
}

?>