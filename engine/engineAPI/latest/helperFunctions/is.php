<?php

/**
 * Determines if the passed argument is a function
 *
 * @see http://us2.php.net/manual/en/function.create-function.php#96321
 * @param mixed $mixed
 *        Method name to test
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
 * Determine if value is Odd (not Even)
 *
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
 * Replacement for is_int()
 * Tests strings as well, returns true for an integer.
 *
 * @param mixed $var
 *        The input to test
 * @return bool
 */
function isint($var) {
	return (bool)validate::integer($var);
}

/**
 * Returns TRUE if string evaluates to "null" as well as normal cases. replacement for is_null()
 * @param mixed $var variable to evaluate
 * @param bool $strict if FALSE uses the built in is_null() php function
 */

/**
 * Replacement for is_null()
 * Returns TRUE if string evaluates to "null" as well as normal cases.
 *
 * @param mixed $var
 *        The input to test
 * @param bool $strict
 *        If False, use native is_null() [Defaults to TRUE]
 * @return bool
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
 *
 * @see is_empty()
 * @param mixed $var
 *        The input to test
 * @param bool $strict
 *        Pass-through for isnull() [Defaults to TRUE]
 * @return bool
 */
function isempty($var,$strict=TRUE) {
	return is_empty($var, $strict);
}

/**
 * Replacement for empty().
 * Does NOT return true when test variable is int 0 or string "0"
 *
 * @param mixed $var
 *        The input to test
 * @param bool $strict
 *        Pass-through for isnull() [Defaults to TRUE]
 * @return bool
 */
function is_empty($var,$strict=TRUE) {

	if (!isset($var)) {
		return(TRUE);
	}
	if (isnull($var,$strict)) {
		return(TRUE);
	}
	if ($var === FALSE) {
		return(TRUE);
	}

	if (is_array($var) && empty($var)) {
		return(TRUE);
	}
	else if (is_array($var)) {
		// return array before trim is hit
		return(FALSE);
	}

	$var = trim($var);

	if ($var == "0" || (is_int($var) && $var == 0)) {
		return(FALSE);
	}

	if (empty($var)) {
		return(TRUE);
	}

	return(FALSE);
}

/**
 * Returns TRUE if we are running in CLI mode
 *
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
 * @see https://github.com/EllisLab/CodeIgniter/blob/develop/system/core/Common.php
 * @param $version
 * @return bool
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
 *
 * @param $str
 * @return bool
 */
function isSerialized($str) {
	return ($str === 'b:0;' || @unserialize($str) !== FALSE);
}
?>