<?php

class engineDB {
	

	
	private $dbLink   = NULL;
	private $username = NULL;
	private $password = NULL;
	private $database = NULL;
	
	public $status    = FALSE; // If the connection to the database succeeds this is set to TRUE
	
	public $sanitize   = TRUE;
	public $queryArray = TRUE;
	
	//private $server     = "localhost";
	//private $serverPort = "3306";
	
	function __construct($user,$passwd,$db) {

		global $engineVars;
		
		// Check that we have a Username, Password, and Database provided
		if (isset($user) && isset($passwd) && isset($db)) {
			$this->dbLink = mysql_connect($engineVars['mysql']['server'].":".$engineVars['mysql']['port'],$user,$passwd);
			
			// Ensure we got connected
			// Select the Database
			if ($this->dbLink && mysql_select_db($db,$this->dbLink)) {
				// Success
				$this->username = $user;
				$this->password = $passwd;
				$this->database = $db;
				$this->status   = TRUE;
			}
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
		
		if(isnull($string)) {
			$string = "NULL";
		}
		else if (is_bool($string)) {   
	        $var = ($var) ? 1 : 0;
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
	
	// $this->sanitize is reset to TRUE after executing
	function query($query) {
		
		$result      = FALSE;
		$resultArray = array();
		$queryInsert = FALSE;
		
		// no dbLink, return false
		if (!isset($this->dbLink) || !isset($query)) {
			return(FALSE);
		}
		
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
		
		
		$this->sanitize = TRUE;
		return(($result)?$result:$resultArray);
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
}

?>