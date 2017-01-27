<?php
/**
 * EngineAPI session manager
 * @author David Gersting
 * @version 1.0
 * @package EngineAPI\modules\session
 */

/**
 * EngineAPI session manager
 *
 * This is the class that the developer will use to directly interact with the session
 * This interaction is done through a set of static methods on this class, which allows the class to be used globally without needing to pass around an instance of it
 *
 * ##Important Note:
 * All session operations should be done though this module such as session::set() and session::get()
 * Any direct manipulation of $_SESSION will not be preserved between requests.
 *
 * @package EngineAPI\modules\session
 */
class session{
	/**
	 * Internal placeholder for NULL
	 */
	const NULL  = '__NULL__68RO5cLS1JZHKTO2u6OgeQXN854112PT';
	const TRUE  = '__TRUE__Hw4yMEX4O46TF33fIk2HXA2L272IhDRC';
	const FALSE = '__FALSE__5SV34oP28522Kzrao7R2c675O37EtdFm';

	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @var sessionDriverInterface
	 */
	private static $driver;

	/**
	 * @var EngineAPI
	 */
	private static $engine;

	/**
	 * Default session options from EngineAPI
	 * @var array
	 */
	private static $optionsDefault=array();

	/**
	 * Active session options
	 * @var array
	 */
	private static $options=array();

	/**
	 * Holds the fingerprint of the current browser
	 * @var string
	 */
	private static $browserFingerprint;

	/**
	 * Our internal, authoritative, data store for the session data
	 * @var array
	 */
	private static $sessionData=array();

	/**
	 * Internal flag indicating if the session as been started
	 * @var bool
	 */
	private static $started=FALSE;

	private $enginevars;

	/**
	 * Class constructor
	 *
	 * @param array $options Array of options
	 * @param EngineAPI $engineAPI
	 */
	private function __construct($options,$engineAPI=NULL){
		self::$engine         = isset($engineAPI) ? $engineAPI : EngineAPI::singleton();
		$this->set_enginevars(enginevars::getInstance());

		self::$optionsDefault = $this->enginevars->is_set("session") ? $this->enginevars->get("session") : array();

		// Define template tags
		templates::defTempPatterns('/\{session\s+(.+?)\}/', 'session::templateHandler', $this);
		templates::defTempPatterns('/\{csrf}/', 'session::templateHandler_csrf', $this);
		templates::defTempPatterns('/\{csrfToken=[\'"](.+?)[\'"]}/', 'session::templateHandler_csrfToken', $this);
		templates::defTempPatterns('/\{csrfID=[\'"](.+?)[\'"]}/', 'session::templateHandler_csrfID', $this);

		// Get the current cookie params (these will be used as the fail-safe defaults)
		$defaultCookieParams = session_get_cookie_params();

		// Determine active session options
		self::$options = array_merge(array(
			'name'             => isset(self::$optionsDefault['name'])             ? self::$optionsDefault['name']             : 'EngineAPI',
			'driver'           => isset(self::$optionsDefault['driver'])           ? self::$optionsDefault['driver']           : 'native',
			'autoStart'        => isset(self::$optionsDefault['autoStart'])        ? self::$optionsDefault['autoStart']        : TRUE,
			'fingerprintAttrs' => isset(self::$optionsDefault['fingerprintAttrs']) ? self::$optionsDefault['fingerprintAttrs'] : array('HTTP_USER_AGENT,REMOTE_ADDR'),
			'csrfTimeout'      => isset(self::$optionsDefault['csrfTimeout'])      ? self::$optionsDefault['csrfTimeout']      : 86400,
			'cookieLifetime'   => isset(self::$optionsDefault['cookieLifetime'])   ? self::$optionsDefault['cookieLifetime']   : $defaultCookieParams['lifetime'],
			'cookiePath'       => isset(self::$optionsDefault['cookiePath'])       ? self::$optionsDefault['cookiePath']       : $defaultCookieParams['path'],
			'cookieDomain'     => isset(self::$optionsDefault['cookieDomain'])     ? self::$optionsDefault['cookieDomain']     : $defaultCookieParams['domain'],
			'cookieSecure'     => isset(self::$optionsDefault['cookieSecure'])     ? self::$optionsDefault['cookieSecure']     : $defaultCookieParams['secure'],
			'cookieHttpOnly'   => isset(self::$optionsDefault['cookieHttpOnly'])   ? self::$optionsDefault['cookieHttpOnly']   : $defaultCookieParams['httponly'],
			'gcProbability'    => isset(self::$optionsDefault['gcProbability'])    ? self::$optionsDefault['gcProbability']    : 1,
			'gcDivisor'        => isset(self::$optionsDefault['gcDivisor'])        ? self::$optionsDefault['gcDivisor']        : 100,
			'gcMaxlifetime'    => isset(self::$optionsDefault['gcMaxlifetime'])    ? self::$optionsDefault['gcMaxlifetime']    : $defaultCookieParams['lifetime']*2,
		), (array)$options);

		/*
		 * Normalize the options
		 *   - Make sure session name doesn't have spaces
		 *   - Make sure fingerprintAttrs is an array if it's a CSV
		 *   - Make sure driver is formatted correctly if there's only 1 driver with no options
		 */
		self::$options['name'] = str_replace(' ','_',self::$options['name']);
		if(is_string(self::$options['fingerprintAttrs'])) self::$options['fingerprintAttrs'] = explode(',', self::$options['fingerprintAttrs']);
		if(!is_array(self::$options['driver'])) self::$options['driver'] = array(self::$options['driver'] => array());

		/*
		 * Catch any really stupid options
		 *   - gcMaxlifetime is less than cookieLifetime
		 */
		if(self::$options['gcMaxlifetime'] < self::$options['cookieLifetime'] || (self::$options['gcMaxlifetime'] > 0 && !self::$options['cookieLifetime'])){
			error_log(__METHOD__."() - gcMaxlifetime is less than cookieLifetime! Setting cookieLifetime to gcMaxlifetime");
			self::$options['cookieLifetime'] = self::$options['gcMaxlifetime'];
		}

		// Set the session cookie params
		session_set_cookie_params(
			self::$options['cookieLifetime'],
			self::$options['cookiePath'],
			self::$options['cookieDomain'],
			self::$options['cookieSecure'],
			self::$options['cookieHttpOnly']);

		// Set garbage collection values
		ini_set('session.gc_probability', self::$options['gcProbability']);
		ini_set('session.gc_divisor', self::$options['gcDivisor']);
		ini_set('session.gc_maxlifetime', self::$options['gcMaxlifetime']);

		// Startup the session driver
		foreach(self::$options['driver'] as $driverName => $driverOptions){
			// Determine the driver class' name and filename
			$driverClassName     = "sessionDriver".ucfirst(trim($driverName));
			$driverClassFilename = __DIR__.DIRECTORY_SEPARATOR."drivers".DIRECTORY_SEPARATOR.$driverClassName.".php";
			if(is_readable($driverClassFilename)){
				require_once $driverClassFilename;
				self::$driver = new $driverClassName($this,$driverOptions);
				if(self::$driver->isReady()) break;
			}
		}

		// If there's still no driver, or if it's not ready we have a problem!
		if(!(self::$driver instanceof sessionDriverInterface) or !self::$driver->isReady()){
			errorHandle::newError(__METHOD__."() - Session driver failed to load", errorHandle::CRITICAL);
		}

		// Calculate browser fingerprint
		$fingerprint='';
		foreach(self::$options['fingerprintAttrs'] as $fingerprintAttr){
			$fingerprintAttr = trim(strtoupper($fingerprintAttr));
			if(isset($_SERVER[$fingerprintAttr])){
				$fingerprint .= $_SERVER[$fingerprintAttr];
			}
		}
		self::$browserFingerprint = md5($fingerprint);

		// Apply the session name
		session_name(self::$options['name']);

		// Lastly, should we auto-start?
		if(self::$options['autoStart']) self::start();
	}

	public function set_enginevars($enginevars) {
		$this->enginevars = $enginevars;
	}

	/**
	 * Initialize the session handler
	 *
	 * ## Available Options:
	 * - name:             Default session name
	 * - driver:           List of back-end drivers to use for session data storage (see expanded docs below)
	 * - autoStart:        Automatically start the session w/o having to call session::start()
	 * - fingerprintAttrs: Array or CSV of nodes of $_SERVER which will be used to calculate the browser fingerprint
	 * - csrfTimeout:      Length of time after which a csrf token will no longer be accepted (will have no effect if cookieLifetime is less)
	 * - cookieLifetime:   Length of time the session's cookie should live
	 * - cookiePath:       The session cookie's path param (controls what paths the cookie is visible on)
	 * - cookieDomain:     The session cookie's domain param (controls what domain the cookie is visible on)
	 * - cookieSecure:     If TRUE, the cookie will only be valid over https
	 * - cookieHttpOnly:   If TRUE, the cookie will have the httponly flag set (making it visible only on the http(s) protocol)
	 * - gcProbability:    The probability of garbage collection running (will be the numerator for the probability)
	 * - gcDivisor:        The divisor of probability for garbage collection (ex: 100 sets gcProbability to be %'s of 100)
	 * - gcMaxlifetime:    At what point does the garbage collector see old data as 'garbage' (This should never be less than cookieLifetime)
	 *
	 * ## Driver Options:
	 * - You can specify one or more drivers which will act as fall-backs in case of failure with the order they are listed being the order they are tried
	 * - Currently, the following drivers are available: native,filesystem,database
	 *   - Native: PHP's native session handler
	 *      - [No options available]
	 *   - Filesystem: Custom flat-file based storage system
	 *      - [Options listed in docs for sessionDriverFilesystem]
	 *   - Database: MySQL Database backend
	 *      - [Options listed in docs for sessionDriverDatabase]
	 *
	 * @param array $options Array of options
	 * @param EngineAPI $engineAPI
	 * @return session
	 */
	public static function singleton($options=array(),$engineAPI=NULL){
		if(!self::$instance) self::$instance = new self($options,$engineAPI);
		return self::$instance;
	}

	/**
	 * Returns the instance of the session manager
	 *
	 * @return session
	 */
	public static function getInstance(){
		return self::$instance;
	}

	/**
	 * Returns the instance of the session manager
	 *
	 * @return session
	 */
	public static function getEngine(){
		return self::$engine;
	}

	/**
	 * EngineAPI template tag handler
	 *
	 * @param array $matches
	 * @return string
	 */
	public static function templateHandler($matches){
		$attPairs = attPairs($matches[1]);
		switch(true){
			case isset($attPairs['get']):
				$output = isset($attPairs['default'])
					? self::get($attPairs['get'], $attPairs['default'])
					: self::get($attPairs['get']);
				break;
			case isset($attPairs['csrf']):
				return templateHandler_csrfToken();
			case isset($attPairs['csrfToken']):
				return templateHandler_csrfToken(array($matches[0], $attPairs['csrfToken']));
			case isset($attPairs['csrfID']):
				return templateHandler_csrfID(array($matches[0], $attPairs['csrfID']));
			default;
				$output = '[Invalid session tag]';
				break;
		}
		return $output;
	}

	/**
	 * EngineAPI csrf template tag handler
	 *
	 * @return string
	 */
	public static function templateHandler_csrf(){
		$csrf    = self::csrfTokenRequest();
		$output  = sprintf('<input type="hidden" name="__csrfID" id="__csrfID" value="%s">', $csrf[0]);
		$output .= sprintf('<input type="hidden" name="__csrfToken" id="__csrfToken" value="%s">', $csrf[1]);
		$output .= sprintf('<input type="hidden" name="engineCSRFCheck" id="engineCSRFCheck" value="%s">', $csrf[1]);
		return $output;
	}

	/**
	 * EngineAPI csrfToken template tag handler
	 *
	 * @param array $matches
	 * @return string
	 */
	public static function templateHandler_csrfToken($matches){
		return sprintf('<input type="hidden" name="__csrfToken" id="__csrfToken" value="%s">', $matches[1]);
	}

	/**
	 * EngineAPI csrfID template tag handler
	 *
	 * @param array $matches
	 * @return string
	 */
	public static function templateHandler_csrfID($matches){
		return sprintf('<input type="hidden" name="__csrfID" id="__csrfID" value="%s">', $matches[1]);
	}

	/**
	 * Restore a previous session
	 *
	 * @see self::__construct()
	 * @param string $sessionID
	 * @param string $sessionKey
	 * @param array $options
	 *        An array of options to use if the session hasn't not bee started yet
	 *        These will only take effect if self::started() is FALSE
	 *        See self::__construct for valid options
	 */
	public static function restore($sessionID,$sessionKey,$options=array()){
		if(self::$instance){
			// Already running session - We need to close it and reopen the new
			self::stop();
			session_id($sessionID);
			self::start($sessionKey);
		}else{
			// No running session - We can just declare the session ID and instantiate this object
			session_id($sessionID);
			self::$instance = new self($options);
		}
	}

	/**
	 * Returns the back-end session driver
	 *
	 * @return sessionDriverInterface
	 */
	public static function getDriver(){
		return self::$driver;
	}

	/**
	 * Manually triggers session save method
	 * This might be a little expensive depending on the driver as a full write/read cycle will occur in the driver
	 */
	public static function save(){
		session_write_close();
		session_start();
	}

	/**
	 * Syncs internal data store to external $_SESSION super-global
	 */
	public static function sync(){
		$_SESSION = self::$sessionData;
	}

	/**
	 * Starts the session
	 *
	 * @param string $sessionKey
	 * @return bool
	 */
	public static function start($sessionKey=NULL){
		if(self::started()) return TRUE;

		// Start the session
		self::$started = session_start();
		if(!self::$started){
			errorHandle::newError(__METHOD__."() PHP session failed to start!", errorHandle::HIGH);
			return FALSE;
		}

		// Bring the session data into the object
		self::$sessionData = $_SESSION;

		// Setup the basic SESSION layout (if it's needed)
		if(!isset(self::$sessionData['fingerprint'])){
			self::$sessionData['fingerprint'] = self::browserFingerprint();
			self::clear();
		}

		// Before we do anything else, check the browser's fingerprint
		if(!isset($sessionKey)) $sessionKey = self::browserFingerprint();
		if(self::$sessionData['fingerprint'] != $sessionKey){
			// Invalid fingerprint!
			$errMsg = sprintf(__METHOD__."() - Browser fingerprint check failed! (IP: %s Hostname: %s Useragent: %s)",
				$_SERVER['REMOTE_ADDR'],
				gethostbyaddr($_SERVER['REMOTE_ADDR']),
				$_SERVER['HTTP_USER_AGENT']);
			errorHandle::newError($errMsg, errorHandle::HIGH);
			self::reset();
			return FALSE;
		}

		/*
		 * Make sure sync runs when PHP shuts down
		 * This is a little odd, but what it does is;
		 * When PHP shuts down, it goes though and calls all registered shutdown functions in the order in which they were registered
		 * When this function fires, it registers a new function (at the end of the que) which will force $_SESSION to get synced
		 */
		register_shutdown_function(function(){
			register_shutdown_function(function(){
				session::sync();
			});
		});

		// Expire the old flash data
		unset(self::$sessionData['flash']['__old__']);
		self::$sessionData['flash'] = array('__old__' => self::$sessionData['flash']);

		// Perform garbage cleanup on ourselves
		self::gc();

		// If there is a logout token in the URL and it's correct, then log the user out!
		if(isset($_GET['MYSQL']['logoutToken']) && $_GET['MYSQL']['logoutToken'] == self::get('logoutToken')){
			self::reset();
		}

		// Lastly, re-sync $_SESSION, and return
		self::sync();
		return TRUE;
	}

	/**
	 * Stops the session
	 */
	public static function stop(){
		self::sync();
		self::$started = FALSE;
		session_write_close();
	}

	/**
	 * Has the session been started?
	 * @return bool
	 */
	public static function started(){
		return self::$started;
	}

	/**
	 * Overwrites the session data with the provided array
	 * @param array|string $data Raw data array or serialized string
	 */
	public static function import($data){
		self::$sessionData['data'] = is_string($data)
			? unserialize($data)
			: $data;
		self::sync();
	}

	/**
	 * Export session data suitable for import
	 * @see self::import
	 * @return string Serialized session data
	 */
	public static function export(){
		return serialize(self::$sessionData['data']);
	}

	/**
	 * Returns the session's ID
	 * @return string
	 */
	public static function id(){
		return session_id();
	}

	/**
	 * Returns the session's name
	 * @return string
	 */
	public static function name(){
		return session_name();
	}

	/**
	 * Returns the browser fingerprint
	 * @return string
	 */
	public static function browserFingerprint(){
		return self::$browserFingerprint;
	}

	/**
	 * Requests a new csrf token/id pair
	 * Will return an array with the id in index 0 and the token in index 1
	 * @return array
	 */
	public static function csrfTokenRequest(){
		$csrfID    = uniqid();
		$csrfToken = self::genToken();
		$options   = array(
			'location' => 'csrf',
			'timeout' => self::$options['csrfTimeout']
		);

		self::set("CSRF",$csrfToken);
		self::set($csrfID, $csrfToken, $options);
		self::sync();
		return array($csrfID,$csrfToken);
	}

	/**
	 * Checks a given CSRF token against the correct one in the session
	 *
	 * @param string $id
	 * @param string $token
	 * @return bool
	 */
	public static function csrfTokenCheck($id,$token){
		if(isset(self::$sessionData['csrf'][$id])){
			if($token === self::$sessionData['csrf'][$id]){
				unset(self::$sessionData['csrf'][$id]);
				self::sync();
				return TRUE;
			}
			return FALSE;
		}
		return FALSE;
	}

	/**
	 * Generate a random token string
	 * @return string
	 */
	private static function genToken(){
		return md5(uniqid(mt_rand(), TRUE));
	}

	/**
	 * Clears all session data
	 */
	public static function clear(){
		self::$sessionData['csrf']     = array();
		self::$sessionData['private']  = array();
		self::$sessionData['data']     = array();
		self::$sessionData['timeouts'] = array();
		self::$sessionData['flash']    = array('__old__' => array());
		self::sync();
	}

	/**
	 * Reset the session
	 *
	 * @todo Reset 'private' data as well (needs way to re-init it after the reset)
	 * @param bool $keepData
	 */
	public static function reset($keepData=FALSE){
		/*
		 * Generate a new session id, and delete the old session
		 * Also, save the pre and post sid for any event listeners
		 */
		$oldSID = self::id();
		session_regenerate_id(TRUE);
		$newSID = self::id();

		// If we're not keeping the old data, then we need to clear it
		if(!$keepData) self::clear();

		// Make sure session is synced
		self::sync();
	}

	/**
	 * Does the requested setting exist in the session?
	 *
	 * @param string $name
	 *        Name of the value to retrieve
	 * @param string $location
	 *        You can explicitly define the location to look for $name
	 *        Valid locations: 'private', 'data', 'flash'
	 * @return bool
	 */
	public static function has($name,$location=NULL){
		// Define the valid locations, also sets the search order
		$validLocations = array('private','data','flash');
		// Normalize name
		$name = self::normalizeName($name);

		// Was a specific location requested?
		if($location){
			// Is it a valid location?
			if(!in_array($location, $validLocations)){
				errorHandle::newError(__METHOD__."() - Invalid location '$location' requested. (valid: ".implode(',', $validLocations).")", errorHandle::DEBUG);
				return FALSE;
			}
			// Okay, return the data if it exists
			return NULL !== array_get(self::$sessionData[$location], $name);
		}else{
			// Okay, start looking for the data
			foreach($validLocations as $location){
				if(NULL !== array_get(self::$sessionData[$location], $name)) return TRUE;
			}
			// Last chance is for old flash data. If it's not there, it's not anywhere
			return NULL !== array_get(self::$sessionData['flash']['__old__'], $name);
		}
	}

	/**
	 * Check that the requested data location is value for the given user
	 * @param string $location
	 * @param string $name
	 * @return bool
	 */
	private static function checkLocation($location, $name=NULL){
		$callingFile = realpath(callingFile(FALSE, 2));
		switch($location){
			case 'csrf':
			case 'timeouts':
				if($callingFile != __FILE__){
					errorHandle::newError(__METHOD__."() - Access denied for set $location", errorHandle::DEBUG);
					return FALSE;
				}
				return TRUE;

			case 'private':
				$engineDir = realpath(EngineAPI::$engineDir);
				if(0 !== strpos($callingFile, $engineDir)){
					// Invalid
					errorHandle::newError(__METHOD__."() - Access denied for set private", errorHandle::DEBUG);
					return FALSE;
				}
				return TRUE;

			case 'flash':
				if($name == '__old__'){
					errorHandle::newError(__METHOD__."() - Invalid name for flash data '$name'! (Reserved word)", errorHandle::DEBUG);
					return FALSE;
				}
				return TRUE;

			case 'data':
				return TRUE;

			default:
				errorHandle::newError(__METHOD__."() - Invalid location! '$location'!", errorHandle::DEBUG);
				return FALSE;

		}
	}



	/**
	 * Get a value from the session
	 *
	 * @param string $name
	 *        Name of the value to retrieve (Case insensitive)
	 * @param mixed $default
	 *        The default value if the requested name doesn't exist
	 * @param string $location
	 *        You can explicitly define the location('private', 'data', or 'flash') to look for $name<br>
	 *        If no location is provided will search 'private' then 'data' then 'flash' for $name
	 * @return mixed
	 */
	public static function get($name,$default=NULL,$location=NULL){
		// Normalize name
		$name = self::normalizeName($name);

		// Was a specific location requested?
		if($location){
			// Check the location
			if(!self::checkLocation($location, $name)) return FALSE;
			// Get the requested value
			$result = array_get(self::$sessionData[$location], $name);
			// If we're looking for flash, also try old flash
			if($result === NULL && $location == 'flash' && isset(self::$sessionData[$location.'.__old__'])){
				$result = array_get(self::$sessionData[$location.'.__old__'], $name);
			}
		}else{
			// Okay, start looking for the data
			foreach(array('private','data','flash','flash.__old__') as $location){
				$result = array_get(self::$sessionData, "$location.$name");
				if(NULL !== $result) break;
			}
		}

		// The result might be a broken object, try and fix it!
		fixObject($result);

		// Catch the case where the requested value doesn't exist
		if($result === NULL) $result = $default;

		// Return the value (taking care of the encoded booleans)
		switch($result){
			case self::NULL:
				return NULL;

			case self::TRUE:
				return TRUE;

			case self::FALSE:
				return FALSE;

			default:
				return $result;
		}
	}

	/**
	 * Set a value into the session
	 *
	 * @param string $name
	 *        Name of the value to set
	 * @param mixed $value
	 *        Value to be set
	 * @param array $options
	 *        Options for this session data
	 *         - location
	 *           Sets the location to save this data (valid: data, flash, private)
	 *           Notes:
	 *            - Flash data will only live for one request
	 *            - Only EngineAPI and its modules can use private
	 *         - timeout
	 *           Lets you set a timeout for the data.
	 *           This can either be UNIX timestamp or the number of seconds the data should live
	 * @return bool
	 *         Was the setting successful?
	 */
	public static function set($name,$value,$options=array()){
		// Normalize name
		$name = self::normalizeName($name);

		$options = array_merge(array(
			'location' => 'data',
		), (array)$options);

		// Check the location
		if(!self::checkLocation($options['location'], $name)) return FALSE;

		// Encode booleans (NULL, TRUE, FALSE)
		if(NULL === $value) $value = self::NULL;
		if(TRUE === $value) $value = self::TRUE;
		if(FALSE === $value) $value = self::FALSE;

		// Expand dot-notation and set the value
		array_set(self::$sessionData[ $options['location'] ], $name, $value);

		// Is there a timeout set for this data?
		if(isset($options['timeout'])){
			$now = time();
			// If the timeout is less than time, add time to it (this gives us 'timeout in n seconds from now')
			if($options['timeout'] < $now) $options['timeout'] += $now;
			// Record this timeout
			self::$sessionData['timeouts'][ $options['timeout'] ][] = $options['location'].".$name";
		}

		// Sync _SESSION, and return
		self::sync();
		return TRUE;
	}

	/**
	 * Destroy (delete) an item from the session data
	 * @param string $name
	 * @param string $location
	 *        Location to delete $name from (default to data)
	 * @return bool
	 */
	public static function destroy($name,$location='data'){
		// Normalize name
		$name = self::normalizeName($name);

		// Check the location
		if(!self::checkLocation($location, $name)) return FALSE;

		// Delete it! (if it exists)
		array_unset(self::$sessionData[$location], $name);

		self::sync();
		return TRUE;
	}

	/**
	 * [Alias] Get flash data
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function flashGet($name,$default=NULL){
		return self::get($name, $default, 'flash');
	}

	/**
	 * [Alias] Set flash data
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public static function flashSet($name,$value){
		return self::set($name, $value, array('location' => 'flash'));
	}

	/**
	 * [Alias] Destroy flash data
	 * @param $name
	 * @return bool
	 */
	public static function flashDestroy($name){
		return self::destroy($name, 'flash');
	}

	/**
	 * [Alias] Does flash have a given piece of data
	 * @param $name
	 * @return bool
	 */
	public static function flashHas($name){
		return self::has($name, 'flash') || self::has($name, 'flash.__old__');
	}

	/**
	 * [Alias] Get flash data
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function privateGet($name,$default=NULL){
		return self::get($name, $default, 'private');
	}

	/**
	 * [Alias] Set flash data
	 * @param string $name
	 * @param mixed $value
	 * @return bool
	 */
	public static function privateSet($name,$value){
		return self::set($name, $value, array('location' => 'private'));
	}

	/**
	 * [Alias] Does private have a given piece of data
	 * @param $name
	 * @return bool
	 */
	public static function privateHas($name){
		return self::has($name, 'private');
	}

	/**
	 * [Alias] Destroy flash data
	 * @param $name
	 * @return bool
	 */
	public static function privateDestroy($name){
		return self::destroy($name, 'private');
	}


	/**
	 * Causes flash data to last for one more request
	 *
	 * @param array|string $names
	 *        Either an array or CSV of names of settings to copy over
	 *        (Array is more efficient)
	 * @param string $delim
	 *        The char to use as the delimiter for CSV
	 * @param bool $overwrite
	 *        If TRUE, this will overwrite any values encountered in target location
	 */
	public static function reflash($names=NULL,$delim=',',$overwrite=TRUE){
		if(is_null($names)) $names = array_keys(self::$sessionData['flash']['__old__']);
		if(is_string($names)) $names = explode($delim,$names);
		$names = array_map('self::normalizeName',$names);
		foreach($names as $name){
			if($name == '__old__' or !isset(self::$sessionData['flash']['__old__'][$name])) continue;
			if(isset(self::$sessionData['flash'][$name])){
				if($overwrite){
					errorHandle::newError(__METHOD__."() - Overwriting '$name'", errorHandle::DEBUG);
					self::$sessionData['flash'][$name] = self::$sessionData['flash']['__old__'][$name];
				}else{
					errorHandle::newError(__METHOD__."() - Skipping '$name' (already exists)", errorHandle::DEBUG);
				}
			}else{
				self::$sessionData['flash'][$name] = self::$sessionData['flash']['__old__'][$name];
			}
		}
		self::sync();
	}

	/**
	 * Moves flash data to persistent session data
	 *
	 * @param array|string $names
	 *        Either an array or CSV of names of settings to copy over
	 *        (Array is more efficient)
	 * @param string $delim
	 *        The char to use as the delimiter for CSV
	 * @param bool $overwrite
	 *        If TRUE, this will overwrite any values encountered in target location
	 */
	public static function keep($names=NULL,$delim=',',$overwrite=TRUE){
		if(is_null($names)) $names = array_keys(self::$sessionData['flash']['__old__']);
		if(is_string($names)) $names = explode($delim,$names);
		$names = array_map('self::normalizeName',$names);
		foreach($names as $name){
			if($name == '__old__' or !isset(self::$sessionData['flash']['__old__'][$name])) continue;
			if(isset(self::$sessionData['data'][$name])){
				if($overwrite){
					errorHandle::newError(__METHOD__."() - Overwriting '$name'", errorHandle::DEBUG);
					self::$sessionData['data'][$name] = self::$sessionData['flash']['__old__'][$name];
				}else{
					errorHandle::newError(__METHOD__."() - Skipping '$name' (already exists)", errorHandle::DEBUG);
				}
			}else{
				self::$sessionData['data'][$name] = self::$sessionData['flash']['__old__'][$name];
			}
		}

		foreach($names as $name){
			// Skip over names that aren't in the flash data
			if(!isset(self::$sessionData['flash']['__old__'][$name])) continue;

			if(isset(self::$sessionData['flash'][$name])){
				if($overwrite){
					errorHandle::newError(__METHOD__."() - Overwriting '$name'", errorHandle::DEBUG);
					self::$sessionData['flash'][$name] = self::$sessionData['flash']['__old__'][$name];
				}else{
					errorHandle::newError(__METHOD__."() - Skipping '$name' (already exists)", errorHandle::DEBUG);
				}
			}else{
				self::$sessionData['flash'][$name] = self::$sessionData['flash']['__old__'][$name];
			}
		}
		self::sync();
	}

	/**
	 * Perform garbage collection for this session
	 */
	private static function gc(){
		// Kill off old, timed-out, data
		if (sizeof(self::$sessionData['timeouts'])) {
			$now = time();
			foreach (self::$sessionData['timeouts'] as $dieAt => $names) {
				if ($dieAt <= $now){
					foreach($names as $name){
						list($location,$name) = explode('.', $name, 2);
						self::destroy($name, $location);
					}
					self::destroy($dieAt, 'timeouts');
				}
			}
		}

		// Lastly, sync any changes made
		self::sync();
	}

	/**
	 * Normalize the name used for a session var
	 *
	 * @param string $name
	 * @return string
	 */
	private static function normalizeName($name){
		return str_replace(' ','_',strtolower(trim($name)));
	}

	/**
	 * Return logout token
	 * @return mixed
	 */
	public static function logoutToken(){
		$logoutToken = self::genToken();
		self::flashSet('logoutToken', $logoutToken);
		return $logoutToken;
	}
}

?>
