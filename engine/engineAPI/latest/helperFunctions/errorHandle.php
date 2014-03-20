<?php
/**
 * EngineAPI - Error Handler
 */

/**
 * General error handling
 *
 * This is the central handler for all errors (both native and user-generated)
 * This is a singleton object designed to be interacted with via its static methods
 *
 * To trigger a new error, use the newError() method.
 * @see errorHandle::newError
 * @package EngineAPI\errorHandle
 * @todo Add sample code for error profiles
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
     * Internal email handler reference
     * @var mailSender
     */
    private static $email;
    /**
     * The sender of all error emails sent by errorHandle
     * @var string
     */
    public static $emailSender = 'libsys@mail.wvu.edu';
    /**
     * An email subject prefix for all error emails
     * @var string
     */
    public static $emailSubject = 'EngineAPI Error - ';

    /**
     * Set this to true, and ALL errors will simply crash with no further processing done.
     * Useful for very low-level debugging
     * @var bool
     */
    public static $crashOnErrors = FALSE;

    /**
     * Internal engineDB connection
     * @var EngineDB
     */
    private static $db;
    /**
     * The database table where errors will be stored
     * @var string
     */
    public static $dbTblName = 'errorLog';

    /**
     * The PHP Error type
     * @var int
     */
    private static $phpErrNo;
    /**
     * The severity of an error
     * @var int
     */
    private static $errSeverity;
    /**
     * A flag indicating the origin of this error. ('phpError','phpException','newError')
     * @var string
     */
    private static $errorType;

    /**
     * The internal mapping between PHP's error types, and our error severity's.
     * The array key will be the PHP error type, and the value will be our own error severity.
     * Default: set in constructor
     * @var array
     */
    public static $phpErrMapping = array();
    /**
     * Our own, internal, error reporting level, and is accessed though errorReporting()
     * Default: set in constructor
     * @var int
     */
    private static $errorReporting;
    /**
     * Local stack of error profiles
     * @see self::addProfile()
     * @var array
     */
    private static $errorProfiles = array();
    /**
     * The backTrace of an error
     * @var array
     */
    private static $backTrace;
    /**
     * The message explaining an error
     * @var string
     */
    private static $errMsg;
    /**
     * The file where an error occurred in
     * @var string
     */
    private static $errFile;
    /**
     * The line file where an
     * @var int
     */
    private static $errLine;
    /**
     * An array of additional, optional, error information
     * @var array
     */
    private static $errInfo;

    /**
     * [uiMsg] span element. (This will be the wrapping tag)
     * @var string
     */
    public static $uiSpanElement  = 'p';
    /**
     * [uiMsg] CSS class for error messages
     * @var string
     */
    public static $uiClassError   = 'errorMessage';
    /**
     * [uiMsg] CSS class for success messages
     * @var string
     */
    public static $uiClassSuccess = 'successMessage';
    /**
     * [uiMsg] CSS class for warning messages
     * @var string
     */
    public static $uiClassWarning = 'warningMessage';

	/**
	 * [errorStack] Custom callback for pretty print
	 * @var
	 */
	private static $prettyPrintCallback;

    private $enginevars;

    /**
     * Singleton access
     * @static
     * @return errorHandle
     */
    public static function singleton(){
        if(self::$instance === null) {
	 		self::$instance = new self();
		}
        return self::$instance;
    }

	/**
	 * Returns TRUE if the errorHandle is ready to receive errors
	 * @return bool
	 */
	public static function isReady(){
		return (self::$instance instanceof self);
	}

	/**
	 * No cloning allowed!
	 */
	private function __clone(){}
	/**
	 * No wakeup (serialization) allowed!
	 */
    private function __wakeup(){}

    /**
     * Class constructor
     */
    private function __construct()
    {
        /*
         * Set PHP Error Constant => errorHandle Constant mapping
         * Note: Some errors cannot be caught at runtime. These include:
         *   - E_PARSE
         *   - E_CORE_ERROR
         *   - E_CORE_WARNING
         *   - E_COMPILE_ERROR
         *   - E_COMPILE_WARNING
         */
        self::$phpErrMapping = array(
			E_ERROR             => self::HIGH,
            E_WARNING           => self::MEDIUM,
            E_NOTICE            => self::LOW,
            E_USER_ERROR        => self::HIGH,
            E_USER_WARNING      => self::MEDIUM,
            E_USER_NOTICE       => self::LOW,
            E_STRICT            => self::MEDIUM,
            E_RECOVERABLE_ERROR => self::MEDIUM,
            'phpException'      => self::HIGH);

		// Add error types added in version 5.3
		if(defined('E_DEPRECATED'))        self::$phpErrMapping[E_DEPRECATED]        = self::DEBUG;
		if(defined('E_USER_DEPRECATED'))   self::$phpErrMapping[E_USER_DEPRECATED]   = self::DEBUG;

        // Set the default (All but INFO and DEBUG)
        self::$errorReporting = self::E_ALL & ~self::INFO & ~self::DEBUG;

        // Add the base error profiles
        self::addProfile(array('errorSeverity' => self::INFO), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::DEBUG), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::LOW), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::MEDIUM), array('logLocation' => 'nativePHP'));
        self::addProfile(array('errorSeverity' => self::HIGH), array('logLocation' => 'nativePHP','fatal' => TRUE));
        self::addProfile(array('errorSeverity' => self::CRITICAL), array('logLocation' => 'nativePHP','fatal' => TRUE));

        // Add the 'catchAll' error profile (DO NOT REMOVE)
        self::addProfile(array(), array('logLocation' => 'nativePHP'));

        // Register custom handlers for PHP's errors and exceptions
        set_error_handler(array(__CLASS__, 'phpError'));
        set_exception_handler(array(__CLASS__, 'phpException'));

        $this->set_enginevars(enginevars::getInstance());
    }

    public function set_enginevars($enginevars) {
        $this->enginevars = $enginevars;
    }

	/**
	 * Restore PHP's native error and exception handlers
	 * Since we're about to die, we need to hand-off error handling back to PHP
	 */
	public function __destruct(){
		restore_error_handler();
		restore_exception_handler();
	}

    /**
     * This method will reset the instance back to it's vanilla state (ready for a new error)
     * @static
     * @return void
     */
    private static function resetInstance()
    {
        self::$errorType   = NULL;
        self::$errMsg      = NULL;
        self::$phpErrNo    = NULL;
        self::$errSeverity = NULL;
        self::$backTrace   = NULL;
        self::$errFile     = NULL;
        self::$errLine     = NULL;
        self::$email       = NULL;
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
     *                          - file://FILE_PATH                           - Log this error to a static log file. (FILE_PATH must be absolute)
     *                          - email://EMAIL_ADDRESS                      - Send a record of this error to the specified email address
     *                          - db://USER:PASS@HOSTNAME/DB_NAME#TABLE_NAME - Log this error for a MySQL database
     *                          - nativePHP                                  - Hand this error message off to the native PHP log handler.
     *         + httpRedirect - Redirect the user (via HTTP Header) to a specified URL [Fatal is implied]
     *         + exec - Execute a 3rdParty script/applications via PHP's exec()
     *         + fatal - Abort site execution at the conclusion of this profile.
     *
     * @param bool $replace
     *         Will replace any existing error profile with the same conditions, otherwise will combine the actions
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
     *       'db://foo:bar@localhost/siteErrors#high',
     *       'email://webmaster@example.com'
     *     ),
     *     'httpRedirect' => 'http://example.com'
     *   ));
     *
     */
    public static function addProfile($conditions,$actions,$replace=FALSE)
    {
        $profileFingerprint = md5(print_r($conditions,TRUE));
        $newProfile = array('conditions' => $conditions, 'actions' => $actions);

        if(!array_key_exists($profileFingerprint, self::$errorProfiles) || $replace){
            self::$errorProfiles[$profileFingerprint] = $newProfile;
        }else{
            self::$errorProfiles[$profileFingerprint]['actions'] = array_merge_recursive(self::$errorProfiles[$profileFingerprint]['actions'], $actions);
        }

        // Make sure everything is unique!
        self::$errorProfiles[$profileFingerprint]['actions'] = array_unique_recursive(self::$errorProfiles[$profileFingerprint]['actions']);

        return $profileFingerprint;
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

        // If a PHP error has occurred in THIS file, then we need to just crash to allow the developer to see it.
        if($errFile === __FILE__ || self::$crashOnErrors){
            $lineEndings = (isCLI()) ? "\n" : "<br />";
            echo "Critical EngineAPI errorHandle Error!".$lineEndings;
            echo "PHP Error: [".self::phpErr2Str($errNo)."] ".$errStr.$lineEndings;
            echo "File: $errFile".$lineEndings;
            echo "Line: $errLine".$lineEndings;
            if(isCLI()){
                echo str_repeat('-',strlen($errFile)+6).$lineEndings;
                echo "Symbol Tree: ".print_r($errContext,TRUE);
            }
            exit();
        }else{
            self::$errorType = 'phpError';
            self::$phpErrNo = $errNo;
            if(sizeof(self::$errorProfiles)){
                if(sizeof(self::$phpErrMapping)){
					if(isset(self::$phpErrMapping[$errNo])){
						self::newError($errStr, self::$phpErrMapping[$errNo]);
					}else{
						self::newError($errStr, self::CRITICAL);
					}
                }else{
                    self::newError($errStr, self::CRITICAL);
                }
            }else{
                error_log($errStr." at $errLine:$errFile");
            }
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
        self::newError("{$e->getMessage()} at {$e->getFile()}:{$e->getLine()}", self::$phpErrMapping['phpException']);
    }

    /**
     * Record a new error
     * @static
     * @param string $errMsg
     *        The message associated with this error
     * @param int $errSeverity
     *        The severity of this error (LOW, HIGH, CRITICAL, etc)
     * @param array $errInfo
     *        An optional array of addition (contextual) information pertaining to this error
     * @return void
     */
    public static function newError($errMsg, $errSeverity, $errInfo=array())
    {
        // we only care if the PHP error is being looked for
        if(!(self::$errorReporting & $errSeverity)) return;

        // If we're in CLI-mode, just write the error to stderr
        if(isCLI()){
            $fp = fopen("php://stderr", 'w+');
            fwrite($fp,$errMsg);
            fclose($fp);
            return;
        }

        // Save the error info for processing
        if(!isset(self::$errorType)){
            self::$errorType='newError';
        }
        self::$errMsg      = trim($errMsg);
        self::$errSeverity = $errSeverity;
        self::$backTrace   = self::getErrorOrigin();
        self::$errFile     = (is_array(self::$backTrace) and sizeof(self::$backTrace) and array_key_exists('file', self::$backTrace[0]))
                                ? self::$backTrace[0]['file']
                                : 'unknown(errorHandle - No Backtrace Avail)';
        self::$errLine     = (is_array(self::$backTrace) and sizeof(self::$backTrace) and array_key_exists('line', self::$backTrace[0]))
                                ? self::$backTrace[0]['line']
                                : 'unknown(errorHandle - No Backtrace Avail)';

        if(is_array($errInfo)){
            foreach($errInfo as $key => $value){
                self::$errInfo[] = self::toString($value);
            }
        }

        if(self::$crashOnErrors){
            $lineEndings = (isCLI()) ? "\n" : "<br />";
            echo "Critical EngineAPI errorHandle Error!".$lineEndings;
            echo "Error: [".self::severity2Str($errSeverity)."] ".$errMsg.$lineEndings;
            echo "File: ".self::$errFile.$lineEndings;
            echo "Line: ".self::$errLine.$lineEndings;
            if(sizeof(self::$errInfo)){
                echo "Error Info:".$lineEndings;
                foreach(self::$errInfo as $errInfo){
                    echo " + $errInfo".$lineEndings;
                }
            }
            echo "Raw Backtrace:".$lineEndings;
            debug_print_backtrace();
            exit();
        }

        // Find all the error profiles which this error can apply to
        $profiles = array();
        foreach(self::$errorProfiles as $errorProfile){
            if(array_key_exists('conditions', $errorProfile)){
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
            }
            // If we survive to here than this profile will work for this error.
            $profiles[] = $errorProfile;
        }

        /*
         * Okay, we now have a list of errorProfiles which will work for this error. We need to find the one
         * which is the most specific. (In other words, the one with the most conditions)
         */
        $winningProfile=array();
        foreach($profiles as $profile){
            if(is_empty($winningProfile) || sizeof($profile['conditions']) > sizeof($winningProfile['conditions'])){
                $winningProfile = $profile;
            }
        }

        // We now have the profile we need to operate on.
        if(isset($winningProfile['actions'])){
            foreach($winningProfile['actions'] as $action => $value){
                switch($action){
                    case 'logLocation':
                        $locations = (array)$value;
                        foreach($locations as $location){
                            self::recordError($location);
                        }
                        break;

                    case 'httpRedirect':
                        http::redirect($value);
                        break;

                    case 'fatal':
                        if($value and !defined('FATAL_ERROR')) define("FATAL_ERROR",true);
                        break;

                    case 'exec':
                        exec($value);
                        break;
                }
            }
        }

        // If there's a active email object, we need to send out that email
        if(self::$email instanceof mailSender) self::$email->sendEmail();

        // Cleanup - Reset the instance
        self::resetInstance();

        // Lastly, should I die?
        if(defined('fatal_ERROR') and FATAL_ERROR) exit();
    }

    /**
     * This is a helper funcion for newError()
     * This method will take the current error and record it in the specified location
     * @static
     * @param string $logLocation
     * @return bool
	 * @TODO Bug on line 576 (db handling)
     */
    private static function recordError($logLocation)
    {
        if($logLocation == 'nativePHP'){
            error_log(self::$errMsg." at ".self::$errLine.":".self::$errFile);
        }elseif($logLocation == 'engineDB'){
            $dbName = $this->enginevars->get("logDB");
            

            if(!(self::$db instanceof engineDB)){
                $e = EngineAPI::singleton();
                if(!self::$db = $e->getPrivateVar('engineDB')){
                    trigger_error('EngineAPI errorHandle - Cannot get to the engineDB!', E_USER_ERROR);
                    return FALSE;
                }
            }

            // First, we need to make sure the target table exists.
            $qTblSearch = self::$db->query(sprintf("SELECT * FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA`='%s' AND `TABLE_NAME`='%s'",
                self::$db->escape($dbName),
                self::$db->escape(self::$dbTblName)));
            if(!mysql_num_rows($qTblSearch['result'])){
                // We need to create this table!
                $qTblCreate = self::$db->query(sprintf('CREATE TABLE IF NOT EXISTS `%s`.`%s` (
                    `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `IP` varchar(15) NOT NULL,
                    `SID` varchar(32) NOT NULL,
                    `UserAgent` varchar(256) NOT NULL,
                    `File` varchar(256) NOT NULL,
                    `Line` int(10) unsigned NOT NULL,
                    `Severity` varchar(32) NOT NULL,
                    `Message` text NOT NULL,
                    `Information` text NOT NULL,
                    `Boxtrace` text NOT NULL,
                    PRIMARY KEY (`ID`),
                    KEY `Location` (`File`,`Line`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;', self::$db->escape($dbName), self::$db->escape(self::$dbTblName)));
                if($qTblCreate['error']){
                    // Crap, we can't create the table. We need to fallback to PHP's native logger
                    error_log(sprintf(" [errorHandle] Failed to auto-create database table '%s.%s'.\nOriginal Error: %s at %s:%s",
                        $dbName,self::$dbTblName,self::$errMsg,self::$errLine,self::$errFile));
                }
            }
            // Insert this error into the target table
            self::$db->query(sprintf("INSERT INTO `%s`.`%s` (`IP`,`SID`,`UserAgent`,`File`,`Line`,`Severity`,`Message`,`Information`,`Boxtrace`) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                self::$db->escape($dbName),
                self::$db->escape(self::$dbTblName),
                self::$db->escape(self::getRemoteIP()),
                self::$db->escape(session_id()),
                self::$db->escape(self::getUserAgent()),
                self::$db->escape(self::$errFile),
                self::$db->escape(self::$errLine),
                self::$db->escape(( (self::$errFile) ? self::phpErr2Str(self::$phpErrNo).' => '.self::severity2Str(self::$errSeverity) : self::severity2Str(self::$errSeverity) )),
                self::$db->escape(self::$errMsg),
                self::$db->escape(print_r(self::$errInfo, true)),
                self::$db->escape(self::generateBoxtrace(self::$backTrace))));
        }else{
            $logLocation = parse_url($logLocation);
            switch($logLocation['scheme']){
                case 'file':
                    touch($logLocation['path']);
                    if(isCLI()){
                        // CLI Interface
                        error_log(sprintf("[%s] [%s] [cli %s] %s at %s:%s\n",
                            date('D M d H:i:s Y'),
                            self::formattedSeverity(),
                            ( (array_key_exists('USER', $_SERVER)) ? $_SERVER['USER'] : 'unknown' ),
                            self::$errMsg,
                            self::$errLine,
                            self::$errFile), 3, $logLocation['path']);
                    }else{
                        // Web Interface
                        error_log(sprintf("[%s] [%s] [web %s] %s at %s:%s\n",
                            date('D M d H:i:s Y'),
                            self::formattedSeverity(),
                            $_SERVER['REMOTE_ADDR'],
                            self::$errMsg,
                            self::$errLine,
                            self::$errFile), 3, $logLocation['path']);
                    }
                    break;

                case 'email':
                    if(!(self::$email instanceof mailSender)){
                        self::$email = new mailSender();
                        self::$email->addSender(self::$emailSender);

                        $errMsg = (strlen(self::$errMsg) > 15) ? substr(self::$errMsg,0,15).'...' : self::$errMsg;
                        self::$email->addSubject(self::$emailSubject.sprintf('[%s] %s', self::severity2Str(self::$errSeverity), trim($errMsg)));

                        self::$email->addBody(sprintf("EngineAPI errorHandle Error!\n
                                %s
                                Severity: %s (%s)
                                File: %s
                                Line: %s

                                IP: %s
                                SID: %s
                                UserAgent: %s
                                Additional Info: %s

                                %s",
                                self::$errMsg,
                                self::formattedSeverity(),
                                self::$errorType,
                                self::$errFile,
                                self::$errLine,
                                self::getRemoteIP(),
                                session_id(),
                                self::getUserAgent(),
                                ( (isset(self::$errInfo) and sizeof(self::$errInfo)) ? print_r(self::$errInfo, true) : 'none' ),
                                self::generateBoxtrace(self::$backTrace)));
                    }
                    self::$email->addBCC(sprintf('%s@%s', $logLocation['user'], $logLocation['host']));
                    break;
            }
        }
    }

    /**
     * This method will return the error severity in a clean human-readable format
     * @static
     * @return string
     */
    private static function formattedSeverity()
    {
        if(self::$phpErrNo){
            return sprintf('[%s => %s]', self::phpErr2Str(self::$phpErrNo), self::severity2Str(self::$errSeverity));
        }else{
            return sprintf('[%s]', self::severity2Str(self::$errSeverity));
        }
    }

    /**
     * Converts PHP's error type to a human-readable version
     * @static
     * @param int $errNo
     * @return string
     */
    private static function phpErr2Str($errNo)
    {
		// PHP Error Constant -> String mapping table
		$phpErrors = array(
			E_PARSE             => 'E_PARSE',
			E_ERROR             => 'E_ERROR',
			E_WARNING           => 'E_WARNING',
			E_NOTICE            => 'E_NOTICE',
			E_CORE_ERROR        => 'E_CORE_ERROR',
			E_CORE_WARNING      => 'E_CORE_WARNING',
			E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_ALL               => 'E_ALL ');

		// Add error contants added in version 5.3
        if(defined('E_DEPRECATED'))      $phpErrors[E_DEPRECATED]      = 'E_DEPRECATED';
        if(defined('E_USER_DEPRECATED')) $phpErrors[E_USER_DEPRECATED] = 'E_USER_DEPRECATED';

		// And now do the mapping
        if(array_key_exists($errNo, $phpErrors)){
            return $phpErrors[$errNo];
        }else{
            return "UNKNOWN($errNo)";
        }
    }

    /**
     * Converts our internal error severity to a human-readable version
     * @static
     * @param int $severity
     * @return string
     */
    private static function severity2Str($severity)
    {
        $severities = array(
            self::INFO     => 'Informational',
            self::DEBUG    => 'Debug',
            self::LOW      => 'Low',
            self::MEDIUM   => 'Medium',
            self::HIGH     => 'High',
            self::CRITICAL => 'Critical'
        );
        if(array_key_exists($severity,$severities)){
            return $severities[$severity];
        }else{
            return 'Unknown';
        }
    }

    /**
     * This method will take a backtrace array from debug_backtrace() and format it into human-readable ASCII boxes
     * @static
     * @param array $backtrace
     * @param int $depth
     * @param array $hashTable
     * @return string
     */
    private static function generateBoxtrace($backtrace, $depth=0, $hashTable=array()){
        $boxWidth      = 100;
        $boxText       = '';
        $boxIndent     = str_repeat('  ', $depth);
        $backtraceNode = array_pop($backtrace);

        // Box header (only on node 0)
        if($depth == 0)
        {
            $boxText .= sprintf("%s+%s\n", $boxIndent, str_repeat("-", $boxWidth-strlen($boxIndent)));
            $boxText .= sprintf("%s| errorHandle Boxtrace\n", $boxIndent);
            $boxText .= sprintf("%s+%s\n", $boxIndent, str_repeat("-", $boxWidth-strlen($boxIndent)));
        }

        // Basic info (File and Line)
        $boxText .= sprintf("%s| File: %s\n", $boxIndent, ((isset($backtraceNode['file'])) ? $backtraceNode['file'] : "unknown" ));
        $boxText .= sprintf("%s| Line: %s\n", $boxIndent, ((isset($backtraceNode['line'])) ? $backtraceNode['line'] : "unknown" ));

        // Function / Object called
        if(isset($backtraceNode['function'])){
            switch(true){
                case isset($backtraceNode['type']) and $backtraceNode['type'] == "->":
                    $functionText = sprintf("$%s->%s()", $backtraceNode['class'], $backtraceNode['function']);
                    break;
                case isset($backtraceNode['type']) and $backtraceNode['type'] == "::":
                    $functionText = sprintf("%s::%s()", $backtraceNode['class'], $backtraceNode['function']);
                    break;
                default:
                    $functionText = sprintf("%s()", $backtraceNode['function']);
                    break;
            }
            $boxText .= sprintf("%s| Function called: %s\n", $boxIndent, $functionText);
        }

        // Args passed
        if(isset($backtraceNode['args']) and sizeof($backtraceNode['args'])){
            $boxText .= sprintf("%s| Args passed: \n", $boxIndent);
            foreach($backtraceNode['args'] as $number => $value){
                $dispData = self::toString($value);
                if(is_array($dispData)){
                    $hashTable[] = array($dispData[1], $dispData[2]);
                    $dispData = $dispData[0];
                }
                $boxText .= sprintf("%s|  [%s] %s\n", $boxIndent, $number+1, $dispData);
            }
        }

        // Table footer
        if(!sizeof($backtrace)){
            $strLen = $boxWidth-(strlen($boxIndent));
            if($strLen<1) $strLen=1;
            $boxText .= sprintf("%s+%s\n", $boxIndent, str_repeat("-", $strLen));
            // If there's any hashTable items, output them now!
            if(sizeof($hashTable)){
                $alreadyShown = array();
                $boxText .= "\n\nHash table:\n";
                foreach($hashTable as $hash){
                    if(array_search($hash[0], $alreadyShown) !== false) continue;
                    $boxText .= sprintf(" [%s]\n    %s\n\n", $hash[0], wordwrap($hash[1], 75, "\n    ", true));
                    $alreadyShown[] = $hash[0];
                }
            }
            return $boxText;
        }else{
            $strLen = $boxWidth-(strlen($boxIndent)+2);
            if($strLen<1) $strLen=1;
            $boxText .= sprintf("%s+-+%s\n", $boxIndent, str_repeat("-", $strLen));
            return $boxText.self::generateBoxtrace($backtrace, $depth+1, $hashTable);
        }
    }


    /**
     * Convert anything to a string representation of it
     * @static
     * @param mixed $data
     * @param bool $objHash
     * @return string
     */
    private static function toString($data, $objHash=false)
    {
        switch(true){
            case is_null($data):
                return "NULL";
                break;
            case is_bool($data):
                return ($data) ? "boolean(TRUE)" : "boolean(FALSE)";
                break;
            case is_int($data):
                return sprintf("int(%s)", $data);
                break;
            case is_float($data):
                return sprintf("float(%s)", $data);
                break;
            case is_string($data):
                return sprintf("string(%s)[%s]", strlen($data), $data);
                break;
            case is_array($data):
                if($objHash){
                    $arrayHash = self::encodeObject($data);
                    return array(sprintf("array(%sx%s)[%s]", self::arrayDepth($data), sizeof($data), md5($arrayHash)), md5($arrayHash), $arrayHash);
                }else{
                    return sprintf("array(%sx%s)", self::arrayDepth($data), sizeof($data));
                }
                break;
            case is_object($data):
                if($objHash){
                    $objectHash = self::encodeObject($data);
                    return array(sprintf("object(%s)[%s]", get_class($data), md5($objectHash)), md5($objectHash), $objectHash);
                }else{
                    return sprintf("object(%s)", get_class($data));
                }
                break;
			case is_array($data):
                return sprintf("array(%sx%s)", self::arrayDepth($data), sizeof($data));
                break;
            case is_resource($data):
                return sprintf("resource(%s)", get_resource_type($data));
                break;
        }
        return "[Unknown]";
    }

    /**
     * This method will take an object (or array), and return a compressed string representation of it.
     * The compression method used is based on http://us2.php.net/manual/en/function.base64-encode.php#87925
     * @static
     * @see self::decodeObject() for the reverse direction
     * @param object|array $obj
     * @return string
     */
    public static function encodeObject($obj)
    {
        if(!is_object($obj) and !is_array($obj)){
            return "[Not a valid object or array]";
        }
        return base64_encode(gzcompress(print_r($obj, true),9));
    }

    /**
     * This method is the reverse of encodeObject() and will return the print_r of the original 
     * @static
     * @param string $txt
     * @return string
     */
    public static function decodeObject($txt)
    {
        return gzuncompress(base64_decode($txt),9);
    }

    /**
     * Calculates the depth of an array using the indentation in the output of print_r().
     * @see http://us3.php.net/manual/en/function.print-r.php#93764
     * @param array $array
     * @return int
     */
    private static function arrayDepth($array)
    {
        $max_indentation = 1;
        $array_str = print_r($array, true);
        $lines = explode("\n", $array_str);
        foreach($lines as $line){
            $indentation = (strlen($line) - strlen(ltrim($line))) / 4;
            if($indentation > $max_indentation){
                $max_indentation = $indentation;
            }
        }
        return ceil(($max_indentation - 1) / 2) + 1;
    }

    /**
     * Sets the new internal errorReporting level.
     * @static
     * @param null|int $newValue
     *        The new errorReporting level. (omit to keep the current value)
     * @return int
     *         The old errorReporting
     */
    public static function errorReporting($newValue=NULL)
    {
        $oldValue = self::$errorReporting;
        if(!is_null($newValue)) self::$errorReporting = $newValue;
        return $oldValue;
    }

    /**
     * This function will look back through the debug_backtrace() and strip off any nodes which are inside this file.
     * Thus, getting back to the origin of the error.
     * @static
     * @return array
     */
    private static function getErrorOrigin()
    {
        $backTrace = debug_backtrace();
        while(sizeof($backTrace) and (!array_key_exists('file',$backTrace[0]) or $backTrace[0]['file'] == __FILE__)){
            array_shift($backTrace);
        }
        return $backTrace;
    }

    /**
     * Returns the remote IP of the user
     * @static
     * @return string
     */
    private static function getRemoteIP()
    {
        if (isCLI()) {
            if (array_key_exists('SSH_CONNECTION', $_SERVER)) {
                list($remoteIpAddr, $unknown1, $localIpAddr, $unknown2) = explode(' ', $_SERVER['SSH_CONNECTION']);
            } elseif (array_key_exists('SSH_CLIENT', $_SERVER)) {
                list($remoteIpAddr, $unknown1, $unknown2) = explode(' ', $_SERVER['SSH_CLIENT']);
            } else {
                $remoteIpAddr = 'unknown';
            }

            return $remoteIpAddr;
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Returns the user-agent string for the user's client
     * @static
     * @return string
     */
    private static function getUserAgent()
    {
        if(isCLI()){
            $ip = self::getRemoteIP();
            $user = (array_key_exists('USER', $_SERVER)) ? $_SERVER['USER'] : 'unknown';
            if(array_key_exists('SUDO_USER', $_SERVER)){
                return sprintf('%s (via sudo by %s) from %s', $user, $_SERVER['SUDO_USER'], $ip);
            }else{
                return sprintf('%s from %s', $user, $ip);
            }
        }else{
            return $_SERVER['HTTP_USER_AGENT'];
        }
    }

	/**
	 * Register a custom callback to handle call to prettyPrint()
	 *
	 * The callback should match the signature `string callbackFn($errorStack, $type)`:
	 * - $errorStack The engineAPI error stack
	 * - $type The type as passes to prettyPrint()
	 *
	 * @see self::prettyPrint()
	 * @param callable $callback
	 */
	public static function registerPrettyPrintCallback($callback){
		self::$prettyPrintCallback = $callback;
	}

	/**
	 * Clears (removes) a previously registered prettyPrint callback
	 * @see self::registerPrettyPrintCallback()
	 */
	public static function clearPrettyPrintCallback(){
		self::$prettyPrintCallback = NULL;
	}

	/**
	 * Record an error message and return HTML message
	 *
	 * @param string $msg
	 * @return string
	 */
	public static function errorMsg($msg)
    {
        self::errorStack(self::ERROR,$msg);
        return sprintf('<%s class="%s">%s</%s>', self::$uiSpanElement, self::$uiClassError, $msg, self::$uiSpanElement);
    }

	/**
	 * Record an success message and return HTML message
	 *
	 * @param string $msg
	 * @return string
	 */
    public static function successMsg($msg)
    {
        self::errorStack(self::SUCCESS,$msg);
        return sprintf('<%s class="%s">%s</%s>', self::$uiSpanElement, self::$uiClassSuccess, $msg, self::$uiSpanElement);
    }

	/**
	 * Record an warning message and return HTML message
	 *
	 * @param string $msg
	 * @return string
	 */
    public static function warningMsg($msg)
    {
        self::errorStack(self::WARNING,$msg);
        return sprintf('<%s class="%s">%s</%s>', self::$uiSpanElement, self::$uiClassWarning, $msg, self::$uiSpanElement);
    }

	/**
	 * Generates an HTML list of error/success/warning messages
	 *
	 * @param string $type Type of message to generate (all, error, success, warning)
	 * @return bool|string
	 */
	public static function prettyPrint($type="all")
    {
        if(!class_exists('EngineAPI', FALSE)){
            // There's no EngineAPI to read the errorStacks from, so there's no point trying
            return '';
        }

		$engine = EngineAPI::singleton();

		// If there's a custom prettyPrint callback registered use it!
		if (!isnull(self::$prettyPrintCallback)) {
			if (is_callable(self::$prettyPrintCallback)) {
				return call_user_func(self::$prettyPrintCallback, $engine->errorStack, $type);
			} else {
				errorHandle::newError(__METHOD__."() Cannot call registered prettyPrint callback!", errorHandle::DEBUG);
				return FALSE;
			}
		}

		if(isset($engine->errorStack[$type]) && sizeof($engine->errorStack[$type])){
			return self::makePrettyPrint($engine->errorStack[$type]);
		}

		return '';
	}

	/**
	 * Generate pretty print HTML based on given error stack
	 * @param array $errorStack   The stack of errors to print
	 * @param string $assumedType The assumed error type if we can't determine it
	 * @return string
	 */
	public static function makePrettyPrint($errorStack, $assumedType=self::ERROR){
		$output = '<ul class="errorPrettyPrint">';
		foreach($errorStack as $error){
			if(is_array($error)){
				$text = $error['message'];
				$type = $error['type'];
			}else{
				$text = $error;
				$type = $assumedType;
			}

			// Map Message Type -> CSS Class
			switch ($type) {
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
					/*
					 * A little trick:
					 * If $assumedType isn't in the cases above, it becomes the CSS class.
					 * (This is useful if we want to print a custom error stack with a custom CSS class)
					 */
					$class = $type;
					break;
			}

			$output .= sprintf('<li class="%s">%s</li>', $class, $text);
		}
		return "$output</ul>";
	}

	/**
	 * Adds a message onto an errorStack inside EngineAPI
	 *
	 * @param string $type Type of message
	 * @param string $message
	 * @return string
	 */
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
}