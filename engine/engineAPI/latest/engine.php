<?php
/**
 * EngineAPI
 *
 * EngineAPI core object. This is the root of the entire EngineAPI framework
 * Required PHP Version: 5.3
 *
 * @author  WVU Library Systems
 * @version 4.0
 * @license http://systems.lib.wvu.edu/engineapi/license.php WVU Open Source License
 * @link    http://systems.lib.wvu.edu/engineapi/
 * @package EngineAPI
 */
$engineVars        = array();
$engineVarsPrivate = array();

$accessControl   = array();
$moduleFunctions = array();
$engineDB        = NULL;
$DEBUG           = NULL;

/**
 * EngineAPI Class
 *
 * This is the heart of the EngineAPI system. This is a singleton class where only 1 instance is ever allowed
 * @package EngineAPI
 */
class EngineAPI{
	const VERSION='4.0';

	/**
	 * The EngineAPI instance
	 * @var self
	 */
	private static $instance; // Hold an instance of this object, for use as Singleton

	/**
	 * The root directory for EngineAPI and its modules
	 * @var string
	 */
	public static $engineDir;

	/**
	 * The engine config items
	 * @var array
	 */
	public static $engineVars = array();
	private $enginevars;

	/**
	 * Private engine config vars
	 * @var array
	 */
	private $engineVarsPrivate = array();
	private $privatevars;

	/**
	 * The error stack
	 * @var array
	 */
	public $errorStack = array();

	/**
	 * If set to false, the engine tags will not be processed.
	 * @var bool
	 */
	public $obCallback = TRUE; //

	/**
	 * Current working directory
	 * @var string
	 */
	public $cwd = "";

	/**
	 * Unknown - Remove?
	 * @todo appears unused - Remove?
	 * @var array
	 */
	private $DEBUG = array();

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
	
	/**
	 * Recursive counter for template renderer
	 * In module template matches, prevent infinite recursion
	 * @var int
	 */
	private $recurseCount = 0;

	/**
	 * Recursive level for template renderer
	 * @var int
	 */
	private $recurseLevel = 3;

	/**
	 * Flag to trigger recursion
	 * @var bool
	 */
	private $recurseNeeded = FALSE;

	/**
	 * Flag to turn off templates
	 * @var bool
	 */
	private $displayTemplateOff     = FALSE;

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

		require_once self::$engineDir."/loader.php";

		// This needs to be explicitly loaded so that onLoad.php's that call
		// template information can be loaded correctly
		// 
		// @TODO -- we need a to handle priorities / dependencies. onload.php
		// should wait until dependencies are filled before firing off. That would
		// remove the requirement that the templates module be loaded manually. 
		require_once self::$engineDir."/modules/templates/templates.php";

		//Load helper function Modules
		loader(self::$engineDir."/helperFunctions");

		// Define the AutoLoader
		$autoloader = autoloader::getInstance(self::$engineDir."/modules");
		$autoloader->addAutoloader(array($autoloader,'autoloader'));
		$autoloader->loadModules();

		// $configObject = config::getInstance(self::$engineDir,$site);
		$this->privatevars = privatevars::getInstance(self::$engineDir,$site);
		$this->enginevars  = enginevars::getInstance(self::$engineDir,$site);

		/**
		 * @deprecated added to ease transition
		 */
		 $enginevars = $this->enginevars;

		// make sure the session cookie is only accessible via HTTP
		ini_set("session.cookie_httponly", 1);

		// Setup Current Working Directory
		$this->cwd = getcwd();

		// Setup default database connections
		$this->dbUsername = $this->privatevars->get(array('mysql','username'),NULL);
		$this->dbPassword = $this->privatevars->get(array('mysql','password'),NULL);
		$this->dbPort     = $this->privatevars->get(array('mysql','port'),NULL);
		$this->dbServer   = $this->privatevars->get(array('mysql','server'),NULL);

		// Startup engines database connection
		require_once self::$engineDir."/modules/database/engineDB.php";
		$this->engineDB = new engineDB($this->privatevars->get(array('mysql','username')),$this->privatevars->get(array('mysql','password')),$this->privatevars->get(array('mysql','server')),$this->privatevars->get(array('mysql','port')),$enginevars->get('logDB'),FALSE);

		// Start up the logging
		$logger = logger::getInstance($this->engineDB);
		$logger->log();
		
		// Access Control and login inits can't be off loaded to onLoad.php like they should be
		// because engine and private vars needs to be created with engineAPI
		// constructor variables first. (enginedir and site)

		//Load Access Control Modules
		accessControl::init();

		//Load Login Functions
		login::init();

		// Clean variables
		http::cleanPost();                 // $_POST
		http::cleanGet();                  // $_GET
		http::removeRequest();             // kill off $_REQUEST
		phpself::clean();                  // $_SERVER['PHP_SELF']
		server::cleanHTTPReferer();        // $_SERVER['HTTP_REFERER']
		server::cleanQueryStringReferer(); // $_SERVER['QUERY_STRING']

		// Initialize the session and if we are not in CLI mode start the session

		session::singlton(NULL,$this);
		if(!isCLI() and !session::started()) session::start();

		// Cross Site Request Forgery Check
		http::csrfCheck();

        // Last thing we need to do is load, and initialize the errorHandle class (the error handler)
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
	 * Retrieves a private var (if you're allowed)
	 *
	 * @param string $varName
	 * @return mixed|bool
	 */
	// @TODO This appears to only be used to retrieve the engineDB object
	// it is not being used to retrieve private vars (from config), its being used
	// to retrieve private variables from engine
	public function getPrivateVar($varName) {
		$file     = callingFile();
		$function = callingFunction();

		$engineDir = FALSE;
		if (strpos($file,EngineAPI::$engineDir) === 0) {
			$engineDir = TRUE;
		}

		$privateVariable = privatevars::get(array('privateVars',$varName));

		if (!isempty($privateVariable)) {
			foreach ($privateVariable as $I=>$V) {
				if (basename($file) == $V['file'] && $engineDir === TRUE && $V['function'] == $function) {
					return $this->$varName;
				}
			}
		}

		// Record this denial for debugging
		errorHandle::newError(__METHOD__."() - Access Denied to privateVar '$varName' for file '$file' and function '$function'!", errorHandle::DEBUG);

		return FALSE;
	}

	/**
	 * Returns the internal engineDB object
	 * Restricted to EngineAPI and its modules
	 *
	 * @return engineDB|bool
	 */
	public function getEngineDB(){
		$callingFile = realpath(callingFile());
		$engineDir   = realpath(self::$engineDir);
		if(0 === strpos($callingFile, $engineDir)){
			// Valid
			return $this->engineDB;
		}else{
			// Invalid
			$callingFile = callingFunction();
			errorHandle::newError(__METHOD__."() - Access denied to getEngineDB '".callingFunction()."()' in $callingFile", errorHandle::DEBUG);
			return FALSE;
		}
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
			return FALSE;
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
					return $dbObject;
				}
				else {
					$this->openDB = $dbObject;
					return TRUE;
				}

			}
			else {
				$this->openDB = null;
				return FALSE;
			}

		}

		return TRUE;

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
			return TRUE;
		}
		else {
			if (isset($this->dbTables[$action][$state])) {
				return $this->dbTables[$action][$state];
			}
		}

		return FALSE;
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

		return $dbTablesArray;

	}

	/**
	 * determines the server from $referer
	 * @param $referer
	 * @return string the server passed in via referer 
	 */
	private function getHTTP_REFERERServer($referer) {

		$server = NULL;

		if(preg_match('@^(?:https?://)?([^/]+)@i',$referer,$matches)) {
			$server = $matches[1];
		}

		return $server;
	}

	/**
	 * Displays the template out to the world
	 *
	 * @todo This function needs some performance work.
	 * @param $content
	 * @return string
	 */
	public static function displayTemplate($content) {
		
		$engineVars = enginevars::getInstance()->export();

		$engine     = EngineAPI::singleton();

		if ($engine->obCallback === FALSE) {
			return $content;
		}

		// Todo this should be configurable:
		if (strlen($content) > 1000000) {
			trigger_error(__METHOD__.'() - Cannot parse output (too large)', E_USER_WARNING);
			return $content;
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
						// This if check prevents modules that have been loaded but the 
						// constructor hasn't been called from using tag replacements.
						// Commented out until we figure out if its bad to have it commented out ... 
						// The modules could get around this by using an onLoad.php, but i'd rather see it 
						// all autoloaded. 
						
						// if (!class_exists($className, FALSE)) {
							$className = preg_replace("/[^a-zA-Z0-9_]/", "", $className);
							try {
								if (autoloader::getInstance()->exists($className)) {
									$temp = new $className();
								}
							}
							catch (Exception $e) {
								// do nothing
							}
						// }
					}
				}

				//module Replacements
				// foreach ($engine->moduleTemplateEngine as $class) {
				// self::$moduleTemplateEngine
				foreach (templates::getTemplatePatterns() as $class) {
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



		return $content;
	}

	/**
	 * engineVarMatches() - Not sure
	 * @todo Things to implement: Email obsfucation, All Uppercase, All Lowercase, Title Case (cap first letter of each word)
	 * @todo fix readability formatting (Mike)
	 * @param $matches
	 * @return string
	 */
	public static function engineVarMatches($matches) {
		$engineVars = enginevars::getInstance()->export();
		$output = (!empty($engineVars[$matches[1]]))?$engineVars[$matches[1]]:"";
		return $output;
	}

	/**
	 * If $engineVars['replaceDoubleQuotes'] = TRUE, this method will replace double 
	 * quote strings (two quotes, without any characters in between, example: "" ) with : 
	 * "$engineVars['replaceDQCharacter']"
	 *
	 * This prevents a bug in some browsers with "" in the header causes issues. 
	 * 
	 * @param $matches
	 * @return string
	 */
	public static function engineDQMatches($matches) {
		$engineVars = enginevars::getInstance()->export();

		$output = $matches[1].'"'.$engineVars['replaceDQCharacter'].'"'.$matches[3];
		return $output;
	}

	/**
	 * callback to handle {engine .*} matches in templates
	 * 
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
			return $output;
		}
		else {
			return "name attribute missing";
		}

		return "Error";

	}

	/**
	 * Internal handler for engine matches
	 * @internal
	 * @see self::engineMatches()
	 * @param $attPairs
	 * @return bool
	 */
	private function handleMatch($attPairs) {

		$engineVars = enginevars::getInstance()->export();

		$output = "Error: handleMatch in template.php";
		switch($attPairs['name']) {
			case "filelist":
				// This should be moved out to a module
			    $output = filelist($attPairs['dir'],$attPairs['temp']);
				break;
			case "filetemplate":
				// This should be moved out to a module
			    $output = $engineVars['fileList']->getAttribute($attPairs);
				break;
			case "email":
				// This should be moved out to a module
			    $output = $engineVars['emailSender'][$attPairs['type']];
				break;

			default:
			    $output = FALSE;
		}
		return $output;
	}

}
?>