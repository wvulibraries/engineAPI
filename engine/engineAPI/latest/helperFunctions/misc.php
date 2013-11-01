<?php
/**
 * EngineAPI Helper Functions - misc
 * @package Helper Functions\misc
 */

/**
 * Determines if a function exists
 *
 * @todo fix me - There has to be a cleaner way to do this with native functions
 * @param string $param1
 * @param null $param2
 * @return bool
 */
function functionExists($param1,$param2=null) {
	$langConstructs = array("die", "echo", "empty", "exit", "eval", "include", "include_once", "isset", "list", "print", "require", "require_once", "unset");

	// 2 params provided, assume class
	if (!isnull($param2)) {
		return(method_exists($param1,$param2));
	}

	// Ignore everything that isn't a string, from this point on
	if (!is_string($param1)) {
		return(FALSE);
	}

	// check if function exists
	if (function_exists($param1) === TRUE) {
		return(TRUE);
	}

	// Check to see if it is an object being passed as a string.
	// if so, assume object
	$items = explode("::",$param1);
	if (count($items) == 2) {
		return(method_exists($items[0],$items[1]));
	}

	$items = explode("->",$param1);
	if (count($items) == 2) {
		return(method_exists($items[0],$items[1]));
	}

	// check to see if it is a language construct
	if (in_array($param1,$langConstructs)) {
		return(TRUE);
	}

	return(FALSE);
}

/**
 * Determine the calling function from the backtrace
 * @return string
 */
function callingFunction(){
	$backtrace = debug_backtrace();
	return isset($backtrace[2]['function'])
		? (string)$backtrace[2]['function']
		: 'unknown';
}

/**
 * Determine the calling line from the backtrace
 * @return string
 */
function callingLine(){
	$backtrace = debug_backtrace();
	return isset($backtrace[1]['line'])
		? (string)$backtrace[1]['line']
		: 'unknown';
}

/**
 * Determine the calling file from the backtrace
 * @param bool $basename
 *        If TRUE, return the base name of the file [Default: FALSE]
 * @return string
 */
function callingFile($basename=FALSE){
	$backtrace = debug_backtrace();
	if(isset($backtrace[1]['file'])){
		return $basename
			? (string)basename($backtrace[1]['file'])
			: (string)$backtrace[1]['file'];
	}else{
		return 'unknown';
	}
}

/**
 * Return key->value attribute pairs from a string that is in the form of 'key1="val1" key2="val2"'
 *
 * @param $attpairs
 * @return array
 */
function attPairs($attpairs) {

	$attPairs  = explode("\" ",$attpairs);

	$temp = array();

	foreach ($attPairs as $pair) {
		if (empty($pair)) {
			continue;
		}
		list($attribute,$value) = explode("=",$pair);
		$temp[trim($attribute)] = trim(str_replace("\"","",$value));
	}

	return($temp);
}

/**
 * Remove a variable from the query string
 *
 * @param $qs
 * @param $var
 * @return string
 */
function removeQueryStringVar($qs, $var) {

	
	if ($qs[0] == "?") {
		$qs     = substr($qs, 1);
		$qmTest = TRUE;
	}
	else {
		$qmTest = FALSE;
	}


	if ($qs[strlen($qs)-1] == "&") {
		$qs     = substr($qs, 0, -1);
		$ampTest = TRUE;
	}
	else {
		$ampTest = FALSE;
	}

	parse_str($qs, $output);
	if (array_key_exists($var, $output)) unset($output[$var]);

	return (($qmTest)?"?":"").http_build_query($output).(($ampTest)?"&":"");
}

/**
 * Recursively insert a file from the filesystem
 *
 * @todo Check for Performance / Cleanup
 * @param $file
 * @param string $type
 *        php  - Includes the target file (and executes)
 *        url  - Returns a formatted URL
 *        text - Includes the contents of the target file (no execute)
 * @param string $regex
 *        Regular expression to evaluate against $condition
 * @param string $condition
 *        The key of $_SERVER to use as a condition.
 * @param bool $caseInsensitive
 *        Adds case-insensitive flag to the regex passed via $regex
 * @return bool|string
 */
function recurseInsert($file,$type="php",$regex=NULL,$condition="REQUEST_URI",$caseInsensitive=TRUE) {

	$engine     = EngineAPI::singleton();
	$enginevars = enginevars::getInstance();

	if (!isnull($condition) && !isnull($regex)) {
		if (!isset($_SERVER[$condition])) {
			return(FALSE);
		}
		$regex = "/".$regex."/".(($caseInsensitive === TRUE)?"i":"");
		if (preg_match($regex,$_SERVER[$condition]) == 0) {
			return(FALSE);
		}
	}

	$tempParams         = array();
	$tempParams['file'] = $file;
	$tempParams['type'] = $type;
	$output             = $engine->execFunctionExtension("recurseInsert",$tempParams,"before");

	if ($output !== FALSE) {
		return($output);
	}

	// If $foundFile remains null, function returns false
	$foundFile = NULL;

	// Its possible we may be handed an array
	// Clean up the variables, carry on
	if(is_array($file)) {
		$type = $file[1];
		$temp = $file[2];
		unset($file);
		$file = $temp;
	}

	$cwd = getcwd();
	if ($cwd == "/") {
		if (!isnull($engine)) {
			$cwd = $engine->cwd;
		}
	}

	$cwd = explode("/",$cwd);

	for($I=sizeof($cwd);$I>0;$I--) {

		$cwdTemp = implode("/",$cwd);

		// Does the file exist? If yes, set $foundFile and
		// break out of the loop
		if(file_exists($cwdTemp."/$file")) {
			$foundFile = $cwdTemp."/$file";
			break;
		}

		array_pop($cwd);

		// Prevent it from recursing past the document root into the file system
		if($cwdTemp == $enginevars->get("documentRoot")) {
			break;
		}
	}

	// File wasn't found above, check for it in the template directory
	if (!isnull($engine) && isnull($foundFile)) {
		if(file_exists($engine->currentTemplate()."/$file")) {
			$foundFile = $engine->currentTemplate()."/$file";
		}
	}
	// If $engine is null, check $enginevars->get("currentTemplate")
	// *********** I think this check can be removed now ... ************** //
	else if (isnull($engine) && isnull($foundFile) && !isnull($enginevars->get("currentTemplate"))) {
		if(file_exists($enginevars->get("currentTemplate")."/$file")) {
			$foundFile = $enginevars->get("currentTemplate")."/$file";
		}
	}

	// Deal with the file, if $foundFile has been set.
	if (!isnull($foundFile)) {

		if ($type == "php") {
			include($foundFile);

			if(isset($dbTables)) {
				global $dbt;
				$dbt = $dbTables;
			}
			return("");
		}
		elseif ($type == "url") {
			$cwdTemp = str_replace($enginevars->get("documentRoot"),"",$cwdTemp."/");
			$url = $enginevars->get("WVULSERVER")."/".$cwdTemp.$file;
			return($url);
		}
		elseif ($type == "text") {
			$includeText = file_get_contents($foundFile);
			return($includeText);
		}
		// else {
		// 	return("Unsupported Insert Type.");
		// }

	}



	$output                        = FALSE;

	$tempParams                    = array();
	$tempParams['file']            = $file;
	$tempParams['type']            = $type;
	$tempParams['regex']           = $regex;
	$tempParams['condition']       = $condition;
	$tempParams['caseInsensitive'] = $caseInsensitive;

	// $arrayPrint = debug::obsafe_print_r($tempParams, TRUE);
	// $fh = fopen("/tmp/modules.txt","a");
	// fwrite($fh,"\n\n=====recurseInsert Begin =========\n\n");
	// fwrite($fh,$arrayPrint);
	// fwrite($fh,"\n\n=====recurseInsert END =========\n\n");
	// fclose($fh);


	$output                        = $engine->execFunctionExtension("recurseInsert",$tempParams);

	return($output);

}

/**
 * Generate the correct HTML to link a phone number for mobile browsers
 *
 * @todo Remove deprecated webHelper_() function calls
 * @todo Look at cleaning up / rewriting
 * @param $attPairs
 *        An array of params
 *        phone   - The Phone number to rewrite
 *        display - TRUE to display???
 * @return string
 */
function linkPhone($attPairs) {

	if (!isset($attPairs['phone'])) {
		return webHelper_errorMsg("No phone number provided");
	}

	$phone   = $attPairs['phone'];
	$display = NULL;

	if (isset($attPairs['display'])) {
		$display = $attPairs['display'];
	}

	$phoneRegex = "/^\d-\d{3}-\d{3}-\d{4}$/";
	$match = preg_match($phoneRegex,$phone);

	if ($match == 0) {
		return webHelper_errorMsg("Phone number must be in format of: x-xxx-xxx-xxxx");
	}

	$output = '<span class="phoneNumber">';
	if (engine_isMobileBrowser()) {
		$output .= '<a href="tel:'.$phone.'">';
		$output .= (isnull($display))?$phone:$display;
		$output .= "</a>";
	}
	else {
		$output .= (isnull($display))?$phone:$display;
	}

	$output .= "</span>";

	return($output);
}

/**
 * Generate a clean, pretty, file size
 * @param $filesize
 *        The filesize to format in Bytes
 * @param int $base
 *        The base to use for the calculation.
 *        (eq: how many bytes in a Kilobyte) [Default: 1000]
 * @return string
 */
function displayFileSize($filesize,$base=1000){

	if (is_numeric($filesize)) {
		$step = 0;
		$abbrev = array('Byte','KB','MB','GB','TB','PB');

		while (($filesize / $base) > 0.9) {
			$filesize /= $base;
			$step++;
		}

		return round($filesize,2).' '.$abbrev[$step];
	}
	else {
		return 'NaN';
	}
}

/**
 * Casts a given input as a new type
 *
 * @see http://us2.php.net/manual/en/function.settype.php
 * @param mixed $input
 * @param string $cast
 * @return mixed
 */
function castAs($input,$cast){
	$castable = array('boolean','bool','integer','int','float','double','string','array','object','null');
	$cast     = trim(strtolower($cast));
	if(in_array($cast, $castable) and settype($input,$cast)){
		return $input;
	}else{
		trigger_error(sprintf("Failed to cast! (%s to %s)", gettype($input), $cast), E_USER_WARNING);
		return null;
	}
}

?>
