<?php
/**
 * @todo Error Reporting support (ignore all errors below a threshold)
 */
class errorHandle
{
    /**
     * Error Severity
     * (Bitwise compatible)
     */
    const INFO     = 1;
    const DEBUG    = 2;
    const LOW      = 4;
    const MEDIUM   = 8;
    const HIGH     = 16;
    const CRITICAL = 32;
    const E_ALL    = 63;
    /**
     * EngineAPI error stack types
     */
	const ERROR   = "error";
	const SUCCESS = "success";
	const WARNING = "warning";
    /**
     * Singleton holder
     * @var errorHandle
     */
    private static $instance;
    /**
     * Local stack of error profiles
     * @see self::addProfile()
     * @var array
     */
    private static $errorProfiles = array();
    /**
     * The internal mapping between PHP's error types, and our error severities.
     * @var array
     */
    public static $phpErrMapping = array();
    private static $errorReporting;

    private static $errMsg;
    private static $errSeverity;
    private static $backTrace;
    private static $errFile;
    private static $errLine;
    private static $phpErrNo;
    private static $errorType;

    public static $uiSpanElement  = 'p';
    public static $uiClassError   = 'errorMessage';
    public static $uiClassSuccess = 'successMessage';
    public static $uiClassWarning = 'warningMessage';


    /**
     * Singleton access
     * @static
     * @return errorHandle
     */
    public static function singleton()
    {
        if(self::$instance === null) {
	 		self::$instance = new self();
		}
        return self::$instance;
    }

    public function __clone()
    {
        trigger_error('Cloning instances of this class is forbidden.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Unserializing instances of this class is forbidden.', E_USER_ERROR);
    }

    private function __construct()
    {
        // Set PHP Error Constant => errorHandle Constant mapping
        self::$phpErrMapping = array(
            E_WARNING           => self::MEDIUM,
            E_NOTICE            => self::LOW,
            E_USER_ERROR        => self::HIGH,
            E_USER_WARNING      => self::MEDIUM,
            E_USER_NOTICE       => self::LOW,
            E_STRICT            => self::MEDIUM,
            E_RECOVERABLE_ERROR => self::MEDIUM,
            'phpException'      => self::HIGH);

        // Set the default (All but INFO and DEBUG)
        self::$errorReporting = self::E_ALL & ~self::INFO & ~self::DEBUG;

        // Add the base error profiles
        self::addProfile(array('errorSeverity' => self::INFO), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::DEBUG), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::LOW), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::MEDIUM), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::HIGH), array('logLocation' => 'nativePHP','fatal' => TRUE));
        self::addProfile(array('errorSeverity' => self::CRITICAL), array('logLocation' => 'nativePHP','fatal' => TRUE));

        // Register custom handlers for PHP's errors and exceptions
        set_error_handler(array(__CLASS__, 'phpError'));
        set_exception_handler(array(__CLASS__, 'phpException'));
    }

    /**
     * Adds an error 'profile' for later use.
     * Error profiles are used to tell the system what to do when it encounters an error.
     *
     * When an error is encountered, we look though all the available profiles which ALL of its conditions match the error.
     * Then we find the most specific one, the one with the most conditions, and preform the actions of that profile.
     * (In the event of a tie, the profile encountered 1st wins)
     *
     * @static
     * @param array $conditions
     *        An array of conditions which must all match for this profile to be used for an error
     *        Possible values include:
     *         + errorType     - The type of error triggered
     *                            - 'phpError'    - An error triggered from PHP or with trigger_error()
     *                            - 'phpException - An uncaught exception
     *                            - 'custom'      - A custom error message using the newError() method
     *         + errorSeverity - The severity of the error raised. (INFO, DEBUG, LOW, MEDIUM, HIGH, CRITICAL)
     *         + errorOrigin   - A RegEx pattern to match against the file path from where the error originated from
     *         + engineEnv     - The value of the ENGINE_ENVIRONMENT constant. (ex: 'production', 'development', 'cli', etc)
     *
     * @param array $actions
     *        An array of actions to take in response to this profile being used
     *         + logLocation - The location(s) where this error should be recorded to. (use an array for multiple values)
     *                         These are parsed with PHP's parse_URL, so they look a lot like URLs. For example:
     *                          - file://FILE_PATH                              - Log this error to a static log file. (FILE_PATH must be absolute)
     *                          - email://EMAIL_ADDRESS                         - Send a record of this error to the specified email address
     *                          - mysql://USER:PASS@HOSTNAME/DB_NAME#TABLE_NAME - Log this error for a MySQL database
     *                          - nativePHP                                     - Hand this error message off to the native PHP log handler.
     *         + httpRedirect - Redirect the user (via HTTP Header) to a specified URL [Fatal is implied]
     *         + exec - Execute a 3rdParty script/applications via PHP's exec()
     *         + fatal - Abort site execution at the conclusion of this profile.
     *
     * @return string
     *         Error profile referenced using a checksum of their conditions.
     *         This checksum is returned upon successful profile addition. Otherwise an empty string to returned.
     *
     *
     * Example Usage:
     *
     *   I want all HIGH severity errors that occurs from any file under /home/example/admin/ to be logged with
     *   nativePHP, logged in our database, and emailed to me. The user should then be  redirected to our homepage.
     *
     *   addProfile(array(
     *     'errorSeverity' => errorHandle::HIGH,
     *     'errorOrigin'   => '|/home/example/admin/.+|'
     *   ), array(
     *     'logLocation' => array(
     *       'nativePHP',
     *       'mysql://foo:bar@localhost/siteErrors#high',
     *       'email://webmaster@example.com'
     *     ),
     *     'httpRedirect' => 'http://example.com'
     *   ));
     *
     */
    public static function addProfile($conditions,$actions)
    {
        $profileFingerprint = md5(print_r($conditions,TRUE));
        if(!array_key_exists($profileFingerprint, self::$errorProfiles)){
            self::$errorProfiles[$profileFingerprint] = array(
                'conditions' => $conditions,
                'actions' => $actions);
            return $profileFingerprint;
        }else{
            // Trigger Error! ??? @todo
            return '';
        }
    }

    /**
     * Removes an error 'profile' from later use
     * @static
     * @param array|string $conditions
     * @return bool
     */
    public static function removeProfile($conditions)
    {
        $profileFingerprint = (is_array($conditions)) ? md5(print_r($conditions,TRUE)) : $conditions;
        if(array_key_exists($profileFingerprint, self::$errorProfiles)){
            unset(self::$errorProfiles[$profileFingerprint]);
            return TRUE;
        }else{
            // Trigger Error! ??? @todo
            return FALSE;
        }
    }

    /**
     * PHP Error handler - Used to catch all php errors
     *
     * @see http://us2.php.net/manual/en/function.set-error-handler.php
     *
     * @param int    $errNo      The level of the error raised, as an integer
     * @param string $errStr     The error message, as a string
     * @param string $errFile    The filename that the error was raised in, as a string
     * @param int    $errLine    The line number the error was raised at, as an integer
     * @param array  $errContext The active symbol table at the point the error occurred
     * @return bool
     */
    public static function phpError($errNo, $errStr, $errFile, $errLine, $errContext)
    {
        // we only care if the PHP error is being looked for
        $errorReporting = ini_get('error_reporting');
        if($errorReporting === 0 || !($errorReporting & $errNo)) return FALSE;

return FALSE;

        self::$errorType = 'phpError';
        self::$phpErrNo = $errNo;
        if(sizeof(self::$errorProfiles)){
            if(sizeof(self::$phpErrMapping)){
                self::newError($errStr, self::$phpErrMapping[$errNo]);
            }else{
                self::newError($errStr, self::CRITICAL);
            }
        }else{
            error_log($errStr." at $errLine:$errFile");
        }
        return TRUE;
    }

    /**
     * PHP Exception handler - Used to catch all php exceptions
     * Note: Site execution will be aborted after this is triggered (native PHP behavior)
     *
     * @param Exception $e
     * @see http://www.php.net/manual/en/class.exception.php
     * @see http://us2.php.net/manual/en/function.set-exception-handler.php
     */
    public static function phpException(Exception $e)
    {
        self::$errorType = 'phpException';
        self::newError($e->getMessage(), self::$phpErrMapping['phpException']);
    }

    
    private static function phpErr2Str($errNo)
    {
        $phpErrors = array(
            E_WARNING      => 'E_WARNING',
            E_NOTICE       => 'E_NOTICE',
            E_USER_ERROR   => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE  => 'E_USER_NOTICE',
            E_STRICT       => 'E_STRICT');
        if(defined('E_RECOVERABLE_ERROR')) $phpErrors[E_RECOVERABLE_ERROR] = 'E_RECOVERABLE_ERROR';
        if(defined('E_DEPRECATED'))        $phpErrors[E_DEPRECATED]        = 'E_DEPRECATED';
        if(defined('E_USER_DEPRECATED'))   $phpErrors[E_USER_DEPRECATED]   = 'E_USER_DEPRECATED';

        if(array_key_exists($errNo, $phpErrors)){
            return $phpErrors[$errNo];
        }else{
            return (string)$errNo;
        }
    }





    public static function newError($errMsg, $errSeverity, $errInfo=array())
    {
        // we only care if the PHP error is being looked for
        if(!(self::$errorReporting & $errSeverity)) return FALSE;

        // Save the error info for processing
        if(!isset(self::$errorType)){
            self::$errorType='newError';
        }
        self::$errMsg      = trim($errMsg);
        self::$errSeverity = $errSeverity;
        self::$backTrace   = self::getErrorOrigin();
        self::$errFile     = self::$backTrace[0]['file'];
        self::$errLine     = self::$backTrace[0]['line'];

        // Find all the error profiles which this error can apply to
        $profiles = array();
        foreach(self::$errorProfiles as $errorProfile){
            foreach($errorProfile['conditions'] as $condition => $value){
                switch($condition){
                    case 'errorType':
                        if($value != self::$errorType){
                            continue 3;
                        }
                        break;

                    case 'errorSeverity':
                        if($value != self::$errSeverity){
                            continue 3;
                        }
                        break;

                    case 'errorOrigin':
                        if(!preg_match($value, self::$errFile)){
                            continue 3;
                        }
                        break;

                    case 'engineEnv':
                        if(defined('ENGINE_ENVIRONMENT') and ENGINE_ENVIRONMENT != $value){
                            continue 3;
                        }
                        break;
                }
            }

            // If we survive to here than this profile will work for this error.
            $profiles[] = $errorProfile;
        }
        /*
         * Okay, we now have a list of errorProfiles which will work for this error. We need to find the one
         * which is the most specific. (In other words, the one with the most conditions)
         */
        $winningProfile=NULL;
        foreach($profiles as $profile){
            if(is_null($winningProfile) || sizeof($profile['conditions']) > sizeof($winningProfile['conditions'])){
                $winningProfile = $profile;
            }
        }

        // We now have the profile we need to operate on.
        foreach($winningProfile['actions'] as $action => $value){
            switch($action){
                case 'logLocation':
                    if($value == 'nativePHP'){
                        error_log(self::$errMsg." at ".self::$errLine.":".self::$errFile);
                    }else{
                        $logLocation = parse_url($value);
                        switch($logLocation['scheme']){
                            case 'file':
                                if(array_key_exists('SHELL', $_SERVER)){
                                    // CLI Interface
                                    error_log(sprintf('[%s] [%s] [cli %s] %s\n\t%s:%s',
                                        date('D M d H:i:s Y'),
                                        self::phpErr2Str(self::$phpErrNo),
                                        $_SERVER['USER'],
                                        self::$errMsg,
                                        self::$errLine,
                                        self::$errFile), 3, $logLocation['path']);
                                }else{
                                    // Web Interface
                                    error_log(sprintf('[%s] [%s] [web %s] %s\n\t%s:%s',
                                        date('D M d H:i:s Y'),
                                        self::phpErr2Str(self::$phpErrNo),
                                        $_SERVER['REMOTE_ADDR'],
                                        self::$errMsg,
                                        self::$errLine,
                                        self::$errFile), 3, $logLocation['path']);
                                }
                                break;

                            case 'email':
                                // Send Email @todo
                                break;

                            case 'db':
                                // Log in database @todo
                                break;
                        }
                    }
                    break;

                case 'fatal':
                    if($value and !defined('fatal_ERROR')) define("fatal_ERROR",true);
                    break;

                case 'emailAlert':
                    // Send Email @todo
                    break;

                case 'exec':
                    exec($value);
                    break;
            }
        }
        if(defined('fatal_ERROR') and fatal_ERROR) die('*** fatal Error ***'); // @todo Change to exit() after development
    }

    public static function errorMsg($msg)
    {
        self::newError($msg, self::INFO);
        self::errorStack(self::ERROR,$msg);
        return sprintf('<%s class="%s">%s</%s>', self::$uiSpanElement, self::$uiClassError, $msg, self::$uiSpanElement);
    }

    public static function successMsg($msg)
    {
        self::newError($msg, self::INFO);
        self::errorStack(self::SUCCESS,$msg);
        return sprintf('<%s class="%s">%s</%s>', self::$uiSpanElement, self::$uiClassSuccess, $msg, self::$uiSpanElement);
    }

    public static function warningMsg($msg)
    {
        self::newError($msg, self::INFO);
        self::errorStack(self::WARNING,$msg);
        return sprintf('<%s class="%s">%s</%s>', self::$uiSpanElement, self::$uiClassWarning, $msg, self::$uiSpanElement);
    }

    public static function prettyPrint($type="all")
    {
        if(!class_exists('EngineAPI', FALSE)){
            // There's no EngineAPI to read the errorStacks from, so there's no point trying
            return '';
        }

		$engine = EngineAPI::singleton();
		$output = '<ul class="errorPrettyPrint">';
		if ($type == "all") {
			if (!isset($engine->errorStack['all']) || !is_array($engine->errorStack['all'])) {
				return(FALSE);
			}
			foreach ($engine->errorStack['all'] as $V){
				switch ($V['type']) {
					case self::ERROR:
						$class = self::$uiClassError;
						break;
					case self::SUCCESS:
						$class = self::$uiClassSuccess;
						break;
					case self::WARNING:
						$class = self::$uiClassWarning;
						break;
					default:
						break;
				}

				$output .= "<li>";
				$output .= '<span class="'.$class.'">';
				$output .= $V['message'];
				$output .= "</span>";
				$output .= "</li>";
			}
		}else{
			if (!isset($engine->errorStack[$type]) || !is_array($engine->errorStack[$type])) {
				return(FALSE);
			}
			switch ($type) {
				case errorHandle::ERROR:
					$class = "errorMessage";
					break;
				case errorHandle::SUCCESS:
					$class = "successMessage";
					break;
				case errorHandle::WARNING:
					$class = "warningMessage";
					break;
				default:
					break;
			}
			foreach ($engine->errorStack[$type] as $V){
				$output .= "<li>";
				$output .= '<span class="'.$class.'">';
				$output .= $V;
				$output .= "</span>";
				$output .= "</li>";
			}
		}
		$output .= '</ul>';
		return($output);
	}

	private function errorStack($type,$message)
    {
        if(!class_exists('EngineAPI', FALSE)){
            // There's no EngineAPI to push this error onto, so there's no point trying
            return '';
        }

		$engine = EngineAPI::singleton();

		if (!isset($engine->errorStack[$type])) {
			$engine->errorStack[$type] = array();
		}
		if (!isset($engine->errorStack["all"])) {
			$engine->errorStack["all"] = array();
		}
		
		$engine->errorStack[$type][] = $message;
		
		$temp = array();
		$temp['message'] = $message;
		$temp['type']    = $type;
		$engine->errorStack["all"][] = $temp;
		
		return;
	}

    public static function errorReporting($newValue=NULL)
    {
        $oldValue = self::$errorReporting;
        self::$errorReporting = $newValue;
        return $oldValue;
    }

    private static function getErrorOrigin()
    {
        $backtrace = debug_backtrace();

        while(sizeof($backtrace) and $backtrace[0]['file'] != __FILE__){
            array_shift($backtrace);
        }

        // If this is a phpError or a phpException, we need to go 1 more step back
        if(self::$errorType == 'phpError' || self::$errorType == 'phpException'){
            array_shift($backtrace);
        }

        return array_reverse($backtrace);
    }
}