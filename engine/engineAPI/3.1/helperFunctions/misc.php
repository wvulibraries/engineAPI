<?php

// Determines if a function exists
function functionExists($param1,$param2=null) {

	$langConstructs = array("die",
							"echo", "empty", "exit", "eval",
							"include", "include_once", "isset",
							"list",
							"print",
							"require", "require_once",
							"unset"
							);

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

function callingFunction(){
	$backtrace = debug_backtrace();
	$fn = (isset($backtrace[2]['function'])) ? $backtrace[2]['function'] : 'unknown';
	return $fn;
}
function callingLine(){
	$backtrace = debug_backtrace();
	$ln = (isset($backtrace[1]['line'])) ? $backtrace[1]['line'] : 'unknown';
	return $ln;
}
function callingFile($basename=FALSE){
	$backtrace = debug_backtrace();
	if(!isset($backtrace[1]['file'])) return 'unknown';

	$file = $backtrace[1]['file'];
	if ($basename === TRUE) {
		$file = basename($backtrace[1]['file']);
	}
	return($file);
}

// return attribute pairs
function attPairs($attpairs) {

	$attPairs  = split("\" ",$attpairs);

	$temp = array();

	foreach ($attPairs as $pair) {
		if (empty($pair)) {
			continue;
		}
		list($attribute,$value) = split("=",$pair,2);
		$temp[trim($attribute)] = trim(str_replace("\"","",$value));
	}

	return($temp);
}

function removeQueryStringVar($qs, $var) {
	$qs = preg_replace('/(.*)(?|&)'.$var.'=[^&]+?(&)(.*)/i', '$1$2$4', $qs.'&');
	$qs = substr($qs, 0, -1);
	return $qs;
}

//Check for Performance
// Engine is no longer used as a parameter. left for backwards compatibility
function recurseInsert($file,$type="php",$regex=NULL,$condition="REQUEST_URI",$caseInsensitive=TRUE) {

	global $engineVars;

	$engine = EngineAPI::singleton();

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

	$cwd = split("/",$cwd);

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
		if($cwdTemp == $engineVars['documentRoot']) {
			break;
		}
	}

	// File wasn't found above, check for it in the template directory
	if (!isnull($engine) && isnull($foundFile)) {
		if(file_exists($engine->currentTemplate()."/$file")) {
			$foundFile = $engine->currentTemplate()."/$file";
		}
	}
	// If $engine is null, check $engineVars['currentTemplate']
	// *********** I think this check can be removed now ... ************** //
	else if (isnull($engine) && isnull($foundFile) && !isnull($engineVars['currentTemplate'])) {
		if(file_exists($engineVars['currentTemplate']."/$file")) {
			$foundFile = $engineVars['currentTemplate']."/$file";
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
			$cwdTemp = str_replace($engineVars['documentRoot'],"",$cwdTemp."/");
			$url = $engineVars['WVULSERVER']."/".$cwdTemp.$file;
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

// If the browser is a mobile device, make the phone number a clickable link
// Phone number must be in the format of x-xxx-xxx-xxxx ... fairly limited because that is what is
// required for a mobile phone to dial it.
// display is optional. if its there, that's how the number will be displayed.
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
 * @param mixed $input
 * @param string $cast
 * @return mixed
 * @see http://us2.php.net/manual/en/function.settype.php
 */
function castAs($input,$cast){
	$castable = array('boolean','bool','integer','int','float','double','string','array','object','null');
	$cast     = trim(strtolower($cast));
	if(in_array($cast, $castable) and settype($input,$cast)){
		return $input;
	}else{
		// Trigger Error?
		return null;
	}
}

?>