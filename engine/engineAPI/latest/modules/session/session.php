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
	const NULL='__NULL__1B3D7B90B01DDF1413A49751448B80A27CAE480AA0AB31CCA00F686BE5E51A10';

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

	/**
	 * Class constructor
	 *
	 * @param array $options Array of options
	 * @param EngineAPI $engineAPI
	 */
	private function __construct($options,$engineAPI=NULL){
		self::$engine         = isset($engineAPI) ? $engineAPI : EngineAPI::singleton();
		self::$optionsDefault = isset(EngineAPI::$engineVars['session']) ? EngineAPI::$engineVars['session'] : array();

		// Define template tags
		self::$engine->defTempPattern('/\{session\s+(.+?)\}/', 'session::templateHandler', $this);
		self::$engine->defTempPattern('/\{csrf}/', 'session::templateHandler_csrf', $this);
		self::$engine->defTempPattern('/\{csrfToken=[\'"](.+?)[\'"]}/', 'session::templateHandler_csrfToken', $this);
		self::$engine->defTempPattern('/\{csrfID=[\'"](.+?)[\'"]}/', 'session::templateHandler_csrfID', $this);

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
		if(self::$options['gcMaxlifetime'] < self::$options['cookieLifetime']){
			errorHandle::newError(__METHOD__."() - gcMaxlifetime is less than cookieLifetime! Setting gcMaxlifetime to gcMaxlifetime", errorHandle::LOW);
			self::$options['gcMaxlifetime'] = self::$options['cookieLifetime'];
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
	public static function singlton($options=array(),$engineAPI=NULL){
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
		$output  = sprintf('<input type="hidden" name="csrfID" id="csrfID" value="%s">', $csrf[0]);
		$output .= sprintf('<input type="hidden" name="csrfToken" id="csrfToken" value="%s">', $csrf[1]);
		return $output;
	}

	/**
	 * EngineAPI csrfToken template tag handler
	 *
	 * @param array $matches
	 * @return string
	 */
	public static function templateHandler_csrfToken($matches){
		return sprintf('<input type="hidden" name="csrfToken" id="csrfToken" value="%s">', $matches[1]);
	}

	/**
	 * EngineAPI csrfID template tag handler
	 *
	 * @param array $matches
	 * @return string
	 */
	public static function templateHandler_csrfID($matches){
		return sprintf('<input type="hidden" name="csrfID" id="csrfID" value="%s">', $matches[1]);
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

		// Start the session and bring it into the object
		session_start();
		self::$sessionData = $_SESSION;

		// Setup the basic SESSION layout (if it's needed)
		if(!sizeof(self::$sessionData)){
			self::$sessionData = array(
				'fingerprint' => self::browserFingerprint(),
				'csrf'        => array(),
				'private'     => array(),
				'data'        => array(),
				'flash'       => array('__old__' => array()),
			);
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

		// Update the flash data
		$newFlash = array('__old__'=>array());
		foreach(self::$sessionData['flash'] as $key => $value){
			if($key == '__old__') continue;
			$newFlash['__old__'][$key] = $value;
		}
		self::$sessionData['flash'] = $newFlash;

		// Lastly, flag the session as started, re-sync $_SESSION, and return
		self::$started = TRUE;
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
		$csrfToken = md5(uniqid(mt_rand(), true));
		self::$sessionData['csrf'][$csrfID] = array(
			'id'      => $csrfID,
			'token'   => $csrfToken,
			'created' => time());
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
			$validCSRF = self::$sessionData['csrf'][$id];

			// Has the token timmed out?
			if(($validCSRF['created']+self::$options['csrfTimeout']) < time()){
				// Yes - kill the session and return FALSE
				unset(self::$sessionData['csrf'][$id]);
				self::sync();
				return FALSE;
			}else{
				// No, but is the passes token correct?
				if($token === $validCSRF['token']){
					unset(self::$sessionData['csrf'][$id]);
					self::sync();
					return TRUE;
				}
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Clears all session data
	 */
	public static function clear(){
		self::$sessionData['data']  = array();
		self::$sessionData['flash'] = array(
			'__old__' => self::$sessionData['flash']['__old__']
		);
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
			return isset(self::$sessionData[$location][$name]);
		}else{
			// Okay, start looking for the data
			foreach($validLocations as $location){
				if(isset(self::$sessionData[$location][$name])) return TRUE;
			}
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
		// Define the valid locations, also sets the search order
		$validLocations = array('private','data','flash');
		// Normalize name
		$name = self::normalizeName($name);

		// Was a specific location requested?
		if($location){
			// Is it a valid location?
			if(!in_array($location, $validLocations)){
				errorHandle::newError(__METHOD__."() - Invalid location '$location' requested. (valid: ".implode(',', $validLocations).")", errorHandle::DEBUG);
				return $default;
			}
			// Okay, return the data if it exists
			$result = isset(self::$sessionData[$location][$name])
				? self::$sessionData[$location][$name]
				: $default;
		}else{
			// Okay, start looking for the data
			foreach($validLocations as $location){
				if(isset(self::$sessionData[$location][$name])){
					$result = self::$sessionData[$location][$name];
					break;
				}
			}
		}

		// Was a result never found?  (if so, use $default)
		if(!isset($result)) $result = $default;

		// Return the result and catch any encoded 'null' values
		return $result == self::NULL ? NULL : $result;
	}

	/**
	 * Set a value into the session
	 *
	 * @param string $name
	 *        Name of the value to set
	 * @param mixed $value
	 *        Value to be set
	 * @param bool $isFlash
	 *        Is this 'flash' data?
	 *        Flash data will only live for one request
	 * @param bool $isPrivate
	 *        Is this 'private' data
	 *        (Only EngineAPI and its modules can use this)
	 * @return bool
	 *         Was the setting successful?
	 */
	public static function set($name,$value,$isFlash=FALSE,$isPrivate=FALSE){
		// Normalize name
		$name = self::normalizeName($name);

		// Determine location and do any security/sanity checks
		if($isPrivate){
			$callingFile = realpath(callingFile());
			$engineDir   = realpath(EngineAPI::$engineDir);
			if(0 === strpos($callingFile, $engineDir)){
				// Valid
				$location='private';
			}else{
				// Invalid
				errorHandle::newError(__METHOD__."() - Access denied for set private", errorHandle::DEBUG);
				return FALSE;
			}
		}elseif($isFlash){
			if($name == '__old__'){
				errorHandle::newError(__METHOD__."() - Invalid name for flash data '$name'! (Reserved word)", errorHandle::DEBUG);
				return FALSE;
			}
			$location='flash';
		}else{
			$location='data';
		}

		// Encode 'Null' value
		if(is_null($value)) $value = self::NULL;

		// Set value, re-sync _SESSION, and return
		self::$sessionData[$location][$name] = $value;
		self::sync();
		return TRUE;
	}

	/**
	 * Destroy (delete) an item from the session data
	 * @param $name
	 */
	public static function destroy($name){
		$name = self::normalizeName($name);
		unset(self::$sessionData['data'][$name]);
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
	public static function gc(){
		// Kill off old csrf tokens
		foreach(self::$sessionData['csrf'] as $csrfID => $csrf){
			if(($csrf['created']+self::$options['csrfTimeout']) < time()){
				unset(self::$sessionData['csrf'][$csrfID]);
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
	 * @todo REMOVE BEFORE FLIGHT
	 */
	public static function debug(){
		echo "<pre><tt>Session data for sid '".session::id()."'\n".print_r($_SESSION,true)."</tt></pre><hr>";
	}
}

?>