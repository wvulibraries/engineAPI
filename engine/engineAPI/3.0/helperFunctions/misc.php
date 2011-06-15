<?php

function callingFunction() {
	
	$backtrace = debug_backtrace();
	
	return($backtrace[2]['function']);
	
}

function callingFile($basename=FALSE) {
	
	$backtrace = debug_backtrace();
	
	$file = $backtrace[1]['file'];
	if ($basename === TRUE) {
		$file = basename($backtrace[1]['file']);
	}
	
	return($file);
	
}

// return attribute pairs
function attPairs($attpairs) {
	$attPairs  = split("\" ",$attpairs);

	foreach ($attPairs as $pair) {
		if (empty($pair)) {
			continue;
		}
		list($attribute,$value) = split("=",$pair,2);
		$temp[$attribute] = str_replace("\"","",$value);
	}

	return($temp);
}

//Check for Performance
// Engine is no longer used as a parameter. left for backwards compatibility
function recurseInsert($file,$type="php",$engine=NULL) {

	global $engineVars;

	$engine = EngineAPI::singleton();

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
			//return("File FOund: '".$foundFile."'");
			break;
		}

		array_pop($cwd);

		// Prevent it from recursing past the document root into the file system
		if($cwdTemp == $engineVars['documentRoot']) {
			//return("DocRoot: '".$engineVars['documentRoot']."'");
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
	else if (isnull($engine) && isnull($foundFile) && !isnull($engineVars['currentTemplate'])) {
		if(file_exists($engineVars['currentTemplate']."/$file")) {
			$foundFile = $engineVars['currentTemplate']."/$file";
		}
	}
	
	// Deal with the file, if $foundFile has been set.
	if (!isnull($foundFile)) {

		if ($type == "php") {
			
			/*$foo = get_defined_vars();
			echo "<pre>";
			print_r($foo);
			echo "</pre>";*/
			
			include($foundFile);
			
			/*$foo = get_defined_vars();
			echo "<pre>";
			print_r($foo);
			echo "</pre>";*/
			
			if(isset($dbTables)) {
				global $dbt;
				$dbt = $dbTables;
			}
			return(TRUE);
		}
		elseif ($type == "url") {
			$cwdTemp = str_replace($engineVars['documentRoot'],"",$cwdTemp);
			$url = $engineVars['WVULSERVER'].$cwdTemp."/".$file;
			return($url);
		}
		elseif ($type == "text") {
			$includeText = file_get_contents($foundFile);
			return($includeText);
		}
		else {
			return("Unsupported Insert Type.");
		}

	}
	
	return(FALSE);
	
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

?>