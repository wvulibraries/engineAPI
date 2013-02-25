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

    public $inTransaction            = FALSE;
    //public $transactionCount = 0;
	private static $transactionCount = array();
	private $md5ConnName             = NULL;
	
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
			$this->md5ConnName = $this->genDBConnectionHash($this->database,$this->username,$this->password);
			if (!isset($this->transactionCount[$this->md5ConnName])) {
				$this->transactionCount[$this->md5ConnName] = 0;
			}
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
	
	private function genDBConnectionHash($database,$username,$password) {
		return(md5($database."".$username."".$password));
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
		if(is_array($string)) echo '<pre><tt>'.print_r(debug_backtrace(), true).'</tt></pre>';
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
			$resultArray['numRows']      = @mysql_num_rows($resultArray['result']);
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
	public function transBegin($table = NULL, $autocommit = FALSE)
	{
		echo "transBegin(IN) from ".callingLine().":".callingFile()." (count: {$this->transactionCount[$this->md5ConnName]})<br>";
		echo "object hash [".spl_object_hash($this)."]<br>";
		var_dump($this->dbLink);
		if(!$this->inTransaction){
			$this->transEndState = NULL;
			$sqlResult = $this->query("SET AUTOCOMMIT=0");
			if(!$sqlResult['result']){
				errorHandle::newError(__METHOD__."() - Failed to set auto-commit! ({$sqlResult['error']})", errorHandle::HIGH);
				return (FALSE);
			}
			$sqlResult = $this->query("START TRANSACTION");
			if(!$sqlResult['result']){
				errorHandle::newError(__METHOD__."() - Failed to start transaction! ({$sqlResult['error']})", errorHandle::HIGH);
				return (FALSE);
			}

		}

		$this->inTransaction = TRUE;
		$this->transactionCount[$this->md5ConnName]++;
		echo "transBegin(OUT) count: {$this->transactionCount[$this->md5ConnName]}<br>";
		return TRUE;
	}

	/**
	 * Ends the transaction
	 *
	 * If autocommit is TRUE we don't turn it back on ...
	 * No reason to call this function if transBegin was called with TRUE
	 *
	 * @return bool
	 */
	public function transEnd()
	{
		if($this->inTransaction === FALSE){
			return (NULL);
		}
		echo "transEnd(IN) from ".callingLine().":".callingFile()." (count: {$this->transactionCount[$this->md5ConnName]})<br>";
		$returnState = TRUE;
		$this->transactionCount[$this->md5ConnName]--;
		if($this->transactionCount[$this->md5ConnName] == 0){
			if($this->transEndState === TRUE){
				// Do the Commit
				$sqlResult = $this->query("COMMIT");
				if(!$sqlResult['result']){
					errorHandle::newError(__METHOD__."() - Failed to commit the transaction! ({$sqlResult['error']})", errorHandle::CRITICAL);
					$returnState = FALSE;
				}
			}else{
				// Do the Rollback
				if(isnull($this->transEndState)){
					errorHandle::newError(__METHOD__."() - Unknown transaction end state (treating it as a rollback)", errorHandle::DEBUG);
				}
				$sqlResult = $this->query("ROLLBACK");
				if(!$sqlResult['result']){
					errorHandle::newError(__METHOD__."() - Failed to rollback the transaction! ({$sqlResult['error']})", errorHandle::CRITICAL);
					$returnState = FALSE;
				}
			}

			// Reset auto-commit back to the default
			$sqlResult = $this->query("SET AUTOCOMMIT=1");
			if(!$sqlResult['result']){
				errorHandle::newError(__METHOD__."() - Failed to reset auto-commit! ({$sqlResult['error']})", errorHandle::HIGH);
				$returnState = FALSE;
			}

			$this->inTransaction = FALSE;
		}
		echo "transEnd(OUT) count: {$this->transactionCount[$this->md5ConnName]}<br>";
		return $returnState;
	}

	/**
	 * Rolls back (cancels) the transaction
	 * @var bool $autoEndTrans
	 * @return bool
	 */
	public function transRollback($autoEndTrans=TRUE)
	{
		if($this->inTransaction === FALSE){
			return (NULL);
		}

		$this->transEndState = FALSE;

		if($autoEndTrans){
			return $this->transEnd();
		}else{
			return TRUE;
		}
	}

	/**
	 * Commits (saves) the transaction to the database
	 * @var bool $autoEndTrans
	 * @return bool
	 */
	public function transCommit($autoEndTrans=TRUE)
	{
		if($this->inTransaction === FALSE){
			return (NULL);
		}

		if($this->transEndState !== FALSE){
			$this->transEndState = TRUE;
		}

		if($autoEndTrans){
			return $this->transEnd();
		}else{
			return TRUE;
		}
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

	/**
	 * Retrieves a list of all the fields of a given table
	 * @param string $tblName
	 * @param BOOL $primary -- if FALSE does not return the primary key
	 * @param BOOL $array -- if set to true returns an array, otherwise comma delimited string
	 * @return array|null
	 */
	public function listFields($tblName,$primary=TRUE,$array=TRUE) {
		// $tblInfo = $this->tableInfo($tblName);
		// if($tblInfo){
		// 	return array_keys($tblInfo['fields']);
		// }else{
		// 	return NULL;
		// }
		
		$sql = sprintf("SELECT GROUP_CONCAT(column_name) FROM information_schema.columns WHERE table_schema = '%s' AND table_name = '%s' %s",
			$this->database,
			$tblName,
			($primary === TRUE)?"":"AND column_key <> 'PRI'"
			);

		$sqlResult                = $this->query($sql);
		
		if (!$sqlResult['result']) {
			return(NULL);
		}
		
		$row                      = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		
		$fields = $row['GROUP_CONCAT(column_name)'];
		
		if ($array === TRUE) {
			$fields = explode(",",$fields);
		}
		
		return($fields);
	}

	/**
	 * Retrieves an array breaking down the details of a given table
	 * @param string $tblName
	 * @return array|null
	 */
	public function tableInfo($tblName)
	{
		$dbTblFields = $this->query(sprintf("DESCRIBE `%s`", $this->escape($tblName)));
		if($dbTblFields['result']){
			$result = array();

			// Get the table's info
			$dbTblInfo = $this->query(sprintf("SELECT * FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '%s' AND `TABLE_NAME` = '%s' LIMIT 1",
				$this->escape($this->database),
				$this->escape($tblName)));
			$tblInfo = mysql_fetch_assoc($dbTblInfo['result']);

			$result['name']    = $tblInfo['TABLE_NAME'];
			$result['scheme']  = $tblInfo['TABLE_SCHEMA'];
			$result['catalog'] = $tblInfo['TABLE_CATALOG'];

			$result['info'] = array(
				'rows'      => $tblInfo['TABLE_ROWS'],
				'engine'    => $tblInfo['ENGINE'],
				'nextID'    => $tblInfo['AUTO_INCREMENT'],
				'checksum'  => $tblInfo['CHECKSUM'],
				'collation' => $tblInfo['TABLE_COLLATION'],
				'comment'   => $tblInfo['TABLE_COMMENT'],
			);

			$result['stats'] = array(
				'createTime'    => $tblInfo['CREATE_TIME'],
				'updateTime'    => $tblInfo['UPDATE_TIME'],
				'checkTime'     => $tblInfo['CHECK_TIME'],
				'tblType'       => $tblInfo['TABLE_TYPE'],
				'tblVersion'    => $tblInfo['VERSION'],
				'rowFormat'     => $tblInfo['ROW_FORMAT'],
				'avgRowLength'  => $tblInfo['AVG_ROW_LENGTH'],
				'indexLength'   => $tblInfo['INDEX_LENGTH'],
				'dataFree'      => $tblInfo['DATA_FREE'],
				'dataLength'    => $tblInfo['DATA_LENGTH'],
				'maxDataLength' => $tblInfo['MAX_DATA_LENGTH'],
			);

			// Get the table's fields
			while($row = mysql_fetch_assoc($dbTblFields['result'])){
				$result['fields'][$row['Field']] = array(
					'field'   => $row['Field'],
					'type'    => $row['Type'],
					'null'    => ($row['Null'] == 'YES' ? TRUE : FALSE),
					'key'     => $row['Key'],
					'default' => $row['Default'],
					'extra'   => $row['Extra']
				);
			}
			return $result;
		}else{
			return NULL;
		}
	}

	/**
	 * Retrieves an array of the given table's fields which are marked as being unique
	 * @param string $tblName
	 * @return array
	 */
	public function getUniqueFields($tblName)
	{
		$fields = array();
		$tblInfo = $this->tableInfo($tblName);
		if($tblInfo){
			foreach($tblInfo['fields'] as $field){
				if($field['key'] == 'PRI' or $field['key'] == 'UNI'){
					$fields[] = $field['field'];
				}
			}
		}else{
			errorHandle::newError(__METHOD__."() - Can't examine table '$tblName'! (It may not exist)", errorHandle::DEBUG);
		}
		return $fields;

	}

	/**
	 * This method will display a lot of debugging information for the given query result.
	 * Please Note: This is not for use in production code!
	 *
	 * @param array $query
	 *        The database query result (the result of engineDB->query())
	 * @param bool|null $resultTable
	 * 	      Set to TRUE will include a small sample of the actual result set
	 * @param int|null $resultTableNumRows
	 *        Limits the number of rows included with the results table (Default: 5)
	 * @param int|null $resultTableFieldLength
	 *        Limits the number of chars of all the fields included with the results table (Default: 100)
	 * @return void
	 */
	public function displayQuery($query, $resultTable=FALSE, $resultTableNumRows=5, $resultTableFieldLength=100){
		// Catch munged defaults
		if(is_null($resultTable)) $resultTable = FALSE;
		if(is_null($resultTableNumRows)) $resultTableNumRows = 5;
		if(is_null($resultTableFieldLength)) $resultTableFieldLength = 100;

		// Record this usage, as this can be a security hole in production code
		errorHandle::newError(__METHOD__."() - Query debugging used! Remove for production usage!", errorHandle::INFO);

		// Start the table output
		echo '<table align="center" border="4" cellpadding="6">';
		echo '<thead>';
		echo '<tr>';
		echo '<td>';
		echo sprintf('<p>%s</p>', $query['query']);
		echo '<ul>';
		echo sprintf('<li>Query origin: %s:%s</li>', callingLine(), callingFile());
		if($query['errorNumber']){
			echo sprintf('<li>Result: <b style="color: red;">ERROR</b></li>');
			echo sprintf('<li><b>SQL Error:</b> %s:%s</li>', $query['errorNumber'], $query['error']);
		}else{
			echo sprintf('<li>Result: <b style="color: green;">OK</b></li>');
			echo sprintf('<li>Result Size: %s</li>', $query['numRows']);
			echo sprintf('<li>Insert ID: %s</li>', $query['id']);
			echo sprintf('<li>Affected Rows: %s</li>', $query['affectedRows']);
		}
		echo '</ul>';
		echo '</td>';
		echo '</tr>';
		echo '</thead>';
		if($resultTable and !$query['errorNumber'] and $query['numRows'] and $query['result']){
			echo '<tbody>';
			echo '<td>';

			// Get the 1st row of the recSet
			$row = mysql_fetch_assoc($query['result']);

			echo '<table align="center" border="1" cellpadding="3">';
			echo '<thead style="background-color: #999; color: #333">';
			echo '<th>#</th>';
			foreach(array_keys($row) as $field){
				echo sprintf('<th>%s</th>', $field);
			}
			echo '</thead>';
			echo '</tbody>';

			$rowCount = 0;
			do{
				$rowCount++;
				echo '<tr style="vertical-align: top;">';
				echo sprintf('<th style="background-color: #ccc;">%s</th>', $rowCount);
				foreach($row as $field => $value){
					if(isnull($value)) $value='&nbsp;';
					if(strlen($value) > $resultTableFieldLength) $value = substr($value,0,$resultTableFieldLength-3).'...';
					echo sprintf('<td>%s</td>', $value);
				}
				echo '</tr>';

				if($rowCount >= $resultTableNumRows){
					echo '<tr style="vertical-align: top; background-color: #ccc;">';
					echo '<td></th>';
					echo sprintf('<td colspan="%s" style="color: #666"><i>Output truncated at %s rows...</i></td>', sizeof(array_keys(($row))), $resultTableNumRows);
					echo '</tr>';
					break;
				}
			}while($row = mysql_fetch_assoc($query['result']));
			echo '</tbody>';
			echo '</table>';
			echo '</td>';
			echo '</tbody>';
		}
		echo '</table>';
	}
}

?>