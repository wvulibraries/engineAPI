<?php

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

function stripCarriageReturns($string) {

	global $engineVars;

	if ($engineVars['stripCarriageReturns'] === TRUE) {
		$string = str_replace("\r","",$string);
	}

	return($string);	
}

// Turns an array into a string, using a definable delimiter. 
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

?>