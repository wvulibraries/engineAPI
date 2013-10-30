<?php
/**
 * EngineAPI localvars module
 * @package EngineAPI\modules\localvars
 */
class localvars extends config {

	const CONFIG_TYPE     = "local";

	/**
	 * Class constructor
	 */
	function __construct() {		
		templates::defTempPatterns("/\{local\s+(.+?)\}/","localvars::templateMatches",$this);
	}

	/**
	 * Engine tag handler
	 * @param $matches
	 *        Matches passed by template handler
	 * @return string
	 */
	public static function templateMatches($matches) {

		$attPairs      = attPairs($matches[1]);

		$localvars = parent::export(self::CONFIG_TYPE);
		
		$variable = self::get($attPairs['var']);

		if (!is_empty($variable)) {
			return($variable);
		}

		return("");
		
	}

	/**
	 * Add a localvar
	 *
	 * @param string $var
	 * @param mixed $value
	 * @param bool $null
	 * @return bool
	 */
	public static function add($var,$value,$null=FALSE) {
		
		return parent::set(self::CONFIG_TYPE,$var,$value,$null);
		
	}
	public static function set($var,$value,$null=FALSE) {
		
		return self::add($var,$value,$null);

	}

	public static function isset($name) {
		return parent::isset(self::CONFIG_TYPE,$name);
	}

	/**
	 * Let a local var
	 *
	 * @param string $var
	 * @param string $default
	 * @return mixed
	 */
	public static function get($var,$default="") {
		
		return parent::get(self::CONFIG_TYPE,$var,$default);
		
	}

	/**
	 * Delete a localvar
	 *
	 * @param string $var
	 * @return bool
	 */
	public static function del($var) {
		
		return parent::remove(self::CONFIG_TYPE,$var);
		
	}
	public static function remove($var) {
		
		return self::del(self::CONFIG_TYPE,$var);
		
	}

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
	public static function variable($var,$value=NULL,$null=FALSE) {
		
		return parent::variable(self::CONFIG_TYPE,$var,$value,$null);
		
	}

	/**
	 * Return array of all localvars
	 *
	 * @deprecated
	 * @return array
	 */
	public static function export() {
		return parent::export(self::CONFIG_TYPE);
	}

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
            'dbConn'    => EngineAPI::singleton()->openDB,
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
                self::add($params['namespace'].$row[ $nameField ], $row[ $valueField ]);
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
