<?php

// Class to handle logging engineAPI calls. 
// this should be updated to be expendable 
// so that it can be the base logging for applications as well. 

class logger {
	
	private static $classInstance;
	private $database;

	function __construct() {

	}

	public static function getInstance($database = NULL) {
		if (!isset(self::$classInstance)) { 

			if (!$database instanceof engineDB) {
				return FALSE;
			}

			self::$classInstance = new self();
			self::$classInstance->set_database($database);
		}

		return self::$classInstance;
	}

	public function set_database($database) {

		if ($database instanceof engineDB) {
			$this->database = $database;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Record a log message to the log table
	 * @param string $type
	 * @param null $function
	 * @param null $message
	 * @return bool
	 */
	public function log($type="access",$function=NULL,$message=NULL) {

		$engineVars = enginevars::getInstance()->export();
		$engineDB = $this->database;

		if (!$engineVars['log'] || $engineDB->status === FALSE) {
			return FALSE;
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

		return TRUE;
	}

}

?>