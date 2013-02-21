<?php
class validate {

	// method names and their 'human readable' versions
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

	// returns all the valid validation methods
	public static function validationMethods() {
		return(self::$availableMethods);
	}

	public static function isValidMethod($validationType) {

		if (array_key_exists($validationType,self::validationMethods())) {
			return(TRUE);
		}

		return(FALSE);

	}

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
	
	// Matches the following
	// [+]CountryCode Delimiter [(]AreaCode[)] Delimiter Exchange Delimiter Number [x|ext|extension]delimiter extension
	// country code is optional (plus sign is optional)
	// Area code is required
	// Delimiters between numbers are required, and can be "-", ".", or " "
	// Extension is optional (ext or extension), delimiter, after word, is required and can be "." or ":"
	public static function phoneNumber($number) {
		$phoneRegex = "/^\s*(\+?\d+\s*(\-|\ |\.)\s*)?\(?\d{3}\)?\s*(\-|\ |\.)\s*\d{3}\s*(\-|\ |\.)\s*\d{4}(\s*(\s|ext(\.|\:)?|extension\:?|x(\.|\:)?)\s*\d+)?$/";
		
		return(self::regexp($phoneRegex,$number));
	}
	
	// Checks against regex for valid IP. 
	public static function ipAddr($ip) {
		$ipRegex = "/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/";
		return(self::regexp($ipRegex,$ip));
	}
	
	// Checks against regex for valid IP Range.
	public static function ipAddrRange($ip) {
		$ipAddr  = "(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)";
        $ipGroup = "(?:$ipAddr|$ipAddr-$ipAddr|\*)";
        $ipRange = "(?:$ipGroup\.){3}$ipGroup";
		return(self::regexp("/^$ipRange$/",$ip));
	}

	// Allow just about anything, but if it appears to be a URL it must be a valid URL
	public static function optionalURL($url) {
		$urlCheckRegex = "/^(https?|ftp|ssh|telnet)\:\/\/.+/";
		$urlTest       = self::regexp($urlCheckRegex,$url);

		if ($urlTest == 1) {
			return(self::url($url));
		}

		return(TRUE);
	}
	
	public static function url($url) {
		
		// Regex stolen from
		// http://phpcentral.com/208-url-validation-in-php.html
		$urlregex = "/^(https?|ftp|ssh|telnet)\:\/\/([a-zA-Z0-9+!*(),;?&=\$_.-]+(\:[a-zA-Z0-9+!*(),;?&=\$_.-]+)?@)?[a-zA-Z0-9+\$_-]+(\.[a-zA-Z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-zA-Z0-9+\$_-]\.?)+)*\/?(\?[a-zA-Z+&\*\$_.-][a-zA-Z0-9;:@\/&%=+\*\$_.-]*)?(#[a-zA-Z_.-][a-zA-Z0-9+\*\$_.-]*)?\$/";

		return(self::regexp($urlregex,$url));
	}
	
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

	public static function internalEmailAddr($email) {
		global $engineVars;

		foreach ($engineVars['internalEmails'] as $key => $regex) {
			if(preg_match($regex,$email)) {
				return(TRUE);
			}	
		}

		return(FALSE);
	}
	
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
	
	public static function integerSpaces($test) {
		$regexp = "/^[0-9\ ]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function alphaNumeric($test) {
		$regexp = "/^[a-zA-Z0-9\-\_\ ]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function alphaNumericNoSpaces($test) {
		$regexp = "/^[a-zA-Z0-9\-\_]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function alpha($test) {
		$regexp = "/^[a-zA-Z\ ]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function alphaNoSpaces($test) {
		$regexp = "/^[a-zA-Z]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function noSpaces($test) {
		$regexp = "/^[^\ ]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function noSpecialChars($test) {
		$regexp = "/^[^\W]+$/";
		return(self::regexp($regexp,$test));
	}
	
	public static function date($test,$delim="/") {
		
		if ($delim == "/") {
			$delim = "\/";
		}
		
		$regexp = "/^\d\d".$delim."\d\d".$delim."\d\d\d\d$/";
		return(self::regexp($regexp,$test));
	}

	// Stolen from php.net 
	// http://us1.php.net/manual/en/function.unserialize.php#85097
	public static function serialized($str) {
		return ($str == serialize(false) || @unserialize($str) !== false);
	}
	
}

?>