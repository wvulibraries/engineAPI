<?php

function printENV() {

	global $engineVars;
	
	print "<p><strong>Engine Variables:</strong>:<br />";
	foreach ($engineVars as $key => $value) {
		print "$key : <em>$value</em> <br />";
	}
	print "</p>";
	
	print "<p><strong>Local Variables:</strong>:<br />";
	foreach ($engineVars['localVars'] as $key => $value) {
		print "$key : <em>$value</em> <br />";
	}
	print "</p>";
	
	return;
}

//Check for Performance
function recurseInsert($file,$type="php") {

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

	$cwd = split("/",getcwd());

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
	if(isnull($foundFile) && file_exists($engineVars['currentTemplate']."/$file")) {
		$foundFile = $engineVars['currentTemplate']."/$file";
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

function tempDate($attPairs) {

	if (isset($attPairs['time'])) {
		return(date($attPairs['format'],$attPairs['time']));
	}

	return(date($attPairs['format']));
	
}


function phpSelf() {

	$phpself             = array_pop(explode("/",$_SERVER['SCRIPT_FILENAME']));
	$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'],$phpself)).$phpself;
	
	return;
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

function isnull($var,$strict=TRUE) {
	if ($strict === FALSE) {
		return(is_null($var));
	}

	if (is_array($var)) {
		return(FALSE);
	}
	
	if (strtolower($var) == "null") {
		return(TRUE);
	}
	/* this breaks expected PHP behavior 
	else if (!isset($var)) {
		return(FALSE);
	}
	*/
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

/* $array         = Array to be sorted
 * $col           = The column in a multi dimensional array.
 * $ignoreSymbols = BOOL, default to TRUE. If True, ignores all symbols in the string while sorting
 * $ignoreCase    = BOOL, default to TRUE. Sorts case insensitively.
*/
function naturalSort($array,$col=NULL,$ignoreSymbols=TRUE,$ignoreCase=TRUE) {

	global $engineVars;

	$engineVars['sortCol']           = $col;
	$engineVars['sortIgnoreSymbols'] = $ignoreSymbols;
	$engineVars['sortIgnoreCase']    = $ignoreCase;
	
	usort($array,"naturalSortCmp");

	$engineVars['sortCol']           = NULL;
	$engineVars['sortIgnoreSymbols'] = NULL;
	$engineVars['sortIgnoreCase']    = NULL;
	
	return($array);
}

/* Sort function called by natrualSort() */
function naturalSortCmp($a, $b) {
	
	global $engineVars;
	
	if (!isnull($engineVars['sortCol'])) {
		$aString = $a[$engineVars['sortCol']];
		$bString = $b[$engineVars['sortCol']];
	}
	else {
		$aString = $a;
		$bString = $b;
	}
	
	if($engineVars['sortIgnoreSymbols'] === TRUE) {
		$aString = ereg_replace("[^A-Za-z0-9]", "", $aString);
		$bString = ereg_replace("[^A-Za-z0-9]", "", $bString);
	}
	
	if($engineVars['sortIgnoreCase'] === TRUE) {
		return strnatcasecmp($aString,$bString);
	}
	
	return strnatcmp($aString, $bString);
}

function validateEmailAddr($email) {
	
	if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
	    return(TRUE);
	}
	
	return(FALSE);
}

function kwic($str1,$str2) {
	
	$kwicLen = strlen($str1);

	$kwicArray = array();
	$pos       = 0;
	$count     = 0;

	while($pos !== FALSE) {
		$pos = stripos($str2,$str1,$pos);
		if($pos !== FALSE) {
			$kwicArray[$count]['kwic'] = substr($str2,$pos,$kwicLen);
			$kwicArray[$count++]['pos']  = $pos;
			$pos++;
		}
	}

	for($I=count($kwicArray)-1;$I>=0;$I--) {
		$kwic = '<span class="kwic">'.$kwicArray[$I]['kwic'].'</span>';
		$str2 = substr_replace($str2,$kwic,$kwicArray[$I]['pos'],$kwicLen);
	}
		
	return($str2);
}

?>