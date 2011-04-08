<?php

global $engineDir;

$engineVars = array();

class EngineAPI {
	
	private static $instance; // Hold an instance of this object, for use as Singleton
	
	private $localVars        = array();
	public  $template         = ""; // $engineVars['currentTemplate'];
	
	public  $cwd              = "";
	
	private $DEBUG            = array();
	
	public $errorStack        = array();
	
	// Cleaned up $_GET and _$POST variables with HTML and MySQL sanitized values
	public $cleanGet  = array();
	public $cleanPost = array();
	
	// Used for page access/security
	private $accessMethods    = "";
	private $accessExistsTest = TRUE;
	private $acl              = array();
	private $aclgroups        = array();
	private $aclCount         = 0;
	
	// Used for database connections
	private $dbUsername = "";
	private $dbPassword = "";
	private $dbDatabase = "";
	private $dbServer   = "";
	private $dbPort     = "";
	private $dbTables   = array();
	public  $openDB   = NULL;
	
	// Module Stuffs
	private $availableModules       = array();
	
	//Module Template Mathes and function calls for displayTemplate()
	private $moduleTemplateEngine   = array();
	private $recurseCount           = 0; // In module template matches, prevent infinite recursion
	private $recurseLevel           = 3;
	private $recurseNeeded          = FALSE;
	private $displayTemplateOff     = FALSE;
	
	
	private function __construct($site="default") {
		global $engineDir;
		
		ob_start(array(&$this, 'displayTemplate'));
		
		//setup $engineVars;
		global $engineVars;
		require_once($engineDir."/config/default.php");
		if ($site != "default") {
			require_once($engineDir."/config/".$site.".php");
		}
		
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
		
		require_once($engineDir."/sessionManagement.php");
		require_once($engineDir."/debug.php");
		require_once($engineDir."/stats.php");
		require_once($engineDir."/userInfo.php");
		
		// Setup Current Working Directory
		$this->cwd = getcwd();
		
		// Setup initial Template
		$this->template = $engineVars['tempDir']."/".$engineVars['templateDefault'];
		$engineVars['currentTemplate'] = $this->template;
		
		// Setup default database connections
		$this->dbUsername = ($engineVars['mysql']['username'])?$engineVars['mysql']['username']:NULL;
		$this->dbPassword = ($engineVars['mysql']['password'])?$engineVars['mysql']['password']:NULL;
		$this->dbPort     = ($engineVars['mysql']['port'])?$engineVars['mysql']['port']:NULL;
		$this->dbServer   = ($engineVars['mysql']['server'])?$engineVars['mysql']['server']:NULL;
		
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
		spl_autoload_register(array($this, 'autoloader'));
		
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
		$phpself             = array_pop(explode("/",$_SERVER['SCRIPT_FILENAME']));
		$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'],$phpself)).$phpself;
		
		// Sets up a clean clean HTTP_REFERER
		if (isset($_SERVER['HTTP_REFERER'])) {
			$_SERVER['HTTP_REFERER'] = htmlSanitize($_SERVER['HTTP_REFERER']);
		}
		
		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['QUERY_STRING'] = htmlSanitize($_SERVER['QUERY_STRING']);
		}
		
		// Startup engines database connection
		global $engineDB;
		$engineDB = new engineDB($engineVars['mysql']['username'],$engineVars['mysql']['password'],$engineVars['mysql']['server'],$engineVars['mysql']['port'],$engineVars['logDB'],FALSE);
		
		// Start up the logging
		if ($engineVars['log']) {
			$this->engineLog(); // access log
		}
		
		// Cross Site Request Forgery Check
		if(!empty($_POST)) {
			if(!isset($_POST["engineCSRFCheck"])) {
				echo "CSRF Check Failed. Not Defined! ";
				exit;
			}
			if(!sessionCheckCSRF($_POST["engineCSRFCheck"])) {
				echo "CSRF Check Failed. Possible Cross Site Request Forgery Attack!";
				exit;
			}	
			
			$server = $this->getHTTP_REFERERServer($_SERVER['HTTP_REFERER']);
			
			if($server != $engineVars['server']) {
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
		

		
		// Setup debugging
		debugBuild($this);
		
		if (debugNeeded("includes")) {
			debugDisplay("includes","\$cleanPost",1,"Contents of the \$cleanPost array.",$this->cleanPost);
		}

		if (debugNeeded("includes")) {
			debugDisplay("includes","\$cleanGet",1,"Contents of the \$cleanGet array.",$this->cleanGet);
		}
		
	}
	
	function __destruct() {
		
//		$output = "Destruct Dump:<br /><pre>";
//		$output .= var_export($this,true);
//		$output .= "</pre>";
		//print $output;
		ob_flush();
	}
	
	public static function singleton($site="default") {
		if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($site);
        }

        return self::$instance;
	}
	
	
	// Define Template Object Pattern
	public function defTempPattern($pattern,$function,$object) {
		
		$class = get_class($object);

		$this->moduleTemplateEngine[$class]['pattern']  = $pattern;
		$this->moduleTemplateEngine[$class]['function'] = $function;
		$this->moduleTemplateEngine[$class]['object']   = $object;
		
	}
	
	// Retrieve Template Object
	public function retTempObj($className) {
		return($this->moduleTemplateEngine[$className]['object']);
	}
	
	/*
	Define local variables.
	if second value is not provided, tries to return that value. False if it doesn't exist
	*/
	public function localVars($variable,$value=NULL,$null=FALSE) {		
		
		if (isnull($value) && $null === TRUE) {
			$this->localVars[$variable] = "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%";
			return(TRUE);
		}
		
		if(isset($value)) {
			$this->localVars[$variable] = $value;
			return(TRUE);
		}
		else if (isset($this->localVars[$variable])) {
			if ($this->localVars[$variable] == "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%") {
				return(NULL);
			}
			return($this->localVars[$variable]);
		}
		else {
			return(FALSE);
		}
		
		return(FALSE);
	}
	
	/* Returns an array identical to the original LocalVars arrays */
	/* This function is only for migration purposes and should be removed ASAP */
	public function localVarsExport() {
		return($this->localVars);
	}
	
	// load == define which tempalte to use. "Default" was setup above
	// include == fire off the 'header' or the 'footer'
	public function eTemplate($func,$value) {
		
		global $engineVars;
		
		if ($func == "load") {
			if (file_exists($engineVars['tempDir']."/".$value)) {
				$this->template = $engineVars['tempDir']."/".$value;
				$engineVars['currentTemplate'] = $this->template;
			}
			else {
				return(FALSE);
			}
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
	
	// returns the current template directory
	public function currentTemplate() {
		return($this->template);
	}
	
	// hardbreak causes the function to exit immediately on a FALSE ACL return if set to TRUE
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
			foreach ($this->aclgroups as $key => $value) {
				
				if ($value === FALSE) {
					$auth = FALSE;
					$this->accessControlDenied();
					exit;
				}
				else if ($value === TRUE) {
					$auth = TRUE;
				}
				else {
					// Safety check in case of errors
					$auth = NULL;
				}
			}
	
			// Safety check in case of errors
			if (isnull($auth)) {
				$this->accessControlDenied();
				exit;
			}
			
			return($auth);
		}
		
		if ($action == "clear") {
			unset($this->acl);
			$this->acl = array();
			$aclCount  = 0;
			return(TRUE);
		}
		
		if(!array_key_exists($action,$this->accessMethods)) {
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
					die;
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
	
	/* Returns the original dbTables array from the Old EngineCMS. This is provided to ease 
	transition into the new system */ 
	public function dbTablesExport() {
		$dbTablesArray = array();
		
		$dbTablesArray                             = $this->dbTables;
		$dbTablesArray["engineDBInfo"]["database"] = $this->dbDatabase;
		
		return($dbTablesArray);
		
	}
	
	public function login($loginType) {
		if (isset($this->loginFunctions[$loginType])) {
			if($this->loginFunctions[$loginType]($this->cleanPost['RAW']['username'],$this->cleanPost['RAW']['password'])) {
				return(TRUE);
			}
		}
		return(FALSE);
	}
	
	######################################################################
	#Private Functions
	######################################################################

	private function autoloader($className) {
		
		if (!class_exists($className, FALSE)) {

			if (!file_exists($this->availableModules[$className])) {
				return(FALSE);
			}

			require_once($this->availableModules[$className]);
			return(TRUE);
		}
		
		return;
	}

	private function getHTTP_REFERERServer($referer) {
		
		$server = NULL;
		
		if(preg_match('@^(?:https?://)?([^/]+)@i',$referer,$matches)) {
			$server = $matches[1];
		}
		
		return($server);
	}

	private function engineLog($type="access",$function=NULL,$message=NULL) {

		global $engineVars;
		global $engineDB;

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

		if (debugNeeded("log")) {
			debugDisplay("log","\$results",1,"Contents of the \$results array.",$results);
		}

		return(TRUE);
	}
		
	private function accessControlDenied() {
		global $engineVars;
		
		ob_end_clean();
		header( 'Location: '.$engineVars['loginPage'].'?page='.$_SERVER['PHP_SELF']."&qs=".(urlencode($_SERVER['QUERY_STRING'])) ) ;
		//die("No Access Here");
		//return(FALSE);
	}
		
	// This has to be public for the callback to work properly. 
	//
	// This function needs some performance work. 
	//	
	public function displayTemplate($content) {
		global $engineVars;
		
		$contentArray = preg_split('/<!-- engine Instruction break -->/',$content);
		$content = "";
		
		foreach ($contentArray as $line) {

			if (preg_match("/<!-- engine Instruction (\w+) -->/",$line,$matches)) {
				switch($matches[1]) {
					case "displayTemplateOff":
					$this->displayTemplateOff = TRUE;
					break;
					case "displayTemplateOn":
					$this->displayTemplateOff = FALSE;
					break;
				}
			}

			if ($this->displayTemplateOff === FALSE) {
				//local var replacements
				$line = preg_replace_callback("/\{local\s+?var=\"(.+?)\"\}/",array( &$this, 'localMatches'),$line);

				//engineVar replacements
				$line = preg_replace_callback("/\{engine\s+?var=\"(.+?)\"\}/",array( &$this, 'engineVarMatches'),$line);

				//engine Replacements	
				$line = preg_replace_callback("/\{engine\s+(.+?)\}/",array( &$this, 'engineMatches'),$line);

				if ($engineVars['replaceDoubleQuotes'] === TRUE) {
					$line = preg_replace_callback('/(<[^"]+=)("{2})([^>]+>)/',array( &$this, 'engineDQMatches'),$line);
				}
				
				// Check to see if the pattern matches the "standard" for
				// module templates. If so, see if the module is loaded. 
				// If no, try to load the module and create a temporary 
				// instance of it to get the replacement pattern and function
				preg_match("/\{(.+?)\s(.+?)\}/",$line,$matches);
				if (isset($matches[1]) && !is_empty($matches[1])) {
					if (!class_exists($matches[1], FALSE)) {
						$temp = @new $matches[1]();
					}
				}
				
				//module Replacements
				foreach ($this->moduleTemplateEngine as $plugin) {
					if(isset($plugin['pattern']) && isset($plugin['function'])) {
						$this->recurseNeeded = TRUE;
						$line = preg_replace_callback($plugin['pattern'],$plugin['function'],$line);
					}
				}
				
				//add a line break, \n, after <br /> ... makes the source a touch prettier. 
				$line = str_replace("<br />","<br />\n",$line);
			}
			
			$content .= $line;
		}
		
		if ($this->recurseNeeded === TRUE) {
			if ($this->recurseCount < $this->recurseLevel) {
				$this->recurseCount++;
				$content = $this->displayTemplate($content);
			}
			// There has GOT to be a better way to do this
			$backtrace = debug_backtrace();
			if ($backtrace[0]['function'] != "displayTemplate") {
				$this->recurseCount = 0;
			}
		}

		return($content);
	}
	
	
	private function engineVarMatches($matches) {
		global $engineVars;
		
		$output = (!empty($engineVars[$matches[1]]))?$engineVars[$matches[1]]:"";

		return($output);

		//Things to impliment 
		// Email obsfucation
		// All Uppercase
		// All Lowercase 
		// Title Case (cap first letter of each word)

	}
	
	private function localMatches($matches) {

		$output = (isset($this->localVars[$matches[1]]) && !is_empty($this->localVars[$matches[1]]))?$this->localVars[$matches[1]]:"";

		return($output);

		//Things to impliment 
		// Email obsfucation
		// All Uppercase
		// All Lowercase 
		// Title Case (cap first letter of each word)

	}
	
	private function engineDQMatches($matches) {
		global $engineVars;
		
		$output = $matches[1].'"'.$engineVars['replaceDQCharacter'].'"'.$matches[3];
		return($output);
	}

	private function engineMatches($matches) {

		//Debugging Comments
		//$output = "debug: <pre>".obsafe_print_r($matches)."</pre>";

		$attPairs  = split("\" ",$matches[1]);

		//Debugging Comments
		//$output .= "debug: <pre>".obsafe_print_r($attPairs)."</pre>";
		//return($output);

		foreach ($attPairs as $pair) {
			if (empty($pair)) {
				continue;
			}
			list($attribute,$value) = split("=",$pair,2);
			$temp[$attribute] = str_replace("\"","",$value);
		}

		//$output .= "debug: <pre>".obsafe_print_r($temp)."</pre>";
		//return($output);

		if (isset($temp['name'])) {
			$output = $this->handleMatch($temp);
			return($output);
		}
		else {
			return("name attribute missing");
		}

		return("Error");

	}

	private function handleMatch($attPairs) {

		global $engineVars;

		$output = "Error: handleMatch in template.php";
		switch($attPairs['name']) {
			case "include":
			//$output = "Begin recurseInsert<br />";
			    $output = recurseInsert($attPairs['file'],$attPairs['type']);
			//$output .= "End recurseInsert<br />";
				break;
			case "session":
			    $output = sessionGet($attPairs['var']);
				break;
			case "filelist":
			    $output = filelist($attPairs['dir'],$attPairs['temp']);
				break;
			case "filetemplate":
			    global $engineVars;
			    $output = $engineVars['fileList']->getAttribute($attPairs);
				break;
			case "insertCSRF":
			case "crsf":
			    $output = sessionInsertCSRF();
				break;
			case "email":
			    $output = $engineVars['emailSender'][$attPairs['type']];
				break;
			case "csrfGet":
			    $output = sessionInsertCSRF(FALSE);
			    break;
			case "function":
			    $output = $attPairs['function']($attPairs);
				break;
			default:
			    $output = "Error: name function '".$attPairs['name']."' not found.";
		}
		return($output);
	}
}
?>