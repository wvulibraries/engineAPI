<?php

class engineDB {
	
	public $dbLink   = NULL;
	private $username = NULL;
	private $password = NULL;
	private $database = NULL;
	private $server   = NULL;
	private $port     = NULL;
	private $die      = TRUE; // If set to FALSE, we will not die when a database cannot be connected too.
	
	public $status    = FALSE; // If the connection to the database succeeds this is set to TRUE
	
	public $sanitize   = FALSE;
	public $queryArray = TRUE;
	
	private $transArray = array(); // array of transaction queries 
	
	//private $server     = "localhost";
	//private $serverPort = "3306";
	
	function __construct($user,$passwd,$server,$port,$db,$die=TRUE) {
		
		$this->username = $user;
		$this->password = $passwd;
		$this->database = $db;
		$this->server   = $server;
		$this->port     = $port;
		$this->die      = $die;
		
		// Check that we have a Username, Password, and Database provided
		if (isset($user) && isset($passwd) && isset($db)) {
			$this->connect();
		}
	}
	
	function __destruct() {
		if ($this->status == TRUE) {
			@mysql_close($this->dbLink);
		}
	}
	
	/* 
	 *
	 * PUBLIC FUNCTIONS 
	 *
	 */
	
	function select_db($db) {
		
		$this->testConnection(TRUE);
		
		if(!isset($db)) {
			return(FALSE);
		}
		
		if ($this->dbLink && mysql_select_db($db,$this->dbLink)) {
			// Success
			$this->database = $db;
			return(TRUE);
		}
		
		return(FALSE);
	}
	
	function escape($string) {
		
		$this->testConnection(TRUE);
		$this->select_db($this->database);
		
		if(isnull($string)) {
			$string = "NULL";
		}
		else if (is_bool($string)) {   
	        $var = ($string) ? 1 : 0;
	    }
	
		return(mysql_real_escape_string($string,$this->dbLink));
	}
	
	// inserts returns:
	// FALSE on failute
	// ID on insert
	// TRUE on UPDATE, DELETE, DROP
	// resource on SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset

	// If $this->queryArray == TRUE an array is returned with the following structure
	// 
	// $array{result} = what would be returned if arrayReturn == False
	// $array{affectedRows}
	// $array{errorNumber} = FALSE, unless defined
	// $array{error} = FALSE, unless defined
	// $array{info} = results of mysql_info
	// $array{id} = id of last insert if insert query, otherwise FALSE
	// $array{query} = query that was sent
	
	// $this->sanitize is reset to FALSE after executing
	function query($query) {
		
		$this->testConnection(TRUE);
		
		$result      = FALSE;
		$resultArray = array();
		$queryInsert = FALSE;
		
		// no dbLink, return false
		if (!isset($this->dbLink) || !isset($query)) {
			return(FALSE);
		}
		
		$this->select_db($this->database);
		
		if($this->sanitize) {
			$query = mysql_real_escape_string($query,$this->dbLink);
		}
	
		// Determine if the Query is an INSERT statement
		// If yes, set $queryInsert
		if(preg_match('/^INSERT/i',$query)) {
			$queryInsert = TRUE;
		}
	
		if($this->queryArray) {
			$resultArray['result'] = mysql_query($query,$this->dbLink);

			// what was i doing here? remove?
			if($queryInsert && $resultArray['result']) {
				$array['result'] = mysql_insert_id($this->dbLink);
			}
			
			$resultArray['affectedRows'] = mysql_affected_rows($this->dbLink);
			$resultArray['errorNumber']  = (mysql_errno($this->dbLink) == 0)?FALSE:mysql_errno($this->dbLink);
			$resultArray['error']        = mysql_error($this->dbLink);
			$resultArray['info']         = mysql_info($this->dbLink);
			$resultArray['id']           = mysql_insert_id($this->dbLink);
			$resultArray['query']        = $query;
		}
		else {
			$result = mysql_query($query,$this->dbLink);
			// If this is an INSERT query, we set the result to the insert ID
			if($queryInsert && $result) {
				$result = mysql_insert_id($this->dbLink);
			}
		}
		
		
		// $this->sanitize = TRUE;
		return(($result)?$result:$resultArray);
	}
	
	// $actions:
	// 		query
	//		run
	//
	// Usage: Add queries
	//		  run (provide table name)
	//        evaluate what happened.
	public function transaction($action, $query=NULL) {
		
		if ($action == "query") {
			if (isnull($query)) {
				return FALSE;
			}
			$this->transArray[] = $query;
		}
		if ($action == "run") {
			
			if (isnull($query)) {
				return FALSE;
			}
			
			// need to have something to work with
			if (count($this->transArray) < 1) {
				return FALSE;
			}
			
			// Begin the transaction
			$result = $this->transBegin($query);
			if ($result === FALSE) {
				return FALSE;
			}
			
			foreach ($this->transArray as $I=>$V) {
				$this->sanitize = FALSE;
				$sqlResult      = $this->query($V);
				
				if (!$sqlResult['result']) {
					$this->transRollback();
					$this->transEnd();
					return FALSE;
				}
				
			}
			
			$result = $this->transCommit();
			if ($result === FALSE) {
				return FALSE;
			}	
			
			$result = $this->transEnd();
			if ($result === FALSE) {
				return FALSE;
			}
		}
		
		return TRUE;
		
	}
	
	// If autocommit is TRUE we don't turn it off
	public function transBegin($table=NULL, $autocommit=FALSE) {
		
		if (isnull($table)) {
			return FALSE;
		}
		
		// check to make sure the table is innodb
		$sql = sprintf("SHOW TABLE STATUS LIKE '%s'",
			$this->escape($table));

		$this->sanitize = FALSE;
		$sqlResult      = $this->query($sql);
		$row            = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		if ($row['Engine'] != "InnoDB") {
			return FALSE;
		}
		
		
		if ($autocommit == FALSE) {
			$sql = sprintf("SET AUTOCOMMIT=0");
			$this->sanitize = FALSE;
			$sqlResult      = $this->query($sql);
		
			if (!$sqlResult['result']) {
				return FALSE;
			}
		}
		
		$sql = sprintf("START TRANSACTION");
		$this->sanitize = FALSE;
		$sqlResult      = $this->query($sql);
		
		if (!$sqlResult['result']) {
			return FALSE;
		}
		
		return TRUE;
		
	}
	
	// If autocommit is TRUE we don't turn it back on ... 
	// No reason to call this function if transBegin was called with TRUE
	public function transEnd($autocommit=FALSE) {
		if ($autocommit == FALSE) {
			$sql = sprintf("SET AUTOCOMMIT=1");
			$this->sanitize = FALSE;
			$sqlResult      = $this->query($sql);
		
			if (!$sqlResult['result']) {
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	public function transRollback() {
		$sql = sprintf("ROLLBACK");
		$this->sanitize = FALSE;
		$sqlResult      = $this->query($sql);
		
		if (!$sqlResult['result']) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	public function transCommit() {
		$sql = sprintf("COMMIT");
		$this->sanitize = FALSE;
		$sqlResult      = $this->query($sql);
		
		if (!$sqlResult['result']) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	// returns an multidimensional array of the results, suitable for sorting
	function makeArray($result) {
		$return = array();
		while ($row = mysql_fetch_row($result)) {
			$return[] = $row;
		}
		return($return);
	}
	
	function test() {
		print "<h3>engineDB Object Information</h3>";
		print "username: $this->username<br />";
		print "password: ".((!is_null($this->password))?"********":"Undefined")."<br />";
		print "database: $this->database<br />";
		print "sanitize: ".(($this->sanitize)?"TRUE":"FALSE")."<br />";
		print "quaryArray: ".(($this->queryArray)?"TRUE":"FALSE")."<br />";
	}
	
	public function testConnection($reconnect=FALSE) {
		if ($this->dbLink && !@mysql_ping($this->dbLink)) {
			
			$this->status = FALSE;
			$this->dbLink = NULL;
			
			if($reconnect === TRUE) {
				$this->connect();
			}
			return(FALSE);
		}
		
		return(TRUE);
	}
	
	private function connect() {
		$this->dbLink = @mysql_connect($this->server.":".$this->port,$this->username,$this->password) or $this->connectFailed();
		//or die("mysql.php dbLink connect");
		
		// Ensure we got connected
		// Select the Database
		if ($this->select_db($this->database)) {
			$this->status = TRUE;
			return(TRUE);
		}
		else {
			$this->connectFailed();
			//die("MYSQL Error, Connecting");
		}
		
		return(FALSE);
	}
	
	public function connectFailed() {
		if ($this->die === FALSE) {
			return(FALSE);
		}
		
		die("MYSQL Error, Connecting ...");
	}
}

?>