<?php
/**
 * EngineAPI Helper Functions - validate
 * @package Helper Functions\validate
 */

/**
 * Validate a phone number
 *
 * @depreciated
 * @see validate::phoneNumbe()
 * @param $number
 * @return bool
 */
function validPhoneNumber($number) {
	return(validate::phoneNumber($number));
}

/**
 * Validate an IP address
 *
 * @depreciated
 * @see validate::ipAddr()
 * @param $ip
 * @return bool|null
 */
function validIPAddr($ip) {
	return(validate::ipAddr($ip));
}

/**
 * Validate URL (if there is one)
 *
 * @depreciated
 * @see validate::optionalURL()
 * @param $url
 * @return bool|null
 */
function validOptionalURL($url) {
	return(validate::optionalURL($url));
}

/**
 * Validate URL
 *
 * @depreciated
 * @see validate::validURL()
 * @param $url
 * @return bool|null
 */
function validURL($url) {
	return(validate::url($url));
}

/**
 * Validate Email address
 *
 * @depreciated
 * @see validate::emailAddr()
 * @param $email
 * @param bool $internal
 * @return bool
 */
function validateEmailAddr($email,$internal=FALSE) {
	return(validate::emailAddr($email,$internal));
}	


?>