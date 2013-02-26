<?php
$engineVars        = array();
$engineVarsPrivate = array();

/**
 * EngineAPI Class
 *
 * This is the heart of the EngineAPI system. This is a singleton class where only 1 instance is ever allowed
 * @see self::singleton()
 */
class EngineAPI{
	const VERSION='4.0';

	/**
	 * @var self
	 */
	private static $instance; // Hold an instance of this object, for use as Singleton
	/**
	 * @var string
	 */
	public static $engineDir;
	/**
	 * @var array
	 */
	public static $engineVars = array();
	/**
	 * @var array
	 */
	public $errorStack = array();
	/**
	 * If set to false, the engine tags will not be processed.
	 * @var bool
	 */
	public $obCallback = TRUE; //
	/**
	 * $engineVars['currentTemplate']
	 * @var string
	 */
	public $template = "";
	/**
	 * Current working directory
	 * @var string
	 */
	public $cwd = "";
	/**
	 * @todo appears unused - Remove?
	 * @var array
	 */
	private $DEBUG = array();

	# Used for page access/security
	#############################################################
	/**
	 * Sanitized $_GET
	 * @var array
	 */
	public $cleanGet  = array();
	/**
	 * Sanitized $_POST
	 * @var array
	 */
	public $cleanPost = array();

	private $accessMethods    = "";
	private $accessExistsTest = TRUE;
	private $acl              = array();
	private $aclgroups        = array();
	private $aclCount         = 0;

	# Used for database connections
	#############################################################
	/**
	 * Database Username
	 * @var string
	 */
	private $dbUsername = "";
	/**
	 * Database Password
	 * @var string
	 */
	private $dbPassword = "";
	/**
	 * Database Name
	 * @var string
	 */
	private $dbDatabase = "";
	/**
	 * Database Server hostname/ip
	 * @var string
	 */
	private $dbServer = "";
	/**
	 * Database Port
	 * @var string
	 */
	private $dbPort = "";
	/**
	 * Database table name mappings
	 * @var array
	 */
	private $dbTables = array();
	/**
	 * The EngineAPI database object
	 * @var engineDB
	 */
	private $engineDB = NULL;
	/**
	 * The database object
	 * @var engineDB
	 */
	public $openDB = NULL;

	# Module Stuffs
	#############################################################
	/**
	 * Stack of available modules
	 * @var array
	 */
	private $availableModules = array();
	/**
	 * Array of loaded libraries (for auto-loading)
	 * @var array
	 */
	public $library = array();
	/**
	 * Function extensions
	 * @var array
	 */
	private $functionExtensions = array();

	# Module Template Mathes and function calls for displayTemplate()
	###################################################################
	private static $moduleTemplateEngine   = array();
	private $recurseCount           = 0; // In module template matches, prevent infinite recursion
	private $recurseLevel           = 3;
	private $recurseNeeded          = FALSE;
	private $displayTemplateOff     = FALSE;
	private $engineVarsPrivate      = array();

	/**
	 * Cloning is not allowed!
	 */
	public function __clone() {
		trigger_error('Cloning instances of this class is forbidden.', E_USER_ERROR);
	}

	/**
	 * Serialization/De-serialization is not allowed!
	 */
	public function __wakeup() {
		trigger_error('Unserializing instances of this class is forbidden.', E_USER_ERROR);
	}

	/**
	 * Let us begin...
	 *
	 * @param string $site Name of the site config to use
	 */
	private function __construct($site="default") {
		self::$engineDir = dirname(__FILE__);

		// make sure the session cookie is only accessible via HTTP
		ini_set("session.cookie_httponly", 1);

		// ob_start('EngineAPI::displayTemplate');

		// setup private config variables
		require_once(self::$engineDir."/config/defaultPrivate.php");
		if($site != "default" && $site != "defaultPrivate"){
			$siteConfigFile = self::$engineDir."/config/".$site."Private.php";
			require_once($siteConfigFile);
		}
		$this->engineVarsPrivate = $engineVarsPrivate;
		unset($engineVarsPrivate);

		// setup $engineVars
		require_once(self::$engineDir."/config/default.php");
		if($site != "default" && $site != "defaultPrivate"){
			$siteConfigFile = self::$engineDir."/config/".$site.".php";
			require_once($siteConfigFile);
		}
		self::$engineVars = $engineVars;

		// $engineVars - backward compatibility
		global $engineVars;
		$engineVar =& self::$engineVars;

		//Load Access Control Modules
		$hfDirHandle = @opendir($engineVars['helperFunctions']) or die("Unable to open ".$engineVars['helperFunctions']);
		while (false !== ($file = readdir($hfDirHandle))) {
			// Check to make sure that it isn't a hidden file and that it is a PHP file
			if ($file != "." && $file != ".." && $file) {
				$fileChunks = array_reverse(explode(".", $file));
				$ext= $fileChunks[0];
				if ($ext == "php") {
					require_once($engineVars['helperFunctions']."/".$file);
				}
			}
		}

		require_once(self::$engineDir."/sessionManagement.php");
		require_once(self::$engineDir."/userInfo.php");

		// Setup Current Working Directory
		$this->cwd = getcwd();

		// Setup initial Template
		$this->template = $engineVars['tempDir']."/".$engineVars['templateDefault'];
		$engineVars['currentTemplate'] = $this->template;

		// Setup default database connections
		$this->dbUsername = ($this->engineVarsPrivate['mysql']['username'])?$this->engineVarsPrivate['mysql']['username']:NULL;
		$this->dbPassword = ($this->engineVarsPrivate['mysql']['password'])?$this->engineVarsPrivate['mysql']['password']:NULL;
		$this->dbPort     = ($this->engineVarsPrivate['mysql']['port'])?$this->engineVarsPrivate['mysql']['port']:NULL;
		$this->dbServer   = ($this->engineVarsPrivate['mysql']['server'])?$this->engineVarsPrivate['mysql']['server']:NULL;

		//Load Access Control Modules
		$accessModDirHandle = @opendir($engineVars['accessModules']) or die("Unable to open ".$engineVars['accessModules']);
		while (false !== ($file = readdir($accessModDirHandle))) {
			// Check to make sure that it isn't a hidden file and that it is a PHP file
			if ($file != "." && $file != ".." && $file) {
				$fileChunks = array_reverse(explode(".", $file));
				$ext= $fileChunks[0];
				if ($ext == "php") {
					include_once($engineVars['accessModules']."/".$file);
				}
			}
		}

		foreach ($accessControl as $method => $function) {
			$this->accessMethods[$method] = $function;
		}
		$this->accessMethods['denyAll']  = 'dummyFunction';
		$this->accessMethods['allowAll'] = 'dummyFunction';

		if ($engineVars['accessExistsTest'] === TRUE || $engineVars['accessExistsTest'] === FALSE) {
			$this->accessExistsTest = $engineVars['accessExistsTest'];
		}

		// Define the AutoLoader
		// spl_autoload_register(array($this, 'autoloader'));
		$this->addAutoloader(array($this, 'autoloader'));

		// Get modules ready for Autoloader (previously loaded modules). Load the "onLoad.php" files
		// for each module
		$modules_dirHandle = @opendir($engineVars['modules']) or die("Unable to open ".$engineVars['modules']);
		while (false !== ($dir = readdir($modules_dirHandle))) {
			// Check to make sure that it isn't a hidden file and that the file is a directory
			if ($dir != "." && $dir != ".." && is_dir($engineVars['modules']."/".$dir) === TRUE) {
				$singleMod_dirHandle = @opendir($engineVars['modules']."/".$dir) or die("Unable to open ".$engineVars['modules']);
				while (false !== ($file = readdir($singleMod_dirHandle))) {
					if ($file != "." && $file != ".." && $file) {

						if ($file == "onLoad.php") {
							include_once($engineVars['modules']."/".$dir."/".$file);
						}
						else {
							$fileChunks = array_reverse(explode(".", $file));
							$ext= $fileChunks[0];
							if ($ext == "php") {
								$this->availableModules[$fileChunks[1]] = $engineVars['modules']."/".$dir."/".$file;
							}
						}

					}
				}
			}
		}

		// $testP = "/\{engine name=\"function\"\s+(.+?)\}/";
		// $testF = "eapi_function::templateMatches";
		// $this->defTempPattern($testP,$testF,$this);

		//Load Login Functions
		$login_dirHandle = @opendir($engineVars['loginModules']) or die("Unable to open ".$engineVars['loginModules']);
		while (false !== ($file = readdir($login_dirHandle))) {
			// Check to make sure that it isn't a hidden file and that it is a PHP file
			if ($file != "." && $file != ".." && $file) {
				$fileChunks = array_reverse(explode(".", $file));
				$ext= $fileChunks[0];
				if ($ext == "php") {
					include_once($engineVars['loginModules']."/".$file);
				}
			}
		}

		foreach ($loginFunctions as $type => $function) {
			$this->loginFunctions[$type] = $function;
		}

		//Start the Session
		sessionStart();

		// Sets up a clean PHP_SELF variable to use.
		$phpself             = basename($_SERVER['SCRIPT_FILENAME']);
		$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'],$phpself)).$phpself;

		// Sets up a clean clean HTTP_REFERER
		if (isset($_SERVER['HTTP_REFERER'])) {
			$_SERVER['HTTP_REFERER'] = htmlSanitize($_SERVER['HTTP_REFERER']);
		}

		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['QUERY_STRING'] = htmlSanitize($_SERVER['QUERY_STRING']);
		}

		// Startup engines database connection
		require_once(self::$engineDir."/modules/database/engineDB.php");
		$this->engineDB = new engineDB($this->engineVarsPrivate['mysql']['username'],$this->engineVarsPrivate['mysql']['password'],$this->engineVarsPrivate['mysql']['server'],$this->engineVarsPrivate['mysql']['port'],$engineVars['logDB'],FALSE);

		// Start up the logging
		if ($engineVars['log']) {
			$this->engineLog(); // access log
		}

		// Cross Site Request Forgery Check
		if(!empty($_POST)) {
			if(!isset($_POST["engineCSRFCheck"])) {
				error_log("CSRF Check Failed. Not Defined!");
				echo "CSRF Check Failed. Not Defined! ";
				exit;
			}
			if(!sessionCheckCSRF($_POST["engineCSRFCheck"])) {
				error_log("CSRF Check Failed. Possible Cross Site Request Forgery Attack!");
				echo "CSRF Check Failed. Possible Cross Site Request Forgery Attack!";
				exit;
			}

			$server = $this->getHTTP_REFERERServer($_SERVER['HTTP_REFERER']);

			if($server != $engineVars['server']) {
				error_log("HTTP Referer check failed. Possible Cross Site Request Forgery Attack!");
				echo "HTTP Referer check failed. Possible Cross Site Request Forgery Attack!<br />";
				echo "engineVars['server']: ".$engineVars['server']."<br />";
				echo "_SERVER: ".$_SERVER['HTTP_REFERER']."<br />";
				echo "server: ".$server."<br />";
				exit;
			}
		}

		// Get clean $_POST
		if(isset($_POST)) {
			foreach ($_POST as $key => $value) {
				$cleanKey                            = htmlSanitize($key);
				$this->cleanPost['HTML'][$cleanKey]  = htmlSanitize($value);
				$this->cleanPost['MYSQL'][$cleanKey] = dbSanitize($value);
				$this->cleanPost['RAW'][$cleanKey]   = $value;
			}
			unset($_POST);
		}

		// Get clean $_GET
		if(isset($_GET)) {
			foreach ($_GET as $key => $value) {
				$cleanKey                           = htmlSanitize($key);
				$this->cleanGet['HTML'][$cleanKey]  = htmlSanitize($value);
				$this->cleanGet['MYSQL'][$cleanKey] = dbSanitize($value);
				$this->cleanGet['RAW'][$cleanKey]   = $value;
			}
			unset($_GET);
		}

		// kill off $_REQUEST and force everything through cleanGet and cleanPost
		if (isset($_REQUEST)) {
			unset($_REQUEST);
		}

        // Last thing we need to do is load, and initialize the errorHandle class (the error handler)
        require_once(self::$engineDir."/errorHandle.php");
        errorHandle::singleton();
        ob_start('EngineAPI::displayTemplate');

	} // Constructor

	/**
	 * End of EngineAPI
	 */
	function __destruct() {
		ob_flush();
	}

    /**
	 * Get an instance of EngineAPI
	 *
     * @param string $site Name of the site config to use
     * @return self
     */
	public static function singleton($site="default") {
		if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($site);
        }

        return self::$instance;
	}

	/**
	 * Adds a library
	 *
	 * @param string $libraryDir
	 * @return bool
	 */
	public function addLibrary($libraryDir) {

		// Make sure that it is a directory
		if (is_dir($libraryDir) === FALSE) {
			return(FALSE);
		}

		// Make sure that we can read it
		if (is_readable($libraryDir) === FALSE) {
			return(FALSE);
		}

		$dirHandle = @opendir($libraryDir);

		if ($dirHandle === FALSE) {
			return(FALSE);
		}

		while (false !== ($file = readdir($dirHandle))) {

			$fileChunks = array_reverse(explode(".", $file));
			$ext        = $fileChunks[0];
			if ($ext == "php") {
				$this->availableModules[$fileChunks[1]] = $libraryDir."/".$file;
			}
		}
	}

	/**
	 * Retrieves a private var (if you're allowed)
	 *
	 * @param string $varName
	 * @return mixed|bool
	 */
	public function getPrivateVar($varName) {
		$file     = callingFile();
		$function = callingFunction();

		$engineDir = FALSE;
		if (strpos($file,EngineAPI::$engineDir) === 0) {
			$engineDir = TRUE;
		}

		if (isset($this->engineVarsPrivate['privateVars'][$varName])) {
			foreach ($this->engineVarsPrivate['privateVars'][$varName] as $I=>$V) {
				if (basename($file) == $V['file'] && $engineDir === TRUE && $V['function'] == $function) {
					return($this->$varName);
				}
			}
		}

		// Record this denial for debugging
		errorHandle::newError(__METHOD__."() - Access Denied to privateVar '$varName' for file '$file' and function '$function'!", errorHandle::DEBUG);

		return(FALSE);
	}

	/**
	 * [Alias] EngineAPI::setItems()
	 *
	 * @see self::defTempPatterns()
	 * @param string $pattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public function defTempPattern($pattern,$function,$object) {
		return self::defTempPatterns($pattern,$function,$object);
	}

	/**
	 * Define Template Object Pattern
	 *
	 * @param string $pattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public static function defTempPatterns($pattern,$function,$object) {
		$class            = get_class($object);
		$temp             = array();
		$temp['pattern']  = $pattern;
		$temp['function'] = $function;
		$temp['object']   = $object;
		self::$moduleTemplateEngine[$class][] = $temp;
		return(TRUE);
	}

	/**
	 * Redefine Template Object Pattern
	 *
	 * @see self::defTempPatterns()
	 * @param string $oldPattern
	 * @param string $newPattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public function reDefTempPattern($oldPattern,$newPattern,$function,$object) {
		foreach (self::$moduleTemplateEngine as $class=>$V) {
			foreach (self::$moduleTemplateEngine[$class] as $I => $plugin) {
				if ($plugin['pattern'] == $oldPattern) {
					unset(self::$moduleTemplateEngine[$class][$I]);
					break;
				}
			}
		}
		$this->defTempPattern($newPattern,$function,$object);
		return(TRUE);
	}

	/**
	 * Um...
	 * This function should be combined with defTempPatterns
	 * if $pattern, $function, $class already exist the object should be updated
	 *
	 * @param string $pattern
	 * @param string $function
	 * @param string $object
	 * @return bool Always TRUE
	 */
	public function reDefTempPatternObject($pattern,$function,$object) {
		$class = get_class($object);
		foreach (self::$moduleTemplateEngine[$class] as $I => $plugin) {
			if ($plugin['pattern'] == $pattern) {
				self::$moduleTemplateEngine[$class][$I]['object'] = $object;
			}
		}
		return(TRUE);
	}

	/**
	 * Retrieve Template Object
	 *
	 * @param string $className
	 * @return mixed
	 */
	public function retTempObj($className) {
		return(self::$moduleTemplateEngine[$className][0]['object']);
	}

	/**
	 * Set function extensions
	 *
	 * @todo What does $stage control?
	 * @param string|array $function
	 * @param string|array $newFunction
	 * @param string $stage UNKNOWN
	 * @return bool
	 */
	public function setFunctionExtension($function,$newFunction,$stage="after") {

		$class       = (is_array($function))?$function[0]:NULL;
		$function    = (is_array($function))?$function[1]:$function;
		$newClass    = (is_array($newFunction))?$newFunction[0]:NULL;
		$newFunction = (is_array($newFunction))?$newFunction[1]:$newFunction;

		// check if the function/method exists

		if (isnull($newClass) && functionExists($newFunction) === FALSE) {
			return(FALSE);
		}
		else if (isnull($newClass) && $newFunction == "recurseInsert") {
			// can't define the system recurseInsert as the function.
			return(FALSE);
		}
		else if (!isnull($newClass) && functionExists($newClass,$newFunction) === FALSE) {
			return(FALSE);
		}

		$functionIndex = $function.((isnull($class))?"":"::".$class);

		$temp             = array();
		$temp['class']    = $newClass;
		$temp['function'] = $newFunction;

		if (!isset($this->functionExtensions[$functionIndex][$stage])) {
			$this->functionExtensions[$functionIndex][$stage] = array();
		}

		$this->functionExtensions[$functionIndex][$stage][] = $temp;

		return(TRUE);
	}

	/**
	 * Get function extensions
	 *
	 * @param string $function
	 * @param string|null $class
	 * @return array|bool
	 */
	public function getFunctionExtension($function,$class=NULL) {
		$functionIndex = $function.((isnull($class))?"":"::".$class);
		if (array_key_exists($functionIndex,$this->functionExtensions)) {
			return($this->functionExtensions[$functionIndex]);
		}
		return(FALSE);
	}

	/**
	 * Execute something...
	 * @param string $function
	 * @param string $params
	 * @param string $stage UNKNOWN (before, after)
	 * @return bool
	 */
	public function execFunctionExtension($function,$params,$stage="after") {

		if (!is_array($params)) {
			return(FALSE);
		}

		$class     = (is_array($function))?$function[0]:NULL;
		$function  = (is_array($function))?$function[1]:$function;


		$functions = $this->getFunctionExtension($function,$class);

		if (!is_array($functions) || count($functions) < 1) {
			return(FALSE);
		}

		if (!array_key_exists($stage, $functions)) {
			return(FALSE);
		}

		$output = FALSE;



		// return(FALSE);
		foreach($functions[$stage] as $I=>$function) {
			if (array_key_exists('class',$function) && !isnull($function['class'])) {
				$obj    = new $function['class'];
				$output = $obj->$function['function']($params);
			}
			else {
				$output = $function['function']($params);
			}
			if ($output !== FALSE) {
				break;
			}
		}

		return($output);

	}

	/**
	 * eTemplate() - Not sure what this does
	 *
	 * @param $func
	 * 		  load - define which tempalte to use.
	 * 		  name - UNKNOWN
	 * 		  include - fire off the 'header' or the 'footer'
	 * @param null $value
	 * @return bool|string
	 */
	public function eTemplate($func,$value=NULL) {

		global $engineVars;

		if ($func == "load") {

			if(isnull($value)) {
				return(FALSE);
			}

			if (file_exists($engineVars['tempDir']."/".$value)) {
				$this->template = $engineVars['tempDir']."/".$value;
				$engineVars['currentTemplate'] = $this->template;
			}
			else {
				return(FALSE);
			}
		}
		if ($func == "name") {
			return(basename($this->template));
		}
		if ($func == "include") {
			switch($value) {
				case "header":
					include($this->template."/templateHeader.php");
					break;

				case "footer":
				    include($this->template."/templateFooter.php");
					break;

				default:
				    return(FALSE);
				    break;
			}
		}
		return(TRUE);
	}

	/**
	 * Returns the current template directory
	 *
	 * @return string
	 */
	public function currentTemplate() {
		return($this->template);
	}

	/**
	 * Register ACL rules
	 * hardbreak causes the function to exit immediately on a FALSE ACL return if set to TRUE
	 *
	 * @param $action
	 *        debugListAll - Prints debug info
	 *        existsTest - UNKNOWN
	 *        build - UNKNOWN
	 *        clear - Clears all acl rules
	 * @param string|null $value
	 * @param string|bool $state
	 * @param bool $hardBreak
	 * @return bool
	 */
	public function accessControl($action,$value=NULL,$state=FALSE,$hardBreak=TRUE) {

		if ($action == "debugListAll") {
			print "<pre>";
			var_dump($this->acl);
			print "</pre>";
			return(TRUE);
		}

		if ($action == "existsTest") {
			if ($value === TRUE || $value === FALSE) {
				$this->accessExistsTest = $value;
				return(TRUE);
			}
			return(FALSE);
		}

		if ($action == "build") {

			$auth  = NULL;
			$count = 0;

			foreach ($this->acl as $key => $value) {

				$action = $value['action'];

				if ($action == "denyAll") {
					// If this is the first item in the array, access is denied.
					// if it is NOT the first item, we assume it is the last intended to be
					// evaluated as a 'catch all'
					if ($count === 0) {
						$this->accessControlDenied();
						exit;
					}
					else {
						break;
					}
				}

				$count++;

				if ($action == "allowAll") {
					return(TRUE);
				}


				$returnValue = $this->accessMethods[$action]($value['value'],$value['state']);

				// NULL value is error state. set auth to false to be safe
				if (isnull($returnValue)) {
					if ($value['hardBreak'] === TRUE) {
						$this->aclgroups[$action] = FALSE;
						$this->accessControlDenied();
						exit;
					}
					$this->aclgroups[$action] = FALSE;
					continue;
				}
				else if ($returnValue === FALSE) {
					if ($value['hardBreak'] === TRUE) {
						$this->aclgroups[$action] = FALSE;
						$this->accessControlDenied();
						exit;
					}
					if ($this->aclgroups[$action] === TRUE) {
						continue;
					}
					$this->aclgroups[$action] = FALSE;
				}
				else if ($returnValue === TRUE) {
					$this->aclgroups[$action] = TRUE;
				}
			}

			// foreach group ("action") check if it is true. If all actions are true, YAY!
			// Otherwise Ugh!
			$auth = NULL;
			foreach ($this->aclgroups as $key => $value) {

				// At this point, the only "FALSE" things should be those that did not have a hard break
				// so we should NOT exit if we see them, unless ALL things fail.

				if ($value === FALSE) {
					if (isnull($auth)) {
						$auth = FALSE;
					}
					// $this->accessControlDenied();
					// exit;
				}
				else if ($value === TRUE) {
					$auth = TRUE;
				}
				else {
					// Safety check in case of errors
					$auth = NULL;
				}
			}

			if ($auth === TRUE) {
				return(TRUE);
			}


			$this->accessControlDenied();
			exit;

			return($auth);
		}

		if ($action == "clear") {
			unset($this->acl);
			$this->acl = array();
			$aclCount  = 0;
			return(TRUE);
		}

		if(!isset($this->accessMethods[$action])) {
			if ($this->accessExistsTest === TRUE) {
				die("Access Control $action is undefined. Exiting.\n");
			}
			return(FALSE);
		}

		$this->acl[$this->aclCount]['action']     = $action;
		$this->acl[$this->aclCount]['value']      = $value;
		$this->acl[$this->aclCount]['state']      = $state;
		$this->acl[$this->aclCount]['hardBreak']  = $hardBreak;


		if ($action != "denyAll") {
			$this->aclgroups[$action] = FALSE;
		}

		$this->aclCount++;

		return(TRUE);
	}

	/**
	 * Connect to MySQL Database
	 *
	 * @param $action
	 *        username - Sets the username
	 *        password - Sets the password
	 *        port - Sets the port
	 *        database - Sets the database name
	 *
	 * @param string $value
	 * @param bool $state
	 * @return bool|engineDB
	 */
	public function dbConnect($action,$value,$state=FALSE) {
		if (!isset($value)) {
			return(FALSE);
		}

		if ($action == "username") {
			$this->dbUsername = $value;
		}
		else if ($action == "password") {
			$this->dbPassword = $value;
		}
		else if ($action == "port") {
			$this->dbPort     = $value;
		}
		else if ($action == "server") {
			$this->dbServer   = $value;
		}
		else if ($action == "database") {

			$this->dbDatabase = $value;

			// Open up the database connections
			if (isset($this->dbUsername) && isset($this->dbPassword) && isset($this->dbPort) && isset($this->dbServer) && isset($this->dbDatabase)) {

				if (!isnull($this->openDB)) {
					// die;
				}

				$dbObject = new engineDB($this->dbUsername,$this->dbPassword,$this->dbServer,$this->dbPort,$this->dbDatabase);

				if ($state === FALSE) {
					return($dbObject);
				}
				else {
					$this->openDB = $dbObject;
					return(TRUE);
				}

			}
			else {
				$this->openDB = null;
				return(FALSE);
			}

		}

		return(TRUE);

	}

	/**
	 * dbTables()
	 * @param $action
	 * @param string $state
	 * @param null $value
	 * @return bool
	 */
	public function dbTables($action,$state="prod",$value=NULL) {

		if(!isnull($value)) {
			$this->dbTables[$action][$state] = $value;
			return(TRUE);
		}
		else {
			if (isset($this->dbTables[$action][$state])) {
				return($this->dbTables[$action][$state]);
			}
		}

		return(FALSE);
	}

	/**
	 * Returns the original dbTables array from the Old EngineCMS. This is provided to ease transition into the new system
	 * @deprecated
	 * @return array
	 */
	public function dbTablesExport() {
		$dbTablesArray = array();

		$dbTablesArray                             = $this->dbTables;
		$dbTablesArray["engineDBInfo"]["database"] = $this->dbDatabase;

		return($dbTablesArray);

	}

	/**
	 * Process a login (Is this deprecated?)
	 *
	 * @deprecated
	 * @param $loginType
	 * @return bool
	 */
	public function login($loginType) {
		if (isset($this->loginFunctions[$loginType])) {
			if($this->loginFunctions[$loginType](trim($this->cleanPost['RAW']['username']),$this->cleanPost['RAW']['password'])) {
				return(TRUE);
			}
		}
		return(FALSE);
	}

	/**
	 * Adds the given autoload function to the autoload stack
	 * @internal
	 * @param mixed $autoload
	 * @return bool
	 */
	function addAutoloader($autoload) {

		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			$return = spl_autoload_register($autoload,TRUE,TRUE);
			return($return);
		}

		$functions = spl_autoload_functions();

		if ($functions === FALSE) {
			$return = spl_autoload_register($autoload);
			return($return);
		}

		foreach ($functions as $I=>$V) {
			$return = spl_autoload_unregister($V);
			if ($return === FALSE) {
				return(FALSE);
			}
		}

		$return = spl_autoload_register($autoload);
		if ($return === FALSE) {
			return(FALSE);
		}
		foreach ($functions as $I=>$V) {
			$return = spl_autoload_register($V);
			if ($return === FALSE) {
				return(FALSE);
			}
		}

		return($return);

	}

	/**
	 * Base EngineAPI autoloader
	 * @param $className
	 * @return bool
	 */
	public static function autoloader($className) {
		$engine = EngineAPI::singleton();
		if (!class_exists($className, FALSE)) {
			if (isset($engine->availableModules[$className]) && file_exists($engine->availableModules[$className])) {
				require_once($engine->availableModules[$className]);
				return(TRUE);
			}

			// Can't throw exceptions in php 5.2 from an autoloader, but you can
			// catch it from this eval block.

			if (preg_match('/^[^a-zA-Z_\x7f-\xff]/',$className)) {
				eval("throw new Exception('Class $className not found', 1001);");
				return(FALSE);
			}

			eval("
				class $className {
					function __construct() {
						throw new Exception('Class $className not found', 1001);
					}
					static function __callstatic(\$m, \$args) {
						throw new Exception('Class $className not found', 1001);
					}
					function x_notaclass_x(){}
				}
				");

			return(FALSE);
		}

		return;
	}

	/**
	 * Um... huh?
	 * @param $referer
	 * @return null
	 */
	private function getHTTP_REFERERServer($referer) {

		$server = NULL;

		if(preg_match('@^(?:https?://)?([^/]+)@i',$referer,$matches)) {
			$server = $matches[1];
		}

		return($server);
	}

	/**
	 * Record a log message to the log table
	 * @param string $type
	 * @param null $function
	 * @param null $message
	 * @return bool
	 */
	private function engineLog($type="access",$function=NULL,$message=NULL) {

		global $engineVars;
		$engineDB = $this->engineDB;

		if (!$engineVars['log'] || $engineDB->status === FALSE) {
			return(FALSE);
		}

		// setup the variables
		$date      = time();
		$ip        = isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:NULL;
		$referrer  = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:NULL;
		$resource  = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:NULL;
		$queryStr  = isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:NULL;
		$useragent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:NULL;
		$site      = isset($engineVars['server'])?$engineVars['server']:NULL;

		$query = sprintf(
			"INSERT INTO log (date,ip,referrer,resource,useragent,function,type,message,querystring,site) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			$engineDB->escape($date),
			$engineDB->escape($ip),
			$engineDB->escape($referrer),
			$engineDB->escape($resource),
			$engineDB->escape($useragent),
			$engineDB->escape($function),
			$engineDB->escape($type),
			$engineDB->escape($message),
			$engineDB->escape($queryStr),
			$engineDB->escape($site)
			);

		$engineDB->sanitize = FALSE;
		$results = $engineDB->query($query);

		return(TRUE);
	}

	/**
	 * accessControlDenied()
	 */
	private function accessControlDenied() {
		global $engineVars;

		$engineVars['loginPage'] = EngineAPI::$engineVars['loginPage'];

		ob_end_clean();
		header( 'Location: '.$engineVars['loginPage'].'?page='.$_SERVER['PHP_SELF']."&qs=".(urlencode($_SERVER['QUERY_STRING'])) ) ;
		//die("No Access Here");
		//return(FALSE);
	}

	/**
	 * Displays the template out to the world
	 *
	 * @todo This function needs some performance work.
	 * @param $content
	 * @return string
	 */
	public static function displayTemplate($content) {
		global $engineVars;

		$engine = EngineAPI::singleton();

		if ($engine->obCallback === FALSE) {
			return($content);
		}

		// Todo this should be configurable:
		if (strlen($content) > 1000000) {
			trigger_error(__METHOD__.'() - Cannot parse output (too large)', E_USER_WARNING);
			return($content);
		}

		$contentArray = preg_split('/<!-- engine Instruction break -->/',$content);
		$content = "";

		foreach ($contentArray as $line) {

			if (preg_match("/<!-- engine Instruction (\w+) -->/",$line,$matches)) {
				switch($matches[1]) {
					case "displayTemplateOff":
					$engine->displayTemplateOff = TRUE;
					break;
					case "displayTemplateOn":
					$engine->displayTemplateOff = FALSE;
					break;
				}
			}

			if ($engine->displayTemplateOff === FALSE) {
				//local var replacements
				// $line = preg_replace_callback("/\{local\s+?var=\"(.+?)\"\}/",'EngineAPI::localMatches',$line);

				//engineVar replacements
				$line = preg_replace_callback("/\{engine\s+?var=\"(.+?)\"\}/",'EngineAPI::engineVarMatches',$line);

				//engine Replacements
				$line = preg_replace_callback("/\{engine\s+(.+?)\}/",'EngineAPI::engineMatches',$line);

				// this replaces the occurances of "" with something else. This handles a bug in some browsers (IE)
				if ($engineVars['replaceDoubleQuotes'] === TRUE) {
					$line = preg_replace_callback('/(<[^"]+=)("{2})([^>]+>)/','EngineAPI::engineDQMatches',$line);
				}

				// Check to see if the pattern matches the "standard" for
				// module templates. If so, see if the module is loaded.
				// If no, try to load the module and create a temporary
				// instance of it to get the replacement pattern and function
				preg_match_all("/\{(.+?)(\s(.+?))?\}/",$line,$matches);
				if (isset($matches[1]) && !is_empty($matches[1])) {
					foreach ($matches[1] as $I=>$className) {
						if (!class_exists($className, FALSE)) {
							$className = preg_replace("/[^a-zA-Z0-9_]/", "", $className);
							try {
								if (array_key_exists($className,$engine->availableModules)) {
									$temp = new $className();
								}
							}
							catch (Exception $e) {
								// do nothing
							}
						}
					}
				}

				//module Replacements
				// foreach ($engine->moduleTemplateEngine as $class) {
				foreach (self::$moduleTemplateEngine as $class) {
					foreach ($class as $plugin) {
						if(isset($plugin['pattern']) && isset($plugin['function'])) {
							// testing
							$engine->cwd = "hate this";//$plugin['pattern'];
							$engine->recurseNeeded = TRUE;
							$line = preg_replace_callback($plugin['pattern'],$plugin['function'],$line);
						}
					}
				}
				//add a line break, \n, after <br /> ... makes the source a touch prettier.
				$line = str_replace("<br />","<br />\n",$line);
			}

			$content .= $line;
		}

		if ($engine->recurseNeeded === TRUE) {
			if ($engine->recurseCount < $engine->recurseLevel) {
				$engine->recurseCount++;
				$content = $engine->displayTemplate($content);
			}
			// There has GOT to be a better way to do this
			$backtrace = debug_backtrace();
			if ($backtrace[0]['function'] != "displayTemplate") {
				$engine->recurseCount = 0;
			}
		}



		return($content);
	}

	/**
	 * engineVarMatches() - Not sure
	 * @todo Things to implement: Email obsfucation, All Uppercase, All Lowercase, Title Case (cap first letter of each word)
	 * @todo fix readability formatting (Mike)
	 * @param $matches
	 * @return string
	 */
	public static function engineVarMatches($matches) {
		global $engineVars;
		$output = (!empty($engineVars[$matches[1]]))?$engineVars[$matches[1]]:"";
		return($output);
	}

	/**
	 * Not a clue
	 * @param $matches
	 * @return string
	 */
	public static function engineDQMatches($matches) {
		global $engineVars;

		$output = $matches[1].'"'.$engineVars['replaceDQCharacter'].'"'.$matches[3];
		return($output);
	}

	/**
	 * Something to do with engine matches
	 * @param $matches
	 * @return bool|string
	 */
	public static function engineMatches($matches) {
		$engine    = EngineAPI::singleton();
		$attPairs  = explode("\" ",$matches[1]);

		foreach ($attPairs as $pair) {
			if (empty($pair)) {
				continue;
			}
			list($attribute,$value) = explode("=",$pair);
			$temp[$attribute] = str_replace("\"","",$value);
		}

		if (isset($temp['name'])) {
			$output = $engine->handleMatch($temp);
			if ($output === FALSE) {
				$output = $matches[0];
			}
			return($output);
		}
		else {
			return("name attribute missing");
		}

		return("Error");

	}

	/**
	 * Internal handler for engine matches
	 * @internal
	 * @see self::engineMatches()
	 * @param $attPairs
	 * @return bool
	 */
	private function handleMatch($attPairs) {

		global $engineVars;

		$output = "Error: handleMatch in template.php";
		switch($attPairs['name']) {
			case "filelist":
				// This should be moved out to a module
			    $output = filelist($attPairs['dir'],$attPairs['temp']);
				break;
			case "filetemplate":
				// This should be moved out to a module
			    global $engineVars;
			    $output = $engineVars['fileList']->getAttribute($attPairs);
				break;
			case "email":
				// This should be moved out to a module
			    $output = $engineVars['emailSender'][$attPairs['type']];
				break;
			// case "include":
			//     $output = recurseInsert($attPairs['file'],$attPairs['type']);
			// 	break;
			// case "session":
			//     $output = sessionGet($attPairs['var']);
			// 	break;
			// case "insertCSRF":
			// case "csrf":
			//     $output = sessionInsertCSRF();
			// 	break;
			// case "csrfGet":
			//     $output = sessionInsertCSRF(FALSE);
			//     break;
			// case "function":
			// 	errorHandle::newError("{engine name=\"function\"} replacement is deprecated", errorHandle::DEBUG);
			//     $output = $attPairs['function']($attPairs);
			// 	break;
			default:
			    $output = FALSE;
		}
		return($output);
	}

	//** Deprecated functions to be removed in 4.0 **/

	/**
	 * Define local variables.
	 * if second value is not provided, tries to return that value. False if it doesn't exist
	 *
	 * @deprecated 4.0
	 * @param $variable
	 * @param null $value
	 * @param bool $null
	 * @return bool|null|string
	 */
	public function localVars($variable,$value=NULL,$null=FALSE) {
		return localvars::variable($variable,$value,$null);
	}


	/**
	 * Returns an array identical to the original LocalVars arrays
	 * This function is only for migration purposes and should be removed in 4.0
	 *
	 * @deprecated 4.0
	 * @return array
	 */
	public function localVarsExport() {
		return localvars::export();
	}
}
?>