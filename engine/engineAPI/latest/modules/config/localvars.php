<?php
/**
 * EngineAPI localvars module
 * @package EngineAPI\modules\localvars
 */
class localvars extends config {

	private static $classInstance;
	protected $variables = array();
	protected $database;

	/**
	 * Class constructor
	 */
	function __construct() {
		$this->configObject = config::getInstance(); 
		templates::defTempPatterns("/\{local\s+(.+?)\}/","localvars::templateMatches",$this);
	}

	public static function getInstance() {
		if (!isset(self::$classInstance)) { 

			self::$classInstance = new self();

			// @TODO this needs updated with new database info
			self::$classInstance->set_database(EngineAPI::singleton()->openDB);
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
	 * Engine tag handler
	 * @param $matches
	 *        Matches passed by template handler
	 * @return string
	 */
	public static function templateMatches($matches) {

		$attPairs      = attPairs($matches[1]);

		$localvars = self::export_static();
		
		$variable = self::get_static($attPairs['var']);

		if (!is_empty($variable)) {
			return($variable);
		}

		return("");
		
	}

	public static function export_static() {
		$localvars = self::getInstance();
		return $localvars->export();
	}

	public static function get_static($var) {
		$localvars = self::getInstance();
		return $localvars->get($var);
	}

	/**
	 * Add a localvar
	 *
	 * @param string $var
	 * @param mixed $value
	 * @param bool $null
	 * @return bool
	 */
	// public function set($var,$value,$null=FALSE) {
		
	// 	return $this->configObject->set(self::CONFIG_TYPE,$var,$value,$null);
		
	// }
	// public function add($var,$value,$null=FALSE) {
		
	// 	return $this->set($var,$value,$null);

	// }

	// public function is_set($name) {
	// 	return $this->configObject->is_set(self::CONFIG_TYPE,$name);
	// }

	/**
	 * Let a local var
	 *
	 * @param string $var
	 * @param string $default
	 * @return mixed
	 */
	// public function get($var,$default="") {
		
	// 	return $this->configObject->get(self::CONFIG_TYPE,$var,$default);
		
	// }

	/**
	 * Delete a localvar
	 *
	 * @param string $var
	 * @return bool
	 */
	// public function remove($var) {
		
	// 	return $this->configObject->remove(self::CONFIG_TYPE,$var);
		
	// }
	// public function del($var) {
		
	// 	return $this->remove(self::CONFIG_TYPE,$var);
		
	// }

	/**
	 * Smartly set/get localvar
	 * If only $var is passed, will get $var
	 * Else will set $var to $value
	 *
	 * @see self::add();
	 * @see self::get();
	 * @deprecated
	 * @param string $var
	 * @param mixed $value
	 * @param bool $null
	 *        Pass-through to self::add()
	 * @return mixed
	 */
	// public function variable($var,$value=NULL,$null=FALSE) {
		
	// 	return $this->configObject->variable(self::CONFIG_TYPE,$var,$value,$null);
		
	// }

	/**
	 * Return array of all localvars
	 *
	 * @deprecated
	 * @return array
	 */
	// public function export() {
	// 	return $this->configObject->export(self::CONFIG_TYPE);
	// }

    /**
     * This will import a key->value database table into local vars (very useful for a 'settings' table)
	 *
	 * @deprecated
     * @param $tblName      - The database table
     * @param $nameField    - The table field holding the setting name
     * @param $valueField   - The table field holding the setting value
     * @param array $params - Optional array of additional params
     *          + dbConn    - The database connection to use (default: EngineAPI::openDB)
     *          + namespace - A namespace to put the imported settings into (default: '')
     *          + sqlWhere  - SQL Where clause to use for sql statement
     * @return bool|int     - Returns number of localVars created, or bool FALSE on error
     */
    public static function dbImport($tblName, $nameField, $valueField, $params=array()){
        // Handle default params
        $params = array_merge(array(
            'dbConn'    => $this->database,
            'namespace' => '',
            'sqlWhere'  => '1=1'
        ), (array)$params);

        // Run SQL
        $dbSettings = $params['dbConn']->query(sprintf("SELECT `%s`,`%s` FROM `%s` WHERE %s",
            $params['dbConn']->escape($nameField),
            $params['dbConn']->escape($valueField),
            $params['dbConn']->escape($tblName),
            $params['dbConn']->escape($params['sqlWhere'])));
        if($dbSettings['result']){
            $settingCount = 0;
            while($row = mysql_fetch_assoc($dbSettings['result'])){
                $this->add($params['namespace'].$row[ $nameField ], $row[ $valueField ]);
                $settingCount++;
            }
            return $settingCount;
        }else{
            errorHandle::newError(sprintf(__METHOD__."() - SQL Error [%s:%s]", $dbSettings['errorNumber'], $dbSettings['error']), errorHandle::DEBUG);
            return FALSE;
        }
    }
}

?>
