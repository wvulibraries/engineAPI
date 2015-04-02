<?php
/**
 * EngineAPI database manager
 * @package EngineAPI\modules\db
 */

/**
 * EngineAPI database driver abstract class
 *
 * @package EngineAPI\modules\db
 */
abstract class dbDriver {
    /**
     * @var PDO
     */
    protected $pdo;

	/**
	 * @var array
	 */
	protected $connectionParams;

    /**
     * Transaction nesting counter
     * @var int
     */
    protected $transNestingCounter = 0;

    /**
     * [Flag] Rollback-only mode for current transaction
     * @var bool
     */
    protected $transRollbackOnly = FALSE;

    /**
     * [Flag] Place connection into read-only mode, which will do it's best to only allow SELECT sql calls
     * @var bool
     */
    protected $readOnlyMode = FALSE;

    /**
     * [Flag] Automatically encode/decode arrays and objects for storage.
     * If turned off, passing an array or object will trigger an error
     * @var bool
     */
    public $autoEncode = TRUE;

	/**
	 * [Magic Method] What to do when this object is used as a string
	 * @return string
	 */
	public function __toString() {
        return get_class($this)."\n";
    }

	/**
	 * [Magic Method] Returns array of object attributes which should be saved when serialized
	 * @return array
	 */
	public function __sleep(){
		return array('connectionParams','readOnlyMode','autoEncode');
	}

	/**
	 * [Magic Method] Callback method to wake $this back up after being de-serialized
	 */
	public function __wakeup(){
		$this->createPDO();
	}

    /**
     * Extract a given param from a list of params
     *
     * This extracts the given params and removes it from the array, or returns the given default value
     *
     * @author David Gersting
     * @param string|int $param
     * @param array      $params
     * @param mixed      $default
     * @return mixed
     */
    public function extractParam($param, &$params, $default = NULL) {
        // Make operation case-insensitive
        $params = array_change_key_case($params, CASE_LOWER);
        $param  = strtolower($param);

        // Use array_key_exists() because we need to get a null or false value
        if (array_key_exists($param, $params)) {
            $return = $params[$param];
            unset($params[$param]);

            return $return;
        }
        else {
            return $default;
        }
    }

    /**
     * Build a PDO DSN string based on passed params
     *
     * @author David Gersting
     * @param string $driver
     * @param array  $params
     * @return string string
     */
    public function buildDSN($driver, $params) {
        $driver = trim(strtolower($driver));

        return "$driver:".http_build_query((array)$params, '', ';');
    }

	/**
	 * Create the PDO object
	 * @return bool|object
	 */
	protected function createPDO(){
		try{
			$obj = new ReflectionClass('PDO');
			if(!sizeof($this->connectionParams)) $this->connectionParams = func_get_args();
			$this->pdo = $obj->newInstanceArgs($this->connectionParams);
		}catch (PDOException $e){
			trigger_error(__METHOD__."() Failed to create PDO object! ({$e->getMessage()})", E_USER_ERROR);
			return FALSE;
		}
		return $this->pdo;
	}

    /**
     * Return this driver's underlying PDO object
     *
     * @author David Gersting
     * @return PDO
     */
    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Return TRUE if driver is in read-only mode
     *
     * @return bool
     */
    public function isReadOnly() {
        return $this->readOnlyMode;
    }

    /**
     * Parse the given SQL for compliance with read-only mode
     *
     * @param $sql
     * @return bool
     */
    public function chkReadOnlySQL($sql) {
        // Remove SQL comments
        $sql = preg_replace('/^--.*?$/', '', $sql);
        // There's got to be a better way to combine this...
        if (preg_match('/^\s*(?:ALTER|CREATE|DELETE|INSERT|UPDATE|DROP|TRUNCATE|RENAME|REPLACE|LOAD DATA)/i', $sql)) return FALSE;
        if (preg_match('/;\s*(?:ALTER|CREATE|DELETE|INSERT|UPDATE|DROP|TRUNCATE|RENAME|REPLACE|LOAD DATA)/i', $sql)) return FALSE;

        return TRUE;
    }

	/**
	 * Returns TRUE if we're currently connected
	 * @return bool
	 */
	public function isConnected(){
		return ($this->pdo instanceof PDO);
	}

    // -------------------------------

    /**
     * Prepare the given SQL for execution
     *
     * @param string                        $sql
     *        The SQL to prepare
     * @param array|keyValuePairs|bool|null $params
     *        If NULL, then $sql is assumed to be ready to execute as-is (and will be auto-executed)
     *        If FALSE, the the statement is returned for manual manipulation and execution
     *        Else, $params will be passed to statement's execute() method as-is
     * @return dbStatement
     */
    public abstract function query($sql, $params = NULL);

    /**
     * Escape the given var to use used safely in SQL for this driver
     *
     * @param $var
     * @return string
     */
    public abstract function escape($var);

    /**
     * Start a transaction
     *
     * @return bool
     */
    public abstract function beginTransaction();

    /**
     * Commit (cancel) the current transaction
     *
     * @return bool
     */
    public abstract function commit();

    /**
     * Rollback (cancel) the current transaction
     *
     * @return bool
     */
    public abstract function rollback();

    /**
     * Return TRUE if currently in a transaction
     *
     * @return bool
     */
    public abstract function inTransaction();

    /**
     * Place this connection into 'Read Only' mode.
     * The driver will do it's best ability to prevent database writes in this mode.
     *
     * Warning: placing a driver into read-only mode will cause a degradation in performance when making SQL calls
     * due the additional code needed to parse and check the SQL to be executed.
     *
     * @param bool $newState
     * @return bool
     */
    public abstract function readOnly($newState = TRUE);

    /**
     * Disconnect the underlying driver and self-destruct
     *
     * @return bool
     */
    public abstract function destroy();

	// -------------------------------

	/**
	 * Return array of fields/columns for a given table
	 * @param string $table
	 * @return array
	 */
	public abstract function listFields($table);
}