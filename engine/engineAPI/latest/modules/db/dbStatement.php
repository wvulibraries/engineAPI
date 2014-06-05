<?php
/**
 * EngineAPI database manager
 * @package EngineAPI\modules\db
 */

/**
 * EngineAPI database statement abstract class
 *
 * @package EngineAPI\modules\db
 */
abstract class dbStatement {
    /**
     * @var dbDriver
     */
    protected $dbDriver;
    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var PDOStatement
     */
    protected $pdoStatement;
    /**
     * The timestamp of when the statement was executed
     * @var DateTime
     */
    protected $executedAt;
    /**
     * microtime of how long the statement took to execute
     * @var int
     */
    protected $executedTime;
    /**
     * Array of field names in a result set
     * @var array
     */
    protected $fieldNames;

    /**
     * An array of valid PDO param types
     * @var array
     */
    protected $pdoParamTypes = array(
        PDO::PARAM_BOOL,
        PDO::PARAM_NULL,
        PDO::PARAM_INT,
        PDO::PARAM_STR,
        PDO::PARAM_LOB);

	/**
	 * [Magic Method] What to do when this object is used as a string
	 * @return string
	 */
	public function __toString() {
        $debugEnv = TRUE; // TODO: switch to EngineAPI environments (when they are done)

		return $debugEnv
			? $this->getDebug()
			: get_class($this);
    }

	/**
	 * Returns a lot of debugging information about this statement
	 * @return string
	 */
	public function getDebug(){
		$header = sprintf('%s(%s)', __CLASS__, $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
		$width  = strlen($this->getSQL()) + 10;
		$return = str_repeat("=", $width)."\n";
		$return .= str_pad($header, $width, ' ', STR_PAD_BOTH)."\n";
		$return .= str_repeat("=", $width)."\n";
		$return .= sprintf("SQL: %s\n", $this->getSQL());
		$return .= sprintf("Executed at: %s\n", $this->executedAt->format('g:i:s a'));
		$return .= sprintf("Execute time: %Fs\n", $this->executedTime);
		$return .= str_repeat("-", $width)."\n";
		if ('0000' != $this->errorCode()) {
			$return .= sprintf("Error: [%s] %s\n", $this->errorCode(), $this->errorMsg());
			$return .= str_repeat("-", $width)."\n";
		}
		$return .= sprintf("Row count: %d\n", $this->rowCount());
		$return .= sprintf("Field count: %d\n", $this->fieldCount());
		if ($this->fieldCount()) $return .= sprintf("Field names: %s\n", implode(',', $this->fieldNames()));
		$return .= sprintf("Affected rows: %d\n", $this->affectedRows());
		$return .= sprintf("Insert ID: %d\n", $this->insertId());
		$return .= str_repeat("=", $width)."\n";

		return $return;
	}

    /**
     * Setter method for the pdoStatement instance variable
     *
     * @param $pdoStatement
     * @return bool
     */
    public function set_pdoStatement($pdoStatement) {
        if (!($pdoStatement instanceof PDOStatement)) {
            errorHandle::newError(__METHOD__."() Invalid param passed for pdoStatement. (only PDOStatement allowed) ", errorHandle::DEBUG);

            return FALSE;
        }

        $this->pdoStatement = $pdoStatement;

        return TRUE;
    }

    /**
     * Return this statement's underlying PDOStatement object
     *
     * @author David Gersting
     * @return PDOStatement
     */
    public function getStatement() {
        return $this->pdoStatement;
    }

    /**
     * Return TRUE if the statement has been executed
     *
     * @author David Gersting
     * @return bool
     */
    public function isExecuted() {
        return isset($this->executedAt);
    }

    /**
     * Return the SQL to be used
     *
     * @author David Gersting
     * @return string
     */
    public function getSQL() {
        return $this->pdoStatement->queryString;
    }

    /**
     * Determine best PDO param type base on the given data
     *
     * @author David Gersting
     * @param $data
     * @return bool|int
     */
    protected function determineParamType($data) {
        switch (gettype($data)) {
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'integer':
                return PDO::PARAM_INT;
            case 'null':
                return PDO::PARAM_NULL;
            default:
            case 'string':
            case 'double':
            case 'array':
            case 'object':
                return PDO::PARAM_STR;
            case 'resource':
                errorHandle::newError(__METHOD__."() - Unsupported param type! (can't store a resource)", errorHandle::DEBUG);

                return FALSE;
        }
    }

    // -------------------------------

    /**
     * Create a database statement object
     *
     * @param dbDriver $parentConnection
     * @param string   $sql
     */
    public abstract function __construct($parentConnection, $sql);

    /**
     * Execute the prepared SQL
     *
     * This method MUST accept an unknown number of params (via PHP's func_get_args)
     * @example execute(1, "abc", (float)3.25)
     *
     * @return bool
     */
    public abstract function execute();

    /**
     * Manually bind a param to the prepared statement
     *
     * This binds the variable (by reference) to the prepared statement
     *
     * @param int|string $param
     * @param mixed      $var
     * @param int        $type
     * @param int        $length
     * @param array      $options
     *
     * @return mixed
     */
    public abstract function bindParam($param, &$var, $type = PDO::PARAM_STR, $length = NULL, $options = NULL);

    /**
     * Manually bind a param to the prepared statement
     *
     * This bind the value to the prepared statement
     *
     * @param int|string $param
     * @param mixed      $value
     * @param int        $type
     * @param int        $length
     *
     * @return mixed
     */
    public abstract function bindValue($param, $value, $type = PDO::PARAM_STR, $length = NULL);

    /**
     * Return the number of fields in the prepared SQL/result
     * (will always be 0 until the statement is executed)
     *
     * @return int
     */
    public abstract function fieldCount();

    /**
     * Return an array of field names in the result
     * (will be a blank array until the statement is executed)
     *
     * @return array
     */
    public abstract function fieldNames();

    /**
     * Return the number of rows in the result
     *
     * @return int
     */
    public abstract function rowCount();

    /**
     * Return the number of rows which were affected by the executed SQL
     *
     * @return int
     */
    public abstract function affectedRows();

    /**
     * Return the last insertID from the executed SQL
     *
     * @return int
     */
    public abstract function insertId();

    /**
     * Fetch one row at a time from the result setup according to the current fetch mode.
     *
     * @param $fetchMode
     *        The 'Fetch mode' to use. See FETCH_* constants on dbStatement
     * @return mixed
     */
    public abstract function fetch($fetchMode = PDO::FETCH_ASSOC);

    /**
     * Return an array of all rows from the result set.
     *
     * @param $fetchMode
     *        The 'Fetch mode' to use. See FETCH_* constants on dbStatement
     * @return array
     */
    public abstract function fetchAll($fetchMode = PDO::FETCH_ASSOC);

    /**
     * Return only the given field from one row at a time. (Defaults to the 1st field)
     *
     * @param int|string $field
     *        Either the index (base 0) or the name of the field to fetch
     *        Note: passing the index is often faster
     * @return mixed
     */
    public abstract function fetchField($field = 0);

    /**
     * Return an array of all rows of only the given field. (Defaults to the 1st field)
     *
     * @param int|string $field
     *        Either the index (base 0) or the name of the field to fetch
     *        Note: passing the index is often faster
     * @return mixed
     */
    public abstract function fetchFieldAll($field = 0);

    /**
     * The SQLSTATE (ANSI SQL-92) of the last database operation
     *
     * @return int
     */
    public function sqlState(){
        return $this->pdoStatement->errorCode();
    }

    /**
     * Return TRUE if an error has occured, FALSE otherwise
     * 
     * @return bool
     */
    public function error(){
        return $this->sqlState() != '00000';
    }

    /**
     * The error code/number of the last error *driver specific*
     *
     * @return string
     */
    public function errorCode(){
        $errorMsg = $this->pdoStatement->errorInfo();
        return $errorMsg[1];
    }

    /**
     * The error message of the last error *driver specific*
     *
     * @return string
     */
    public function errorMsg(){
        $errorMsg = $this->pdoStatement->errorInfo();
        return $errorMsg[2];
    }
}
