<?php
class validate {

	/**
	 * Mapping of available validators and their human-readable names
	 * Format: "method_name" => "Human readable name"
	 * @var array
	 */
	private static $availableMethods = array(
		"regexp"               => "Regular Expression",
		"phoneNumber"          => "Phone Number",
		"ipAddr"               => "ipAddr",
		"ipAddrRange"          => "ipAddrRange",
		"optionalURL"          => "URL (Optional)",
		"url"                  => "URL",
		"emailAddr"            => "Email",
		"internalEmailAddr"    => "Email (Internal)",
		"integer"              => "Integer",
		"integerSpaces"        => "Integer (w/ Spaces)",
		"alphaNumeric"         => "Alpha Numeric",
		"alphaNumericNoSpaces" => "Alpha/Numeric (No Spaces)",
		"alpha"                => "Alphabetic Only",
		"alphaNoSpaces"        => "Alphabetic Only (No Spaces)",
		"noSpaces"             => "No Spaces",
		"noSpecialChars"       => "No Special Characters",
		"date"                 => "date"
		);

	/**
	 * Returns the mapping array available validators
	 *
	 * @see self::$availableMethods
	 * @return array
	 */
	public static function validationMethods() {
		return(self::$availableMethods);
	}

	/**
	 * Returns true if if validationType is a valid validator
	 *
	 * @param $validationType
	 * @return bool
	 */
	public static function isValidMethod($validationType) {

		if (array_key_exists($validationType,self::validationMethods())) {
			return(TRUE);
		}

		return(FALSE);

	}

	/**
	 * Applies a supplied testName validation to members of a CSV string
	 *
	 * @todo This looks broken around line 'if(!self::$stringPart($stringPart)) return FALSE;'
	 * @todo Look at some code improvements to make it more stable
	 * @param string $testName
	 *        The validation method to apply to the members of @string
	 * @param string $string
	 *        A CSV of value(s) to which $testName validatioin is applied
	 * @return bool
	 */
	public static function csvValue($testName, $string){
        $testName = strtolower($testName);
        $classMethods = array_map('strtolower', get_class_methods(__CLASS__));
        if(!$classMethods) $classMethods = array();
        if(!in_array($testName, $classMethods)){
            errorHandle::newError(__METHOD__."() - Undefined validation test! ('$testName' isn't a valid test)", errorHandle::DEBUG);
            return FALSE;
        }else{
            $stringParts = explode(',', $string);
            foreach($stringParts as $stringPart){
                if(!self::$stringPart($stringPart)) return FALSE;
            }
            return TRUE;
        }
    }

	/**
	 * Validates against a regular expression
	 *
	 * @param string $regexp
	 *        The regular expression
	 * @param string $test
	 *        The value to test
	 * @return bool|null
	 *         Boolean: Regex matched or didn't match
	 *         Null: Regex returned an error
	 */
	public static function regexp($regexp,$test) {
		$match = @preg_match($regexp,$test);
		
		switch($match) {
			case 1:
				return(TRUE);
				break;
			case 0:
				return(FALSE);
				break;
			case FALSE:
				return(NULL);
				break;
			default:
				return(FALSE);
				break;
		}
		
		return(FALSE);
	}
	
	/**
	 * Validate as a phone number
	 * Roughly matches: [+]CountryCode Delimiter [(]AreaCode[)] Delimiter Exchange Delimiter Number [x|ext|extension]delimiter extension
	 *   - Area code is required
	 *   - Delimiters are required and can be '-', '.', or ' '
	 *   - Extension is optional as 'ext' or 'extension' followed by a delim of '.' or ':'
	 *
	 * @todo Could probably use a little work to accept other formats?
	 * @param $number
	 * @return bool|null
	 */
	public static function phoneNumber($number) {
		$phoneRegex = "/^\s*(\+?\d+\s*(\-|\ |\.)\s*)?\(?\d{3}\)?\s*(\-|\ |\.)\s*\d{3}\s*(\-|\ |\.)\s*\d{4}(\s*(\s|ext(\.|\:)?|extension\:?|x(\.|\:)?)\s*\d+)?$/";
		
		return(self::regexp($phoneRegex,$number));
	}
	
	/**
	 * Validate as an IP Address
	 *
	 * @param string $ip
	 * @return bool|null
	 */
	public static function ipAddr($ip) {
		$ipRegex = "/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/";
		return(self::regexp($ipRegex,$ip));
	}
	
	/**
	 * Validate as an IP range
	 *
	 * @param string $ip
	 * @return bool|null
	 */
	public static function ipAddrRange($ip) {
		$ipAddr  = "(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)";
        $ipGroup = "(?:$ipAddr|$ipAddr-$ipAddr|\*)";
        $ipRange = "(?:$ipGroup\.){3}$ipGroup";
		return(self::regexp("/^$ipRange$/",$ip));
	}

	/**
	 * Validate as a potential URL
	 * Allow just about anything, but if it appears to be a URL it must be a valid URL
	 *
	 * @param string $url
	 * @return bool|null
	 */
	public static function optionalURL($url) {
		$urlCheckRegex = "/^(https?|ftp|ssh|telnet)\:\/\/.+/";
		$urlTest       = self::regexp($urlCheckRegex,$url);

		if ($urlTest == 1) {
			return(self::url($url));
		}

		return(TRUE);
	}

	/**
	 * Validate as URL
	 *
	 * @param string $url
	 * @return bool|null
	 */
	public static function url($url) {
		
		// Regex stolen from
		// http://phpcentral.com/208-url-validation-in-php.html
		$urlregex = "/^(https?|ftp|ssh|telnet)\:\/\/([a-zA-Z0-9+!*(),;?&=\$_.-]+(\:[a-zA-Z0-9+!*(),;?&=\$_.-]+)?@)?[a-zA-Z0-9+\$_-]+(\.[a-zA-Z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-zA-Z0-9+\$_-]\.?)+)*\/?(\?[a-zA-Z+&\*\$_.-][a-zA-Z0-9;:@\/&%=+\*\$_.-]*)?(#[a-zA-Z_.-][a-zA-Z0-9+\*\$_.-]*)?\$/";

		return(self::regexp($urlregex,$url));
	}

	/**
	 * Validate as email address
	 *
	 * @see self::internalEmailAddr()
	 * @param string $email
	 * @param bool $internal
	 *        If true, also validate against self::internalEmailAddr()
	 * @return bool
	 */
	public static function emailAddr($email,$internal=FALSE) {

		if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
			if($internal) {
				if (self::internalEmailAddr($email) === TRUE) {
					return(TRUE);
				}
				return(FALSE);
			}
			return(TRUE);
		}

		return(FALSE);
	}

	/**
	 * Validate as an 'internal' email address
	 * Runs the email through regex's in $engineVars['internalEmails'] for a match
	 *
	 * @param string $email
	 * @return bool
	 */
	public static function internalEmailAddr($email) {
		global $engineVars;

		foreach ($engineVars['internalEmails'] as $key => $regex) {
			if(preg_match($regex,$email)) {
				return(TRUE);
			}	
		}

		return(FALSE);
	}

	/**
	 * Validate as an integer
	 * (ex: '1234')
	 *
	 * @param int $test
	 * @return bool|null
	 */
	public static function integer($test) {

		if (is_numeric($test) === FALSE) {
			return(FALSE);
		}

		if ((int)$test != $test) {
			return(FALSE);
		}

		$regexp = "/^[0-9]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as an integer with spaces allowed
	 * (ex: '12 34')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public static function integerSpaces($test) {
		$regexp = "/^[0-9\ ]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as alpha-numerical with spaces allowed
	 * (ex: 'abc123def456')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public static function alphaNumeric($test) {
		$regexp = "/^[a-zA-Z0-9\-\_\ ]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as alpha-numerical
	 * (ex: 'abc123')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public static function alphaNumericNoSpaces($test) {
		$regexp = "/^[a-zA-Z0-9\-\_]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as alphabetical with spaces allowed
	 * (ex: 'abc def')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public static function alpha($test) {
		$regexp = "/^[a-zA-Z\ ]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as alphabetical
	 * (ex: 'abcdef')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public static function alphaNoSpaces($test) {
		$regexp = "/^[a-zA-Z]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as text with no spaces
	 * (ex: fails for 'abc 123')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public static function noSpaces($test) {
		$regexp = "/^[^\ ]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as no special characters
	 *
	 * @param $test
	 * @return bool|null
	 */
	public static function noSpecialChars($test) {
		$regexp = "/^[^\W]+$/";
		return(self::regexp($regexp,$test));
	}

	/**
	 * Validates as a valid date format
	 *
	 * @param string $test
	 * @return bool
	 */
	public static function date($test) {
		// Parse the date
		$parseData = date_parse($test);

		// If there were any errors or warnings during parse, date failed!
		if($parseData['warning_count'] || $parseData['error_count']) return FALSE;

		// Now we need to check both to catch both MM/DD/YYYY and DD/MM/YYYY
		$chkDate1 = checkdate($parseData['month'],$parseData['day'],$parseData['year']);
		$chkDate2 = checkdate($parseData['day'],$parseData['month'],$parseData['year']);
		if(!$chkDate1 and !$chkDate2) return FALSE;

		// If we get here, the date is valid
		return TRUE;
	}

	/**
	 * Validates as serialized string
	 *
	 * @see http://us1.php.net/manual/en/function.unserialize.php#85097
	 * @param $str
	 * @return bool
	 */
	public static function serialized($str) {
		return ($str == serialize(false) || @unserialize($str) !== false);
	}
	
}

?>