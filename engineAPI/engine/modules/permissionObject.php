<?php

class permissionObject {
	
	/*
	$table is the mySQL table where permissions are stored. Expected fields are:
	ID (int, unsigned);
	name (varchar(75); name of the function/action that will be tested against);
	value (int, unsigned; value that we are checking against)
	*/
	private $table     = NULL;
	private $engine    = NULL;
	private $permsList = array();
	
	function __construct($table,$engine) {
		$this->table  = $table;
		$this->engine = $engine;
		
		$sql = sprintf("SELECT value FROM %s ORDER BY value + 0",$this->engine->openDB->escape($this->table));
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error pulling permissions from table in Constructor");
		}
		
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_NUM)) {
			$this->permsList[] = (string)$row[0];
		}
	}
	
	function __destruct() {
	}
	
	public function insert($function) {
		
		// Check for Duplicates
		$sql = sprintf("SELECT * FROM %s WHERE name='%s'",
			$this->engine->dbTables($this->table),
			$this->engine->openDB->escape($function)
			);

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return(FALSE);
		}
		
		if (mysql_num_rows($sqlResult['result']) > 0) {
			return(FALSE);
		}
		
		// Make sure that only valid Strings are entered
		$return = preg_match("/^[a-zA-Z0-9\-\_]+$/",$this->engine->openDB->escape($function));
		if ($return == 0) {
			return(FALSE);
		}
		
		$value = $this->generateNextNumber();
		if (empty($value)) {
			return(FALSE);
		}
		
		$sql = sprintf("INSERT INTO %s (name,value) VALUES('%s','%s')",
			           $this->engine->dbTables($this->table),
					   $this->engine->openDB->escape($function), 
					   $this->engine->openDB->escape($value)
					);

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);

		if (!$sqlResult['result']) {
			//print webHelper_errorMsg($sql.":". $sqlResult['error']);
			return(FALSE);
		}

		return(TRUE);
	}
	
	// $number = assigned Number
	// $permission = number of permissions
	// determine $number contains $permission.
	public function checkPermissions($number,$permission) {

		if (bccomp($permission,$number) == 1) {
			return(FALSE);
		}

	    for ($I = count($this->permsList) - 1;$I >= 0; $I--) {

			if (bccomp($permission,$number) == 1) {
				return(FALSE);
			}

			if (bccomp($number,$this->permsList[$I]) == 1 || bccomp($number,$this->permsList[$I]) == 0) {
				if (bccomp($this->permsList[$I],$permission) == 0) {
					return(TRUE);
				}

				$number = bcsub($number,$this->permsList[$I]);
				continue;
			}
		}

	    return(FALSE);	
	}
	
	// $permissions is the security number that the user currently has
	public function buildFormChecklist($permissions) {
		
		$sql = sprintf("SELECT * FROM %s",
			$this->engine->openDB->escape($this->engine->dbTables("permissions"))
			);
			
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error pulling permissions from table.");
		}
		
		// This should be in a template instead of hard-coded HTML
		$output = "<ul class=\"perissionsCheckBoxList\">";
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
	
	private function generateNextNumber() {
		
		$sql = sprintf("SELECT value FROM %s",$this->engine->openDB->escape($this->engine->dbTables($this->table)));
		
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);
		
		$count = "0";
		while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM)) {
			if (bccomp($count,$row[0]) == -1) {
				$count = $row[0];
			}
		}
		
		if(empty($count)) {
			return(1);
		}
		
		return(bcmul($count, 2));
	}
}

?>