<?php
/**
 * EngineAPI Helper Functions - wrappers
 * @package Helper Functions\wrappers
 */

/**
 * Determine if an email is internal
 *
 * @deprecated
 * @see email::internalEmailAddr()
 * @param $email
 * @return bool
 */
function internalEmail($email) {
	deprecated();
	return(email::internalEmailAddr($email));
}

/**
 * [Web Helper] Generate error message
 * @deprecated
 * @see errorHandle::errorMsg()
 * @param $message
 * @return string
 */
function webHelper_errorMsg($message) {
	deprecated();
	return(errorHandle::errorMsg($message));
}

/**
 * [Web Helper] Generate success message
 * @deprecated
 * @see errorHandle::successMsg()
 * @param $message
 * @return string
 */
function webHelper_successMsg($message) {
	deprecated();
	return(errorHandle::successMsg($message));
}

/**
 * Determine if browser is mobile
 *
 * @deprecated
 * @see mobileBrowsers::isMobileBrowser()
 * @return null
 */
function engine_isMobileBrowser() {
	deprecated();
	return(mobileBrowsers::isMobileBrowser());
}

/**
 * Generate HTML date drop-down
 *
 * @deprecated
 * @see date->dateDropDown()
 * @param $attPairs
 * @param null $engine
 * @return string
 */
function dateDropDown($attPairs,$engine=null) {
	deprecated();
	$date = new date();
	return($date->dateDropDown($attPairs,$engine=null));
}

/**
 * Generate HTML time drop-down
 *
 * @deprecated
 * @see date->timeDropDown()
 * @param $attPairs
 * @param null $engine
 * @return string
 */
function timeDropDown($attPairs,$engine=null) {
	deprecated();
	$date = new date();
	return($date->timeDropDown($attPairs,$engine=null));
}

/**
 * Generate a file list?
 *
 * @deprecated
 * @see fileList
 * @param $dir
 * @param $template
 * @param $engine
 * @return mixed
 */
function filelist($dir,$template,$engine) {
	deprecated();

	$enginevars = enginevars::getInstance();

	$enginevars->set("fileList", fileList($dir,$engine));

	$output = $enginevars->get("fileList")->applyTemplate($engine->template."/".$template);

	$enginevars->set("fileList", NULL);

	return($output);
}

/**
 * Prints the current environment
 *
 * @deprecated
 * @see debug::printENV()
 */
function printENV(){
	deprecated();
	debug::printENV();
	return;
}

/**
 * Generate print_r() output that's output-buffer safe
 *
 * @deprecated
 * @see debug::obsafe_print_r()
 * @param $var
 * @param bool $return
 * @param int $level
 * @return string
 */
function obsafe_print_r($var, $return = TRUE, $level = 0) {
	deprecated();
	$return = debug::obsafe_print_r($var, $return, $level);
	if (!is_empty($return)) {
		return($return);
	}
	return;
}

?>
