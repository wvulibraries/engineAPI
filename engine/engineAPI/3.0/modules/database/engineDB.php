<?php

class engineDB {

    /**
     * MySQL connection resource
     * @var resource
     */
	public $dbLink   = NULL;

    /**
     * MySQL connection username
     * @var string
     */
	private $username = NULL;

    /**
     * MySQL connection password
     * @var string
     */
	private $password = NULL;

    /**
     * MySQL database name
     * @var string
     */
	private $database = NULL;

    /**
     * MySQL connection hostname
     * @var string
     */
	private $server   = NULL;

    /**
     * MySQL connection port number
     * @var int
     */
	private $port     = NULL;

    /**
     * If set to FALSE, we will not die when a database cannot be connected too.
     * @var bool
     * @see $this->connectFailed()
     */
	private $die      = TRUE;

	/**
     * If the connection to the database succeeds this is set to TRUE
     * @var bool
     */
	public $status    = FALSE;

	/**
     * [Deprecated] Set false before running queries
     * @var bool
     */
	public $sanitize   = FALSE;

    /**
     * Return query operations as a result array
     * @var bool
     */
	public $queryArray = TRUE;
    
	/**
     * Transaction query queue
     * @var array
     */
	private $transArray = array(); // array of transaction queries 
	
    /**
     * Class constructor
     * @param string $user    - The username to connect with
     * @param string $passwd  - The password to connect with
     * @param string $server  - The server to connect to
     * @param int $port       - The server port to connect to
     * @param string $db      - The default scheme to use for this connection
     * @param bool $die       - True to treat connection errors as fetal
     */
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

    /**
     * Class destructor (Close the MySQL connection)
     * @return void
     */
	function __destruct() {
		if ($this->status == TRUE) {
			@mysql_close($this->dbLink);
		}
	}
	
    /**
     * Select a new scheme to interact with
     * @param string $db
     * @return bool
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

    /**
     * Escape a given string so it is suitable for use in a SQL statement
     * @param string $string
     * @return string
     */
	function escape($string) {
		
		$this->testConnection(TRUE);
		$this->select_db($this->database);
		
		if(isnull($string)) {
			$string = "NULL";
		}
		else if (is_bool($string)) {   
	        $string = ($string) ? 1 : 0;
	    }
	
		return(mysql_real_escape_string($string,$this->dbLink));
	}
	
    /**
     * Performs a given SQL query against the selected database
     * @param string $query
     * @return array|bool|int|resource
     *
     * If $this->queryArray is true, then an array is returned with the following elements:
     *  + result       what would be returned if arrayReturn == False
     *  + affectedRows Number of affected rows (for UPDATE,DELETE)
     *  + errorNumber  Any error number produced
     *  + error        Any error message produced
     *  + info         Results of mysql_info()
     *  + id           Results of insert_if()
     *  + query        The original SQL query that was used
     * Else the return depends on the SQL query performed:
     *  + INSERT - int (insert id)
     *  + UPDATE/DELETE/DROP - bool (success)
     *  + SELECT/SHOW/DESCRIBE/EXPLAIN - resource
     */
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
	

    /**
     * Add queries to the transaction queue
     * Available actions:
     *   + add - Add query to queue
     *   + run - Evaluate what happened
     * @param string $action
     * @param string $query
     * @return bool
     */
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
	
    /**
     * Starts a new transaction
     *
     * If autocommit is TRUE we don't turn it off
     *
     * @param string $table
     * @param bool $autocommit
     * @return bool
     */
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
	
    /**
     * Ends the transaction
     *
     * If autocommit is TRUE we don't turn it back on ...
     * No reason to call this function if transBegin was called with TRUE
     *
     * @param bool $autocommit
     * @return bool
     */
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

    /**
     * Rolls back (cancels) the transaction
     * @return bool
     */
	public function transRollback() {
		$sql = sprintf("ROLLBACK");
		$this->sanitize = FALSE;
		$sqlResult      = $this->query($sql);
		
		if (!$sqlResult['result']) {
			return FALSE;
		}
		
		return TRUE;
	}

    /**
     * Commits (saves) the transaction to the database
     * @return bool
     */
	public function transCommit() {
		$sql = sprintf("COMMIT");
		$this->sanitize = FALSE;
		$sqlResult      = $this->query($sql);
		
		if (!$sqlResult['result']) {
			return FALSE;
		}
		
		return TRUE;
	}
	
    /**
     * This method takes a MySQL resource and converts it to a multidimensionalD array
     * @param  $result
     * @return array
     */
	function makeArray($result) {
		$return = array();
		while ($row = mysql_fetch_row($result)) {
			$return[] = $row;
		}
		return($return);
	}

    /**
     * This method prints debug info to the screen.
     * Info printed includes: username,password,database name,sanitize state,quaryArray state
     * @return void
     */
	function test() {
		print "<h3>engineDB Object Information</h3>";
		print "username: $this->username<br />";
		print "password: ".((!is_null($this->password))?"********":"Undefined")."<br />";
		print "database: $this->database<br />";
		print "sanitize: ".(($this->sanitize)?"TRUE":"FALSE")."<br />";
		print "quaryArray: ".(($this->queryArray)?"TRUE":"FALSE")."<br />";
	}

    /**
     * Returns true if there is an active database connection
     * @param bool $reconnect Set TRUE to attempt reconnect
     * @return bool
     */
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

    /**
     * Connect to the specified MySQL database
     * @return bool
     */
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

    /**
     * Handle failed database connection attempts
     * @return bool
     * @see $this->die
     */
	public function connectFailed() {
		if ($this->die === FALSE) {
			return(FALSE);
		}
		
		die("MYSQL Error, Connecting ...");
	}
}

?>