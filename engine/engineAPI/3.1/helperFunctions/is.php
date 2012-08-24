<?php

/**
 * determines if the passed argument is a function
 * swiped from http://us2.php.net/manual/en/function.create-function.php#96321
 * @param mixed $mixed function | method name to test
 * @return bool
 */
function is_function( &$mixed ) {
    if ( is_object( $mixed ) ) {
        return ( $mixed instanceof Closure );
    } elseif( is_string( $mixed ) ) {
        return function_exists( $mixed );
    } else {
        return false;
    }
	return FALSE;
}

/**
 * determine if value is Odd (not Even)
 * @param int $number
 * @return bool
 */
function is_odd($number) {

	if ($number&1) {
		return(TRUE);
	}

	return(FALSE);
}

/**
 * Replacement for is_int
 * Tests strings as well, returns true for an integer.
 * 12	TRUE  (String)
 * 5	TRUE  (INT)
 * 005	TRUE  (String)
 * 5.5	FALSE (FLOAT)
 * 5.5	FALSE (String)
 * 0.5	FALSE (String)
 * test	FALSE (String)
 * 1	FALSE (BOOLE Value TRUE)
 * 		FALSE (Empty string)
 *
 * @param mixed $int
 * @return bool
 */
function isint($int) {

	return validate::integer($int);

}

/**
 * Returns TRUE if string evaluates to "null" as well as normal cases. replacement for is_null()
 * @param mixed $var variable to evaluate
 * @param bool $strict if FALSE uses the built in is_null() php function
 */
function isnull($var,$strict=TRUE) {
	if($strict === FALSE){
		return (is_null($var));
	}

	if(is_array($var)){
		return (FALSE);
	}

	if(is_string($var) and strtolower($var) == "null"){
		return (TRUE);
	}
	elseif($var === NULL){
		return (TRUE);
	}

	return (FALSE);
}


/**
 * Alias for is_empty()
 * @param mixed $v
 * @param bool $strict
 * @return bool
 */
function isempty($v,$strict=TRUE) {
	return is_empty($v,$strict);
}

/**
 * Replacement for empty(). Does NOT return true when test variable is int 0 or string "0"
 * @param mixed $v
 * @param bool $strict
 * @return bool
 */
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

/**
 * Returns TRUE if we are running in CLI mode
 * @return bool
 */
function isCLI(){
    return (php_sapi_name() == 'cli' || (@is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}

/**
 * Determines if the current version of PHP is greater then the supplied value
 *
 * Since there is the potential for this function to be used in many places,
 * we'll cache the results of a given version in the static variable $_isPHP
 *
 * @param $version
 * @return bool
 * @see https://github.com/EllisLab/CodeIgniter/blob/develop/system/core/Common.php
 */
function isPHP($version){
    static $_isPHP;
    $version = (string)$version;
    if(!isset($_isPHP[$version])){
        $_isPHP[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
    }
    return $_isPHP[$version];
}

/**
 * Returns TRUE if the string is a PHP serialized string
 * @param $str
 * @return bool
 */
function isSerialized($str) {
	return ($str === 'b:0;' || @unserialize($str) !== FALSE);
}
?>