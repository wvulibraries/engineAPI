<?php

/**
 * Determine if an email is internal
 *
 * @depreciated
 * @see email::internalEmailAddr()
 * @param $email
 * @return bool
 */
function internalEmail($email) {
	return(email::internalEmailAddr($email));
}

/**
 * [Web Helper] Generate error message
 * @depreciated
 * @see errorHandle::errorMsg()
 * @param $message
 * @return string
 */
function webHelper_errorMsg($message) {
	return(errorHandle::errorMsg($message));
}

/**
 * [Web Helper] Generate success message
 * @depreciated
 * @see errorHandle::successMsg()
 * @param $message
 * @return string
 */
function webHelper_successMsg($message) {
	return(errorHandle::successMsg($message));
}

/**
 * Determine if browser is mobile
 *
 * @depreciated
 * @see mobileBrowsers::isMobileBrowser()
 * @return null
 */
function engine_isMobileBrowser() {
	return(mobileBrowsers::isMobileBrowser());
}

/**
 * Generate HTML date drop-down
 *
 * @depreciated
 * @see date->dateDropDown()
 * @param $attPairs
 * @param null $engine
 * @return string
 */
function dateDropDown($attPairs,$engine=null) {
	$date = new date();
	return($date->dateDropDown($attPairs,$engine=null));
}

/**
 * Generate HTML time drop-down
 *
 * @depreciated
 * @see date->timeDropDown()
 * @param $attPairs
 * @param null $engine
 * @return string
 */
function timeDropDown($attPairs,$engine=null) {
	$date = new date();
	return($date->timeDropDown($attPairs,$engine=null));
}

/**
 * Generate a file list?
 *
 * @depreciated
 * @see fileList
 * @param $dir
 * @param $template
 * @param $engine
 * @return mixed
 */
function filelist($dir,$template,$engine) {
	
	global $engineVars;
	
	$engineVars['fileList'] = new fileList($dir,$engine);
	
	$output = $engineVars['fileList']->applyTemplate($engine->template."/".$template);
	
	$engineVars['fileList'] = NULL;
	
	return($output);
}

/**
 * Prints the current environment
 *
 * @depreciated
 * @see debug::printENV()
 */
function printENV(){
	debug::printENV();
	return;
}

/**
 * Generate print_r() output that's output-buffer safe
 *
 * @depreciated
 * @see debug::obsafe_print_r()
 * @param $var
 * @param bool $return
 * @param int $level
 * @return string
 */
function obsafe_print_r($var, $return = TRUE, $level = 0) {
	$return = debug::obsafe_print_r($var, $return, $level);
	if (!is_empty($return)) {
		return($return);
	}
	return;
}

?>