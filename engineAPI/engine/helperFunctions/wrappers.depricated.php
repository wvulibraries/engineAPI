<?php

function internalEmail($email) {
	return(email::internalEmailAddr($email));
}

function webHelper_errorMsg($message) {
	return(errorHandle::errorMsg($message));
}

function webHelper_successMsg($message) {
	return(errorHandle::successMsg($message));
}

function engine_isMobileBrowser() {
	return(mobileBrowsers::isMobileBrower());
}

// Template Date
// $attPairs['time'] == unix time, Option, current time if not provided
// $attPairs['format'] for date();
// function tempDate($attPairs) {
// 
// 	if (isset($attPairs['time'])) {
// 		return(date($attPairs['format'],$attPairs['time']));
// 	}
// 
// 	return(date($attPairs['format']));
// 	
// }

function dateDropDown($attPairs,$engine=null) {
	$date = new date();
	return($date->dateDropDown($attPairs,$engine=null));
}

function timeDropDown($attPairs,$engine=null) {
	$date = new date();
	return($date->timeDropDown($attPairs,$engine=null));
}

function filelist($dir,$template,$engine) {
	
	global $engineVars;
	
	$engineVars['fileList'] = new fileList($dir,$engine);
	
	$output = $engineVars['fileList']->applyTemplate($engine->template."/".$template);
	
	$engineVars['fileList'] = NULL;
	
	return($output);
}

?>