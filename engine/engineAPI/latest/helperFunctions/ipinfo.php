<?php
/**
 * General user functions
 * @package EngineAPI\ipClass
 */

class ipAddr {

	/**
	 * $ipRanges is an array of valid IP ranges, same type that is used for security
	 * $checkIP is option, if provided it checks against checkip, otherise it uses the clients remote_addr
	 *
	 * returns true if the clients IP is in this range, otherwise false
	 *
	 * @param $ipRanges IP range(s) to check against
	 * @param string|null $checkIP The IP address to check
	 * @return bool
	 */
	public static function rangeCheckArray($ipRanges,$checkIP = NULL) {

		if(isnull($checkIP) && isset($_SERVER['REMOTE_ADDR'])) {
			$checkIP = $_SERVER['REMOTE_ADDR'];
		}

		// Break up the clients IP Address
		$remoteAddr = array();
		$remoteAddr = explode(".",$checkIP);

		$ipFound = FALSE;

		foreach ($ipRanges as $key=>$ip) {
			$ipFound = self::rangeCheck($ip,$checkIP);
			if ($ipFound === TRUE) {
				break;
			}
		}

		if ($ipFound === TRUE) {
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * userInfo IP Range Check
	 *
	 * @param string $ip
	 * @param string $checkIP The IP address to check
	 * @return bool
	 */
	public static function rangeCheck($ip,$checkIP = NULL) {

		if(isnull($checkIP) && isset($_SERVER['REMOTE_ADDR'])) {
			$checkIP = $_SERVER['REMOTE_ADDR'];
		}

		if ($checkIP == $ip) {
			return TRUE ;
		}

		// Break up the clients IP Address
		$remoteAddr = array();
		$remoteAddr = explode(".",$checkIP);

		$ipFound = FALSE;

		$ipQuads = array();
		$ipQuads = explode(".",$ip);

		for ($I = 0;$I <= 3;$I++) {

			if (preg_match("/\-/",$ipQuads[$I])) {

			// Contains a range of numbers
				list($min,$max) = explode("-",$ipQuads[$I]);

				if ($remoteAddr[$I] < $min || $remoteAddr[$I] > $max) {
					break;
				}
			}
			elseif ($ipQuads[$I] == "*") {
			// Quad is a wild Character
			//continue;
			}
			else {
			// Quad is an exact number
				if ($ipQuads[$I] != $remoteAddr[$I]) {
					break;
				}
			}

			if ($I == 3) {
				$ipFound = TRUE;
			}
		}

		return $ipFound ;

	}

	/**
	 * IP range check
	 *
	 * @param $ipRanges
	 * @param string|null $checkIP The IP address to check
	 * @return bool
	 */
	public static function check($ipRanges,$checkIP = NULL) {
		if(is_array($ipRanges)) {
			return self::rangeCheckArray($ipRanges,$checkIP);
		}
		return self::rangeCheck($ipRanges,$checkIP);
	}

	/**
	 * Determine if the user is on or off campus
	 *
	 * @param string $checkIP The IP to check, defaults to $_SERVER['REMOTE_ADDR']
	 * @return bool
	 */
	function onsite($checkIP = NULL) {

		$enginevars = enginevars::getInstance();

		if(isnull($checkIP) && isset($_SERVER['REMOTE_ADDR'])) {
			$checkIP = $_SERVER['REMOTE_ADDR'];
		}

		$ipFound = self::check($enginevars->get("onCampus"),$checkIP);

		if ($ipFound === TRUE) {
			return TRUE;
		}

		return FALSE;

	}

}

/**
 * @deprecated
 * @see ipAddr::rangeCheckArray()
 */
function userInfoIPRangeCheckArray($ipRanges,$checkIP = NULL) {
	deprecated();
	return ipAddr::rangeCheckArray($ipRanges,$checkIP);
}

/**
 * @deprecated
 * @see ipAddr::rangeCheck()
 */
function userInfoIPRangeCheck($ip,$checkIP = NULL) {
	deprecated();
	return ipAddr::rangeCheck($ip,$checkIP);

}

/**
 * @deprecated
 * @see ipAddr::check()
 */
function ipRangeCheck($ipRanges,$checkIP = NULL) {
	deprecated();
	return ipAddr::check($ipRanges,$checkIP);
}

/**
 * @deprecated
 * @see ipAddr::onsite()
 */
function onCampus($checkIP = NULL) {
	deprecated();
	return ipAddr::onsite($checkIP);

}

?>
