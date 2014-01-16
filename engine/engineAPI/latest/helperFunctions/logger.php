<?php

// Class to handle logging engineAPI calls. 
// this should be updated to be expendable 
// so that it can be the base logging for applications as well. 

class logger {
	
	private static $classInstance;

    /**
     * @var dbDriver
     */
    private $database;

	function __construct() {

	}

	public static function getInstance($database = NULL) {
		if (!isset(self::$classInstance)) {

            if(isempty($database) || !db::exists($database)) return FALSE;

			self::$classInstance = new self();
			self::$classInstance->set_database(db::get($database));
		}

		return self::$classInstance;
	}

	public function set_database($database) {

		if ($database instanceof dbDriver) {
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

		if (!$engineVars['log']) return FALSE;

		// setup the variables
		$date      = time();
		$ip        = isset($_SERVER['REMOTE_ADDR'])     ? $_SERVER['REMOTE_ADDR']     : NULL;
		$referrer  = isset($_SERVER['HTTP_REFERER'])    ? $_SERVER['HTTP_REFERER']    : NULL;
		$resource  = isset($_SERVER['REQUEST_URI'])     ? $_SERVER['REQUEST_URI']     : NULL;
		$queryStr  = isset($_SERVER['QUERY_STRING'])    ? $_SERVER['QUERY_STRING']    : NULL;
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : NULL;
		$site      = isset($engineVars['server'])       ? $engineVars['server']       : NULL;

		$sql    = 'INSERT INTO log (date,ip,referrer,resource,useragent,function,type,message,querystring,site) VALUES(?,?,?,?,?,?,?,?,?,?)';
		$params = array($date, $ip, $referrer, $resource, $useragent, $function, $type, $message, $queryStr, $site);
		$this->database->query($sql, $params);

		return TRUE;
	}

}

?>