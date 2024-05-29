<?php

class revisionControl {
	/**
	 * Display controls to revert to revision
	 * @var bool
	 */
	public $displayRevert = TRUE;
	/**
	 * Display controls to compare 2 revisions
	 * @var bool
	 */
	public $displayCompare = TRUE;

	/**
	 * Production table name
	 * @var string
	 */
	private $productionTable;

	/**
	 * Revision table name
	 * @var
	 */
	private $revisionTable;

	/**
	 * Database column which is the primary key for the production table
	 * @var string
	 */
	private $primaryID;

	/**
	 * Database column which is the secondary key for the production table
	 * (will usually be a modified date)
	 * @var string
	 */
	private $secondID;

	/**
	 * @var EngineAPI
	 */

	private $engine;
	/**
	 * @var engineDB
	 */
	private $openDB;

	/**
	 * Class constructor
	 *
	 * @param string $productionTable
	 * @param string $revisionTable
	 * @param string $primaryID
	 * @param string $secondID
	 * @param engineDB $database
	 */
	function __construct($productionTable,$revisionTable,$primaryID,$secondID,$database=NULL) {
		$this->engine = EngineAPI::singleton();

		$this->openDB = $database instanceof engineDB
			? $database
			: $this->engine->openDB;

		$this->productionTable = $productionTable;
		$this->revisionTable   = $revisionTable;
		$this->primaryID 	   = $primaryID;
		$this->secondID 	   = $secondID;
		
	}
	
	/**
	 * Add new revision
	 *
	 * $primary: Primary key field name in $toTable
	 * $ID: value of $primary
	 *
	 * @todo Figure out what $ID and $ID2 are
	 * @param $ID
	 * @param $ID2
	 * @return bool
	 */
	public function insertRevision($ID,$ID2 = NULL) {
		// check to see if this revision is already in the table.
		// if so, don't try to insert again	
		if (isnull($ID2)) {
			$sql = sprintf("SELECT %s FROM %s WHERE %s='%s'",
				$this->openDB->escape($this->secondID),
				$this->openDB->escape($this->productionTable),
				$this->openDB->escape($this->primaryID),
				$this->openDB->escape($ID));
			$sqlResult = $this->openDB->query($sql);

			if (!$sqlResult['result']) {
				errorHandle::newError(__METHOD__."() - Error determing original secondary key", errorHandle::DEBUG);
				return(FALSE);
			}

			$row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
			$ID2 = $row[$this->secondID];

		}

		$sql = sprintf("SELECT COUNT(*) FROM %s WHERE %s='%s' AND %s='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->primaryID),
			$this->openDB->escape($ID),
			$this->openDB->escape($this->secondID),
			$this->openDB->escape($ID2)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error determing revision duplication", errorHandle::DEBUG);
			return(FALSE);
		}

		$row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

		if($row["COUNT(*)"] > 0) return(TRUE);
		/* ** End Count Check ** */

		// Do the insert
		$sql = sprintf("INSERT INTO %s (SELECT * FROM %s WHERE %s='%s' AND %s='%s' LIMIT 1)",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->productionTable),
			$this->openDB->escape($this->primaryID),
			$this->openDB->escape($ID),
			$this->openDB->escape($this->secondID),
			$this->openDB->escape($ID2)
		);

		$sqlResult = $this->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			errorHandle::newError("Error copying row to revisions table. sql:".$sql." sql Error = ".$sqlResult['error'], errorHandle::CRITICAL);
			return(FALSE);
		}
		
		return(TRUE);
	}

	/**
	 * Revert a revision
	 *
	 * @todo figure out params
	 * @param $ID
	 *        value of $primary
	 * @param $ID2
	 *        value of $secondary
	 * @return bool
	 */
	public function revert2Revision($ID,$ID2) {
		$sql = sprintf("REPLACE INTO %s (SELECT * FROM %s WHERE %s='%s' AND %s='%s' LIMIT 1)",
			$this->openDB->escape($this->productionTable),
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->primaryID),
			$this->openDB->escape($ID),
			$this->openDB->escape($this->secondID),
			$this->openDB->escape($ID2));
		$sqlResult = $this->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			errorHandle::newError("Error copying revision to production table. sql: ".$sql." sql Error = ".$sqlResult['error'], errorHandle::CRITICAL);
			return(FALSE);
		}
		
		return(TRUE);
		
	}

	/**
	 * Generate HMTL revision table
	 * @param $ID
	 *        value of the $primaryIDField. NOT SANITIZED, Expects clean value.
	 * @param $revisionDisplayFields
	 *        Array of display fields
	 *        Keys:
	 *          - field: field name in the actual table
	 *          - label: heading for that field in the display table
	 *          - translation: [optional] if present it must be either an array or a function
	 *            if an array, each index of the array must corrispond do a potential value.
	 *            if a function chat function must take an argument, which is the value of the field.
	 * @return bool|string
	 */
	public function generateRevistionTable($ID,$revisionDisplayFields) {
	
		$sql = sprintf("SELECT * FROM %s WHERE %s='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->primaryID),
			$ID
			);

		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError("Error retrieving revision information. sql: ".$sql." SQL ERROR: ".$sqlResult['error'], errorHandle::DEBUG);
			errorHandle::errorMsg("Error retrieving revision information.");
			return(FALSE);
		}
		
		if ($sqlResult['numRows'] == 0) {
			$error = TRUE;
			errorHandle::errorMsg("No Revisions found for this item.");
		}

		$revArray     = array();
		$tableHeaders = array();
		$firstItem    = TRUE;

		if ($this->displayRevert === TRUE) { 
			$tableHeaders[] = "Revert";
		}

		if ($this->displayCompare === TRUE) {
			$tableHeaders[] = "Compare 1";
			$tableHeaders[] = "Compare 2";
		}

		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) { // while 1

			$temp = array();

			if ($this->displayRevert === TRUE) {
				$temp["Revert"]    = '<input type="radio" name="revert"   value='.$row[$this->secondID].' />'; 
			}

			if ($this->displayCompare === TRUE) {
				$temp["Compare 1"] = '<input type="radio" name="compare1" value='.$row[$this->secondID].' />'; 
				$temp["Compare 2"] = '<input type="radio" name="compare2" value='.$row[$this->secondID].' />'; 
			}
			
			foreach ($revisionDisplayFields as $I=>$V) { // foreach 1
					
				if ($firstItem === TRUE) {
					$tableHeaders[] = $V['label'];
				}

				$value = $row[$V['field']];
				if (isset($V['translation'])) {
					if (is_array($V['translation'])) {
						if (isset($V['translation'][$value])) {
							$value = $V['translation'][$value];
						}
						} // is array
						else if (is_function($V['translation'])) {
							$value = $V['translation']($value);
						}
					}
					
					$temp[$V['label']] = $value;
			} // foreach 1
			$revArray[] = $temp;
			$firstItem  = FALSE;
		} // while 1
			
		$table = new tableObject("array");

		$table->summary = "Revisions Table";
		$table->sortable = FALSE;
		$table->headers($tableHeaders);

		return($table->display($revArray));

	}

	/**
	 * Process a submission from the revision table
	 *
	 * @param $ID
	 * @param $ID2
	 * @return bool
	 */
	public function revertSubmit($ID,$ID2) {
	
		// begin database transactions
		$result = $this->openDB->transBegin($this->openDB->escape($this->productionTable));
		if ($result !== TRUE) {
			errorHandle::errorMsg("Transaction could not begin.");
			return(FALSE);
		}
		
		// first we move the current production value into the modified table
		$prod2RevResult = $this->insertRevision($ID,$ID2);

		if ($prod2RevResult === FALSE) {
			errorHandle::newError("Error Copying row from production to revision tables", errorHandle::DEBUG);
			errorHandle::errorMsg("Error reverting to previous revision.");

			// roll back database transaction
			$this->openDB->transRollback();
			$this->openDB->transEnd();
			
			return(FALSE);
		}

		// second we move the selected modified value to the production table
		$rev2ProdResult = $this->revert2Revision($ID,$ID2);

		if ($rev2ProdResult === FALSE) {

			errorHandle::newError("Error Copying row from revision to production tables", errorHandle::DEBUG);
			errorHandle::errorMsg("Error reverting to previous revision.");

			// roll back database transactions
			$this->openDB->transRollback();
			$this->openDB->transEnd();
			
			return(FALSE);
		}

		// commit database transactions
		$this->openDB->transCommit();
		$this->openDB->transEnd();
		
		errorHandle::successMsg("Successfully reverted to revision.");
		
		return(TRUE);
	}

}

?>