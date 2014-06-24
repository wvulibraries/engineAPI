<?php
/**
 * Utility Class - HTTP helpers
 *
 * This library provides basic helper methods for interacting with HTTP
 */
class http
{
    /**
     * Class constructor
     */
    public function  __construct(){}

	/**
	 * Checks that any POST has a valid CSRF Token included with it
	 * If a token is not found, or is invalid script execution is halted!
	 */
	public static function csrfCheck() {
		// No post? Then we don't care about CSRF
		if(!sizeof($_POST)) return;

		try{
			if (!isset($_POST['MYSQL']['__csrfID'])) throw new Exception('No CSRF ID');
			if (!isset($_POST['MYSQL']['__csrfToken'])) throw new Exception('No CSRF Token');
			if (!session::csrfTokenCheck($_POST['MYSQL']['__csrfID'], $_POST['MYSQL']['__csrfToken'])) throw new Exception('Invalid CSRF');
		}catch(Exception $e){
			die("CSRF check failed! ({$e->getMessage()})");
		}
    }

    public static function removeRequest() {
        if (isset($_REQUEST)) unset($_REQUEST);

        return TRUE;
    }

    /**
     * rebuilds the $_GET variable with sanitized HTML, MYSQL, and unsanitized raw values.
     * Builds a pre EngineAPI 4.0 cleanGET array in the $_GET variable
     * @return BOOL Always returns TRUE
     */
    public static function cleanGet() {
        if(isset($_GET)) {

            $temp = array();

            foreach ($_GET as $key => $value) {
                $cleanKey                 = htmlSanitize($key);
                $temp['HTML'][$cleanKey]  = htmlSanitize($value);
                $temp['MYSQL'][$cleanKey] = dbSanitize($value);
                $temp['RAW'][$cleanKey]   = $value;
            }
            unset($_GET);

            $_GET = $temp;
        }

        return TRUE;
    }

    /**
     * rebuilds the $_POST variable with sanitized HTML, MYSQL, and unsanitized raw values.
     * Builds a pre EngineAPI 4.0 cleanPost array in the $_POST variable
     * @return BOOL Always returns TRUE
	 * @todo #bug Checking $_GET and not $_POST on line 79 (won't build clean POST unless GET is set)
     */
    public static function cleanPost() {
        if(isset($_POST)) {

            $temp = array();

            foreach ($_POST as $key => $value) {
                $cleanKey                 = htmlSanitize($key);
                $temp['HTML'][$cleanKey]  = htmlSanitize($value);
                $temp['MYSQL'][$cleanKey] = dbSanitize($value);
                $temp['RAW'][$cleanKey]   = $value;
            }
            unset($_POST);

            $_POST = $temp;
        }

        return TRUE;
    }

    /**
     * Sets a variable in cleanGet. Sanitizes variables for clean MYSQL and HTML
     *
     * @author  Michael Bond
     * @param string $var variable to set
     * @param string $value value of variable to set. Will be converted to string
     *
     * @return BOOL TRUE
     */
    public static function setGet($var,$value) {

        $value  = (string)$value;

        $_GET['MYSQL'][$var] = dbSanitize($value);
        $_GET['HTML'][$var]  = htmlSanitize($value);
        $_GET['RAW'][$var]   = $value;

        return TRUE;
    }

    /**
     * Sets a variable in cleanPost. Sanitizes variables for clean MYSQL and HTML
     *
     * @author  Michael Bond
     * @param string $var variable to set
     * @param string $value value of variable to set. Will be converted to string
     *
     * @return BOOL TRUE
     */
    public static function setPost($var,$value) {

        if (!is_array($value)) $value  = (string)$value;

        $_POST['MYSQL'][$var] = dbSanitize($value);
        $_POST['HTML'][$var]  = htmlSanitize($value);
        $_POST['RAW'][$var]   = $value;

        return TRUE;
    }

	/**
	 * Redirect the browser to the given URL.
	 * (Warning: This will terminate script execution)
	 *
	 * @param string $url
	 * @param int $statusCode
	 * @param bool $exit
	 * @return bool
	 */
	public static function redirect($url, $statusCode=308, $exit=TRUE) {
		if(!preg_match('/^3\d\d$/', $statusCode)){
			trigger_error(__METHOD__."() - Invalid HTTP status code", E_USER_NOTICE);
			return FALSE;
		}

		self::sendStatus($statusCode);
		header(sprintf("Location: %s", trim($url)));
		if($exit) exit();
		return TRUE;
	}

    /**
     * Send the requested HTTP status code to the browser
     *
     * @param int $statusCode
     * @param boolean $replace
	 * @return bool
     */
    public static function sendStatus($statusCode, $replace=true) {
		$statusCode = (int)$statusCode;
		$replace    = (bool)$replace;
		$validCodes = array(
			101 => 'Switching Protocols',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			306 => 'Switch Proxy',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',
			400 => 'Bad Request',
			401 => 'Authorization Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			407 => 'Proxy Authentication Required',
			426 => 'Upgrade Required',
			500 => 'Internal Server Error',
			503 => 'Service Temporarily Unavailable',
		);

		if(isset($validCodes[$statusCode])){
			header("HTTP/1.1 $statusCode ".$validCodes[$statusCode], $replace);
			header("Status: $statusCode ".$validCodes[$statusCode], $replace);
			return TRUE;
		}else{
			trigger_error(__METHOD__."() - Invalid HTTP status code '$statusCode'", E_USER_NOTICE);
			return FALSE;
		}
    }

    /**
     * Compress a given data stream for inclusion in a URL
     *
     * @param mixed $data
     * @return string
     */
    public static function compressData($data) {
        return strtr(base64_encode(addslashes(gzcompress(serialize($data),9))), '+/=', '-_,');
    }

    /**
     * Decompress a given string back into its original form
     *
     * @param string $string
     * @return mixed
     */
    public static function decompressData($string) {
        return unserialize(gzuncompress(stripslashes(base64_decode(strtr($string, '-_,', '+/=')))));
    }
}
?>
