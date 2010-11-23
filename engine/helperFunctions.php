<?php

function printENV($engine=NULL) {

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

//Check for Performance
function recurseInsert($file,$type="php",$engine=NULL) {

	global $engineVars;

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


// Shortens mysql_real_escape_string to mres. makes typing a touch easier, plus if we ever 
// want to use something other than mysql_real_escape_string, its easy to switch.
function mres($var) {
	$var = mysql_real_escape_string($var);
	return($var);
}

//Shamelessly stolen from php.net
//
// Returns a cleaned variable for insertion into mysql
function dbSanitize($var, $quotes = FALSE) {
	//run each array item through this function (by reference)
    if (is_array($var)) {         
        foreach ($var as &$val) {
            $val = dbSanitize($val);
        }
    }
	//clean strings
    else if (is_string($var)) {
        $var = mres($var);
        if ($quotes) {
            $var = "'". $var ."'";
        }
    }
	//convert null variables to SQL NULL
    else if (isnull($var)) {   
        $var = "NULL";
    }
	//convert boolean variables to binary boolean
    else if (is_bool($var)) {   
        $var = ($var) ? 1 : 0;
    }
    return $var;
}

function htmlSanitize($var, $quotes=ENT_QUOTES, $charSet="UTF-8", $doubleEncode=TRUE) {

	if(!isset($var)) {
		return(FALSE);
	}

	//run each array item through this function (by reference)
    if (is_array($var)) {         
        foreach ($var as &$val) {
            $val = htmlSanitize($val);
        }
    }
	else {
		$var = htmlentities($var,$quotes,$charSet,$doubleEncode);
	}
	
	return($var);

}

function is_empty($v,$strict=TRUE) {
	
	if (!isset($v)) {
		return(TRUE);
	}
	if (isnull($v,$strict)) {
		return(TRUE);
	}
	if ($v === FALSE) {
		return(TRUE);
	}
	
	if (is_array($v) && empty($v)) {
		return(TRUE);
	}
	else if (is_array($v)) {
		// return array before trim is hit
		return(FALSE);
	}
	
	$v = trim($v);
	
	if ($v == "0" || (is_int($v) && $v == 0)) {
		return(FALSE);
	}
	
	if (empty($v)) {
		return(TRUE);
	}
	
	return(FALSE);
}

function isnull($var,$strict=TRUE) {
	if ($strict === FALSE) {
		return(is_null($var));
	}

	if (is_array($var)) {
		return(FALSE);
	}
	
	if (is_string($var)) {
		if (strtolower($var) == "null") {
			return(TRUE);
		}
	}
	else if ($var === NULL) {
		return(TRUE);
	}
	
	return(FALSE);
}

function is_odd($number) {
	
	if ($number&1) {
		return(TRUE);
	}
	
	return(FALSE);
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

function buildECMSArray($array) {
	global $engineVars;
	
	$output = "";
	if(is_array($array)) {
		$output = implode($engineVars['delim'],$array);
	}
	else {
		$output = $array;
	}
	return($output);
}

function wordSubStr($str,$wordCount) {
	
	$str = explode(" ",$str);
	$str = array_slice($str,0,$wordCount);
	$str = join(' ',$str);
	
	return($str);
}

function validURL($url) {
	
	// Regex stolen from
	// http://phpcentral.com/208-url-validation-in-php.html
	
	$urlregex = "/^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@\/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$/i";
	
	$match = preg_match($urlregex,$url);
	
	if ($match == 1) {
		return(TRUE);
	}
	
	return(FALSE);
	
}

// Matches the following
// [+]CountryCode Delimiter [(]AreaCode[)] Delimiter Exchange Delimiter Number [x|ext|extension]delimiter extension
// country code is optional (plus sign is optional)
// Area code is required
// Delimiters between numbers are required, and can be "-", ".", or " "
// Extension is optional, delimiter is required and can be "." or ":"
function validPhoneNumber($number) {
	$phoneRegex = "/^\s*(\+?\d+\s*(\-|\ |\.)\s*)?\(?\d{3}\)?\s*(\-|\ |\.)\s*\d{3}\s*(\-|\ |\.)\s*\d{4}(\s*(\s|ext(\.|\:)?|extension\:?|x(\.|\:)?)\s*\d+)?$/";
	$match = preg_match($phoneRegex,$number);
	
	if ($match == 1) {
		return(TRUE);
	}
	
	return(FALSE);
}

// Checks against regex for valid IP range. doesn't break apparent octets and 
// look at values (999.999.999.999 will return correctly)
function validIPAddr($ip) {
	$ipRegex = "/((\d{1,3}(-\d{1,3})?)|\*)\.((\d{1,3}(-\d{1,3})?)|\*)\.((\d{1,3}(-\d{1,3})?)|\*)\.((\d{1,3}(-\d{1,3})?)|\*)/";	
	$match = preg_match($ipRegex,$ip);
	
	if ($match == 1) {
		return(TRUE);
	}
	
	return(FALSE);
}

function stripCarriageReturns($string) {

	global $engineVars;

	if ($engineVars['stripCarriageReturns'] === TRUE) {
		$string = str_replace("\r","",$string);
	}

	return($string);	
}

?>