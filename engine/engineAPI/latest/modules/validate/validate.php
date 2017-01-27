<?php
/**
 * EngineAPI validate module
 * @package EngineAPI\modules\validate
 */
class validate {
	/**
	 * @var enginevars
	 */
	private $enginevars;

	/**
	 * Mapping of available validators and their human-readable names
	 * Format: "method_name" => "Human readable name"
	 * @var array
	 */
	private $availableMethods = array(
		"regexp"               => "Regular Expression",
		"phoneNumber"          => "Phone Number",
		"ipAddr"               => "ipAddr",
		"ipAddrRange"          => "ipAddrRange",
		"optionalURL"          => "URL (Optional)",
		"url"                  => "URL",
		"urlPath"              => "URL Path",
		"urlFlexible"          => "URL Flexible",
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
	 * Class constructor
	 */
	function __construct() {
	}

	/**
	 * Returns validate instance
	 * @return validate
	 */
	public static function getInstance() {
		$validate = new self;
		$validate->set_enginevars(enginevars::getInstance());
		return $validate;
	}

	/**
	 * Sets the internal engineVars instance to use
	 * @param $enginevars
	 */
	public function set_enginevars($enginevars) {
		$this->enginevars = $enginevars;
	}

	/**
	 * Returns the mapping array available validators
	 *
	 * @see self::$availableMethods
	 * @return array
	 */
	public function validationMethods() {
		return($this->availableMethods);
	}

	/**
	 * Returns true if validationType is a valid validator (case insensitive)
	 *
	 * @param $validationType
	 * @return bool
	 */
	public function isValidMethod($validationType) {
		$validationType = trim(strtolower($validationType));
		$availableTypes = array_map(function($n){ return trim(strtolower($n)); }, array_keys($this->availableMethods));
		return in_array($validationType, $availableTypes);
	}

	/**
	 * Get human readable error message based on given validate method
	 * @param string $method The validate method name to use
	 * @param string $data   User data to include in message. (if omitted, a generic message will be created)
	 * @return string
	 */
	function getErrorMessage($method, $data=NULL){
		$return = isset($data)
			? "Entry '".htmlSanitize($data)."' not valid"
			: 'Invalid';

		switch(trim(strtolower($method))) {
			case 'url':
			case 'urlpath':
			case 'optionalurl':
			case 'urlFlexible':
				return $return . ': URL';
			case 'email':
			case 'internalemail':
				return $return . ': Email Address';
			case 'phone':
				return $return . ': Phone Number';
			case 'ipaddr':
				return $return . ': IP Address';
			case 'ipaddrrange':
				return $return . ': IP Address Range';
			case 'date':
				return $return . ': Date';
			case 'integer':
			case 'integerspaces':
				return $return . ': Integer';
			case 'alphanumeric':
				return $return . ': Letters and Numbers';
			case 'alphanumericnospaces':
				return $return . ': Letters and Numbers Without Spaces';
			case 'alpha':
				return $return . ': Letters Only';
			case 'alphanospaces':
				return $return . ': Letters Without Spaces';
			case 'nospaces':
				return $return . ': No Spaces';
			case 'nospecialchars':
				return $return . ': No Special Characters';
			default:
				return $return . '.';
		}
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
	public function csvValue($testName, $string){
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
	public function regexp($regexp,$test) {
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
	public function phoneNumber($number) {
		$phoneRegex = "/^\s*(\+?\d+\s*(\-|\ |\.)\s*)?\(?\d{3}\)?\s*(\-|\ |\.)\s*\d{3}\s*(\-|\ |\.)\s*\d{4}(\s*(\s|ext(\.|\:)?|extension\:?|x(\.|\:)?)\s*\d+)?$/";
		
		return($this->regexp($phoneRegex,$number));
	}
	
	/**
	 * Validate as an IP Address
	 *
	 * @todo  this function is incorrect. 
	 * 
	 * @param string $ip
	 * @return bool|null
	 */
	public function ipAddr($ip) {
		$ipRegex = "/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/";
		return($this->regexp($ipRegex,$ip));
	}
	
	/**
	 * Validate as an IP range
	 *
	 * @param string $ip
	 * @return bool|null
	 */
	public function ipAddrRange($ip) {
		$ipAddr  = "(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)";
        $ipGroup = "(?:$ipAddr|$ipAddr-$ipAddr|\*)";
        $ipRange = "(?:$ipGroup\.){3}$ipGroup";
		return($this->regexp("/^$ipRange$/",$ip));
	}

	/**
	 * Flexible URL validation
	 *  
	 * Performs a lgoci OR between url(), urlPath(), and (optionally) optionalURL()
	 *  
	 * @param string $url
	 * @param bool $optional Pass true to also allow optionalURL()
	 * @return bool|null
	 * @see self::optionalURL()
	 * @see self::url()
	 * @see self::urlPath()
	 */
	public function urlFlexible($url, $optional=FALSE){
		return $this->url($url) || $this->urlPath($url) || ($optional && $this->optionalURL($url));
	}

	/**
	 * Validate as a potential URL
	 * Allow just about anything, but if it appears to be a URL it must be a valid URL
	 *
	 * @param string $url
	 * @return bool|null
	 */
	public function optionalURL($url) {
		$urlCheckRegex = "/^(https?|ftp|ssh|telnet)\:\/\/.+/";
		$urlTest       = $this->regexp($urlCheckRegex,$url);

		if ($urlTest == 1) {
			return($this->url($url));
		}

		return(TRUE);
	}

	/**
	 * Validate as URL
	 *
	 * @param string $url
	 * @return bool|null
	 */
	public function url($url) {
		
		// Regex stolen from
		// http://phpcentral.com/208-url-validation-in-php.html
		$urlregex = "/^(https?|ftp|ssh|telnet)\:\/\/([a-zA-Z0-9+!*(),;?&=\$_.-]+(\:[a-zA-Z0-9+!*(),;?&=\$_.-]+)?@)?[a-zA-Z0-9+\$_-]+(\.[a-zA-Z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-zA-Z0-9+\$_-]\.?)+)*\/?(\?[a-zA-Z+&\*\$_.-][a-zA-Z0-9;:@\/&%=+,\*\$_.-]*)?(#[a-zA-Z_.-][a-zA-Z0-9+\*\$_.-]*)?\$/";

		return($this->regexp($urlregex,$url));
	}

	/**
	 * Validate as a URL path
	 *
	 * @todo Allow relative paths
	 * @param $path
	 * @return bool|null
	 */
	public function urlPath($path){
		$urlregex = "/^(?:\.\.|\.|\w+)?(\/([a-zA-Z0-9+\$_-]\.?)+)*\/?(\?[a-zA-Z+&\*\$_.-][a-zA-Z0-9;:@\/&%=+,\*\$_.-]*)?(#[a-zA-Z_.-][a-zA-Z0-9+\*\$_.-]*)?\$/";

		return($this->regexp($urlregex,$path));

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
	public function emailAddr($email,$internal=FALSE) {

		if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
			if($internal) {
				if ($this->internalEmailAddr($email) === TRUE) {
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
	 * Runs the email through regex's in $enginevars->get("internalEmails") for a match
	 *
	 * @param string $email
	 * @return bool
	 */
	public function internalEmailAddr($email) {

		foreach ($this->enginevars->get('internalEmails') as $key => $regex) {
			if(preg_match($regex,$email)) {
				return(TRUE);
			}	
		}

		return(FALSE);
	}

	private function integerPreChecks($test) {

		if (is_numeric($test) === FALSE) return FALSE;
		
		if ((int)$test != $test) return FALSE;

		if (is_float($test)) return FALSE;

		return TRUE;

	}

	/**
	 * Validate as an integer
	 * (ex: '1234')
	 * This function improves over built in is_int() because it will test 
	 * strings as well
	 *
	 * @param int $test
	 * @return bool|null
	 */
	public function integer($test) {

		if ($this->integerPreChecks($test) === FALSE) return FALSE;

		$regexp = "/^-?\d+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as an integer with spaces allowed
	 * (ex: '12 34')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public function integerSpaces($test) {
		// if ($this->integerPreChecks($test) === FALSE) return FALSE;
		// 
		$regexp = "/^-?\s*[0-9\ ]+\s*$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as alpha-numerical with spaces allowed
	 * (ex: 'abc123def456')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public function alphaNumeric($test) {
		$regexp = "/^[a-zA-Z0-9\-\_\ ]+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as alpha-numerical
	 * (ex: 'abc123')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public function alphaNumericNoSpaces($test) {
		$regexp = "/^[a-zA-Z0-9\-\_]+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as alphabetical with spaces allowed
	 * (ex: 'abc def')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public function alpha($test) {
		$regexp = "/^[a-zA-Z\ ]+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as alphabetical
	 * (ex: 'abcdef')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public function alphaNoSpaces($test) {
		$regexp = "/^[a-zA-Z]+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as text with no spaces
	 * (ex: fails for 'abc 123')
	 *
	 * @param string $test
	 * @return bool|null
	 */
	public function noSpaces($test) {
		$regexp = "/^[^\ ]+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as no special characters
	 *
	 * @param $test
	 * @return bool|null
	 */
	public function noSpecialChars($test) {
		$regexp = "/^[^\W]+$/";
		return($this->regexp($regexp,$test));
	}

	/**
	 * Validates as a valid date format, YYYY[-MM-DD]
	 *
	 * @param string $test
	 * @return bool
	 */
	// @TODO this is broken, but fixes some issues. day is not optional right now, unless month is also omitted 
	function date($test) {

		// match 4 digit year
		if (preg_match("/^\d{4}$/", $test)) return TRUE;

		$parseDate = date_parse($test);
		if (is_empty($parseDate['errors']) && checkdate($parseDate['month'],$parseDate['day'],$parseDate['year'])) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Validates as serialized string
	 *
	 * @see http://us1.php.net/manual/en/function.unserialize.php#85097
	 * @param $str
	 * @return bool
	 */
	public function serialized($str) {
		return ($str == serialize(false) || @unserialize($str) !== false);
	}
	
}

?>