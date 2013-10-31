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
    public function  __construct()
    {}


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
     */
    public static function cleanPost() {
        if(isset($_GET)) {

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
        
        $_GET['MYSQL'][$var] = $engine->openDB->escape($value);
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
        
        $_POST['MYSQL'][$var] = $engine->openDB->escape($value);
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
     */
    public static function redirect($url, $statusCode=307, $exit=TRUE)
    {
        $statusCode = is_null($statusCode) ? 307 : (int)$statusCode;
        $validCodes = array(301,307);
        if(!in_array($statusCode, $validCodes)){
            trigger_error(__METHOD__."() - Invalid HTTP status code", E_USER_NOTICE);
            $statusCode=307;
        }

        self::sendStatus($statusCode);
        header(sprintf("Location: %s", trim($url)));
        if($exit) exit();
    }

    /**
     * Send the requested HTTP status code to the browser
     *
     * @param int $statusCode
     * @param boolean $replace
     */
    public static function sendStatus($statusCode, $replace=true)
    {
        // Clean params
        $statusCode = (int)$statusCode;
        $replace = (bool)$replace;

        // Figure out what status we're sending
        switch( (int)$statusCode ){
            case 101:
                // Informational - Switching Protocols
                header("HTTP/1.1 101 Switching Protocols", $replace);
                header("Status: 101 Switching Protocols", $replace);
                break;
            case 301:
                // Redirect - Moved Permanently
                header("HTTP/1.1 301 Moved Permanently", $replace);
                header("Status: 301 Moved Permanently", $replace);
                break;
            case 307:
                // Redirect - Temporary Redirect
                header("HTTP/1.1 307 Temporary Redirect", $replace);
                header("Status: 307 Temporary Redirect", $replace);
                break;
            case 400:
                // Client Error - Bad Request
                header("HTTP/1.1 400 Bad Request", $replace);
                header("Status: 400 Bad Request", $replace);
                break;
            case 401:
                // Client Error - Authorization Required
                header("HTTP/1.1 401 Authorization Required", $replace);
                header("Status: 401 Authorization Required", $replace);
                break;
            case 403:
                // Client Error - Forbidden
                header("HTTP/1.1 403 Forbidden", $replace);
                header("Status: 403 Forbidden", $replace);
                break;
            case 404:
                // Client Error - Not Found
                header("HTTP/1.1 404 Not Found", $replace);
                header("Status: 404 Not Found", $replace);
                break;
            case 407:
                // Client Error - Proxy Authentication Required
                header("HTTP/1.1 407 Proxy Authentication Required", $replace);
                header("Status: 407 Proxy Authentication Required", $replace);
                break;
            case 426:
                // Client Error - Upgrade Required
                header("HTTP/1.1 426 Upgrade Required", $replace);
                header("Status: 426 Upgrade Required", $replace);
                break;
            case 500:
                // Server Error - Internal Server Error
                header("HTTP/1.1 500 Internal Server Error", $replace);
                header("Status: 500 Internal Server Error", $replace);
                break;
            case 503:
                // Server Error - Service Temporarily Unavailable
                header("HTTP/1.1 503 Service Temporarily Unavailable", $replace);
                header("Status: 503 Service Temporarily Unavailable", $replace);
                break;
            default:
                trigger_error(__METHOD__."() - Invalid HTTP status code", E_USER_NOTICE);
                break;
        }
    }

    /**
     * Compress a given data stream for inclusion in a URL
     *
     * @param mixed $data
     * @return string
     */
    public static function compressData($data)
    {
        return strtr(base64_encode(addslashes(gzcompress(serialize($data),9))), '+/=', '-_,');
    }

    /**
     * Decompress a given string back into its original form
     *
     * @param string $string
     * @return mixed
     */
    public static function decompressData($string)
    {
        return unserialize(gzuncompress(stripslashes(base64_decode(strtr($string, '-_,', '+/=')))));
    }
}
?>
