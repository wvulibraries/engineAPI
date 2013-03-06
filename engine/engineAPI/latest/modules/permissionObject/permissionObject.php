<?php
/**
 * EngineAPI permissionObject module
 * This system uses the bitwise system where each permission is assigned a unique int
 *
 * @package EngineAPI\modules\permissionObject
 */
class permissionObject {
	/**
	 * MySQL table where permissions are stored
	 * Expected fields are:
	 *  - ID (int, unsigned)    - Primary Key
	 *  - name (varchar(75))    - Name of the function/action that will be tested against
	 *  - value (int, unsigned) - Value that we are checking against
	 * @var string
	 */
	private $table     = NULL;
	/**
	 * Instance of EngineAPI
	 * @var EngineAPI
	 */
	private $engine    = NULL;
	/**
	 * @var array
	 */
	private $permsList = array();

	/**
	 * Class constructor
	 *
	 * @todo Remove deprecated usage of webHelper_errorMsg()
	 * @param string $table
	 *        The database table the permissions are stored in
	 * @param engineDB $database
	 *        The database object to interact with
	 */
	function __construct($table,$database=NULL) {
		$this->table    = $table;
		$this->engine   = EngineAPI::singleton();
		$this->database = ($database instanceof engineDB) ? $database : $this->engine->openDB;

		$sql = sprintf("SELECT value FROM `%s` ORDER BY value + 0",
			$this->database->escape($this->table));
		$sqlResult = $this->database->query($sql);

		if(!$sqlResult['result']) return webHelper_errorMsg("Error pulling permissions from table in Constructor");

		while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_NUM)){
			$this->permsList[] = (string)$row[0];
		}
	}

	/**
	 * Insert new function into permission table
	 * In this contact, function is the 'thing' that is to protect (eg create_user)
	 *
	 * @param $function
	 * @return bool
	 */
	public function insert($function) {

		// Check for Duplicates
		$sql = sprintf("SELECT * FROM `%s` WHERE name='%s'",
			$this->engine->dbTables($this->table),
			$this->database->escape($function));
		$sqlResult = $this->database->query($sql);

		if(!$sqlResult['result']) return(FALSE);
		if(mysql_num_rows($sqlResult['result']) > 0) return(FALSE);

		// Make sure that only valid Strings are entered
		$return = preg_match("/^[a-zA-Z0-9\-\_]+$/",$this->database->escape($function));
		if($return == 0) return(FALSE);

		$value = $this->generateNextNumber();
		if(empty($value)) return(FALSE);

		$sql = sprintf("INSERT INTO `%s` (name,value) VALUES('%s','%s')",
			$this->engine->dbTables($this->table),
			$this->database->escape($function),
			$this->database->escape($value));
		$sqlResult = $this->database->query($sql);

		if(!$sqlResult['result']) return(FALSE);
		return(TRUE);
	}

	/**
	 * Check a given permission
	 *
	 * @param string $number
	 *        The user's assigned number
	 * @param string $permission
	 *        The number for the permission you are checking
	 *
	 * @return bool
	 */
	public function checkPermissions($number,$permission) {
		if(bccomp($permission,$number) == 1) return(FALSE);

		for($I = count($this->permsList) - 1;$I >= 0; $I--){
			if(bccomp($permission,$number) == 1) return(FALSE);

			if(bccomp($number,$this->permsList[$I]) == 1 || bccomp($number,$this->permsList[$I]) == 0){
				if(bccomp($this->permsList[$I],$permission) == 0) return(TRUE);
				$number = bcsub($number,$this->permsList[$I]);
				continue;
			}
		}

		return(FALSE);	
	}

	/**
	 * Build the 'wall of checkboxes' for the possible permissions
	 *
	 * @param string $permissions
	 *        The security number that the user currently has
	 * @return string
	 */
	public function buildFormChecklist($permissions) {
		$sql = sprintf("SELECT * FROM `%s`",
			$this->database->escape($this->engine->dbTables("permissions")));
		$sqlResult = $this->database->query($sql);

		if(!$sqlResult['result']) return webHelper_errorMsg("Error pulling permissions from table.");

		// This should be in a template instead of hard-coded HTML
		$output = '<ul class="perissionsCheckBoxList">';
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_BOTH)) {
			$row['name']  = htmlSanitize($row['name']);
			$row['value'] = htmlSanitize($row['value']);

			$output .= "<li>";
			$output .= '<input type="checkbox" name="permissions[]" id="perms_'.$row['name'].'" value="'.$row['value'].'"';
			$output .= ($this->checkPermissions($permissions,$row['value']))?" checked":"";
			$output .= ' />';
			$output .= '<label for="perms_'.$row['name'].'">'.$row['name'].': </label>';
			$output .= "</li>";
		}
		$output .= "</ul>";

		return($output);
	}

	/**
	 * Retrieves the next available permission number
	 * @return string
	 */
	private function generateNextNumber() {
		$sql = sprintf("SELECT value FROM `%s`",
			$this->database->escape($this->engine->dbTables($this->table)));
		$sqlResult = $this->database->query($sql);

		$count = "0";
		while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM)) {
			if (bccomp($count,$row[0]) == -1) {
				$count = $row[0];
			}
		}

		if(empty($count)) return(1);
		return(bcmul($count, 2));
	}
}
?>