<?php
/**
 * EngineAPI revisionControlSystem
 * @package EngineAPI\modules\revisionControlSystem
 */

require("simplediff.php");

/**
 * EngineAPI revisionControlSystem module
 * @package EngineAPI\modules\revisionControlSystem
 */
class revisionControlSystem {

	public  $digitalObjectsFieldName = "digitalObjects";

	// Variables to configure the Revision Table
	/**
	 * Display the revert buttons (otherwise its a "view only" table)
	 * @var bool
	 */
	public $displayRevert = TRUE;
	/**
	 * display the radio buttons for comparing items
	 * @var bool
	 */
	public  $displayCompare = TRUE;

	private $productionTable = NULL;
	private $revisionTable   = NULL;
	/**
	 * Key in production table
	 * @var string
	 */
	private $primaryID = NULL;
	/**
	 * secondary key in revision table, will usually be a modified date
	 * Note: secondary key must exist in both the primary and secondary tables
	 * @var string
	 */
	private $secondaryID = NULL;

	/**
	 * @var engineDB
	 */
	private $openDB          = NULL;
	private $excludeFields   = array();
	private $relatedMappings = array();

	/**
	 * Class constructor
	 *
	 * @param $productionTable
	 *        Table where production data is being stored
	 * @param $revisionTable
	 *        Table where revision information is being stored
	 * @param $primaryID
	 *        Field name of the primary key in the production table
	 * @param $secondaryID
	 *        Field name of the secondary key in the revision table
	 * @param engineDB $database
	 *        engineAPI database object to use instead of $this->openDB
	 */
	function __construct($productionTable,$revisionTable,$primaryID,$secondaryID,$database=NULL) {

		$engine = EngineAPI::singleton();

		if (!is_null($database) && $database instanceof engineDB) {
			$this->openDB = $database;
		}
		else {
			$this->openDB = $engine->openDB;
		}

		$this->revisionTable   = $revisionTable;
		$this->productionTable = $productionTable;
		$this->primaryID 	   = $primaryID;
		$this->secondaryID 	   = $secondaryID;

	}

	function __destruct() {

	}

	/**
	 * Add a new revision to the database
	 *
	 * @param $primaryIDValue
	 *        The primary ID of the item that we will be inserting into the revision table
	 * @return bool
	 */
	public function insertRevision($primaryIDValue) {

		/* ** Begin Insert Check ** */

		// check to see if the current version of the item is already in the revision table
		// Determined by checking the modified date. It is assumed that the developer is checking,
		// on the application level, if the data should have been inserted or not (that is, if the
		// update button was clicked abut nothing was changed, the modified date wasn't updated)

		// Get the value of the secondary key
		$sql       = sprintf("SELECT %s FROM %s WHERE %s='%s'",
			$this->openDB->escape($this->secondaryID),
			$this->openDB->escape($this->productionTable),
			$this->openDB->escape($this->primaryID),
			$this->openDB->escape($primaryIDValue)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error determing original secondary key value", errorHandle::DEBUG);
			return(FALSE);
		}

		$row              = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		$secondaryIDValue = $row[$this->secondaryID];

		// Check to see if the secondary / primary key pair exists in the revision table already
		$sql = sprintf("SELECT COUNT(*) FROM %s WHERE `primaryID`='%s' AND `secondaryID`='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($primaryIDValue),
			$this->openDB->escape($secondaryIDValue)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error determing revision duplication. ".$sqlResult['error'], errorHandle::DEBUG);
			return(FALSE);
		}

		$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

		// they key already exists, so we return TRUE because nothing needs done
		if ($row["COUNT(*)"] > 0) {
			return(TRUE);
		}

		/* ** End Insert Check ** */

		// Get all data from the primary table
		$sql = sprintf("SELECT * FROM %s WHERE %s='%s' AND %s='%s'",
			$this->openDB->escape($this->productionTable),
			$this->openDB->escape($this->primaryID),
			$this->openDB->escape($primaryIDValue),
			$this->openDB->escape($this->secondaryID), // Selecting on secondary ID as well, to be sure
			$this->openDB->escape($secondaryIDValue)   // that it hasn't been updated since requesting revision control
			);

		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() -Error getting data from primary table, sql Error = ".$sqlResult['error'], errorHandle::CRITICAL);
			return(FALSE);
		}

		$row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

		// We don't need the primary and secondary fields in the array
		unset($row[$this->primaryID]);
		unset($row[$this->secondaryID]);

		if (isset($row[$this->digitalObjectsFieldName])) {

			$digitalObjectArray = $row[$this->digitalObjectsFieldName];
			unset($row[$this->digitalObjectsFieldName]);

		}
		else {
			$digitalObjectArray = "";
		}

		// If there are any fields that the developer has specifically 
		// excluded from being managed in revision control, we skip them here. 
		foreach ($this->excludeFields as $I=>$V) {
			unset($row[$V]);
		}

		$metaDataArray = base64_encode(serialize($row));

		$relatedDataArray = array();
		if (count($this->relatedMappings) > 0) {
			// Get the related data from other tables
			foreach ($this->relatedMappings as $I=>$V) {
				$sql       = sprintf("SELECT * FROM %s WHERE `%s`='%s'",
					$this->openDB->escape($V['table']),
					$this->openDB->escape($V['primaryKey']),
					$this->openDB->escape($primaryIDValue)
					);
				$sqlResult = $this->openDB->query($sql);

				if (!$sqlResult['result']) {
					errorHandle::newError(__METHOD__."() - error getting related data.", errorHandle::DEBUG);
					return(FALSE);
				}

				$temp = array();
				while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
					$temp[] = $row;
				}

				$relatedDataArray[$V['table']] = $temp;
			}
			$relatedDataArray = base64_encode(serialize($relatedDataArray));
		}
		else {
			$relatedDataArray = "";
		}

		// find duplicates
		// The Revision Control system will automatically find duplicate data in each of the 3 arrays
		// and link to the last occurrence of the data. 
		// Note: We don't care where the data comes from, if its the same its the same. 

		// Find for metaDataArray
		$sql       = sprintf("SELECT ID FROM %s WHERE metaData='%s' LIMIT 1",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($metaDataArray)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error finding duplicates for Metadata Array", errorHandle::DEBUG);
			return(FALSE);
		}

		$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

		if (isset($row['ID']) && !isempty($row['ID'])) {
			$metaDataArray = $row['ID'];
		}

		// Find for digitalObjectArray
		if (!isempty($digitalObjectArray)) {
			$sql       = sprintf("SELECT ID FROM %s WHERE digitalObjects='%s' LIMIT 1",
				$this->openDB->escape($this->revisionTable),
				$this->openDB->escape($digitalObjectArray)
				);
			$sqlResult = $this->openDB->query($sql);

			if (!$sqlResult['result']) {
				errorHandle::newError(__METHOD__."() - Error finding duplicates for Data Object Array", errorHandle::DEBUG);
				return(FALSE);
			}

			$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

			if (isset($row['ID']) && !isempty($row['ID'])) {
				$digitalObjectArray = $row['ID'];
			}

		}

		// Find for relatedDataArray
		if (!isempty($relatedDataArray)) {
			$sql       = sprintf("SELECT ID FROM %s WHERE relatedData='%s' LIMIT 1",
				$this->openDB->escape($this->revisionTable),
				$this->openDB->escape($relatedDataArray)
				);
			$sqlResult = $this->openDB->query($sql);

			if (!$sqlResult['result']) {
				errorHandle::newError(__METHOD__."() - Error finding duplicates for Related Data Array", errorHandle::DEBUG);
				return(FALSE);
			}

			$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

			if (isset($row['ID']) && !isempty($row['ID'])) {
				$relatedDataArray = $row['ID'];
			}
		}

		$sql = sprintf("INSERT INTO %s (productionTable,primaryID,secondaryID,metaData,digitalObjects,relatedData) VALUES('%s','%s','%s','%s','%s','%s')",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->productionTable),
			$this->openDB->escape($primaryIDValue),
			$this->openDB->escape($secondaryIDValue),
			$this->openDB->escape($metaDataArray),
			$this->openDB->escape($digitalObjectArray),
			$this->openDB->escape($relatedDataArray)
			);

		$sqlResult  = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error Inserting revision into revision table. ".$sqlResult['error'], errorHandle::DEBUG);
			return(FALSE);
		}

		return(TRUE);
	}

	/**
	 * Revert to a past revision
	 *
	 * @param $primaryIDValue
	 * @param $secondaryIDValue
	 * @return bool
	 */
	public function revert2Revision($primaryIDValue,$secondaryIDValue) {

		//Begin database Transactions
		$result = $this->openDB->transBegin($this->openDB->escape($this->productionTable));
		if ($result !== TRUE) {
			errorHandle::errorMsg("Database transactions could not begin.");
			errorHandle::newError(__METHOD__."() - unable to start database transactions", errorHandle::DEBUG);
			return(FALSE);
		}

		// Move the current production value into the modified table
		$prod2RevResult = $this->insertRevision($primaryIDValue,$secondaryIDValue);

		if ($prod2RevResult === FALSE) {
			errorHandle::newError("Error Copying row from production to revision tables", errorHandle::DEBUG);
			errorHandle::errorMsg("Error reverting to previous revision.");

			// roll back database transaction
			$this->openDB->transRollback();
			$this->openDB->transEnd();

			return(FALSE);
		}

		$sql       = sprintf("SELECT * FROM `%s` WHERE `productionTable`='%s' AND `primaryID`='%s' AND `secondaryID`='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->productionTable),
			$this->openDB->escape($primaryIDValue),
			$this->openDB->escape($secondaryIDValue)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error retrieving revision from table.", errorHandle::DEBUG);

			// roll back database transaction
			$this->openDB->transRollback();
			$this->openDB->transEnd();

			return(FALSE);
		}
		else if ($sqlResult['numrows'] < 1) {
			errorHandle::newError(__METHOD__."() - Requested Revision not found in system", errorHandle::DEBUG);

			// roll back database transaction
			$this->openDB->transRollback();
			$this->openDB->transEnd();

			return(FALSE);
		}

		$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

		// $row['metadata']        = unserialize(base64_decode($row['metadata']));
		$row['metadata']    = $this->getMetadataForID($row['ID']);
		$row['relatedData'] = $this->getMetadataForID($row['ID'],"relatedData");

		// Retrieve digital object if it is a link
		if (validate::integer($row['digitalObjects'])) {
			$sql       = sprintf("SELECT `digitalObjects` FROM %s WHERE `ID`='%s'",
				$this->openDB->escape($this->revisionTable),
				$this->openDB->escape($row['digitalObjects'])
				);

			$sqlResult = $this->openDB->query($sql);

			if (!$sqlResult['result']) {
				errorHandle::newError(__METHOD__."() - ", errorHandle::DEBUG);

				// roll back database transaction
				$this->openDB->transRollback();
				$this->openDB->transEnd();

				return(FALSE);
			}

			$row2               = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
			$row['digitalObjects'] = $row2['digitalObjects'];

		}

		$setString = array();
		foreach ($row['metadata'] as $I=>$V) {
			$setString[] = sprintf("%s='%s'",
				$this->openDB->escape($I),
				$this->openDB->escape($V)
				);
		}
		$setString = implode(",",$setString);

		if (!isempty($row['digitalObjects'])) {
			$setString .= sprintf(",`digitalObjects`='%s'",
				$this->openDB->escape($row['digitalObjects'])
				);
		}

		// Add the primary and secondary fields back in
		$setString .= sprintf(",`%s`='%s',`%s`='%s'",
			$this->openDB->escape($this->primaryID),
			$this->openDB->escape($primaryIDValue),
			$this->openDB->escape($this->secondaryID),
			$this->openDB->escape($secondaryIDValue)
			);

		// Restore into production table
		$sql       = sprintf("REPLACE INTO %s SET %s",
			$this->openDB->escape($this->productionTable),
			$setString
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Restoring.". $sqlResult['error']."SQL: ".$sql, errorHandle::DEBUG);

			// roll back database transaction
			$this->openDB->transRollback();
			$this->openDB->transEnd();

			return(FALSE);
		}

		// Restore Related Data
		foreach ($row['relatedData'] as $table=>$rows) {

			// Delete the current set of data in the related table
			$sql       = sprintf("DELETE FROM %s WHERE `%s`='%s'",
				$this->openDB->escape($table),
				$this->openDB->escape($this->relatedMappings[$table]['primaryKey']),
				$this->openDB->escape($primaryIDValue)
				);
			$sqlResult = $this->openDB->query($sql);

			if (!$sqlResult['result']) {
				errorHandle::newError(__METHOD__."() - Error deleting from related data table, ".$table.", with error: ".$sqlResult['error'], errorHandle::DEBUG);

				// roll back database transaction
				$this->openDB->transRollback();
				$this->openDB->transEnd();

				return(FALSE);
			}

			foreach ($rows as $I=>$row) {

				$temp = array();

				foreach ($row as $field=>$value) {
					$temp[] = sprintf("`%s`='%s'",
						$this->openDB->escape($field),
						$this->openDB->escape($value)
						);
				}

				$temp = implode(",",$temp);

				$sql       = sprintf("INSERT INTO `%s` SET %s",
					$this->openDB->escape($table),
					$temp
					);
				$sqlResult = $this->openDB->query($sql);

				if (!$sqlResult['result']) {
					errorHandle::newError(__METHOD__."() - Restoring related Data to table, ".$table.", with error: ".$sqlResult['error'], errorHandle::DEBUG);
					return(FALSE);
				}

			}

		}

		// commit database transactions
		$this->openDB->transCommit();
		$this->openDB->transEnd();

		errorHandle::successMsg("Successfully reverted to revision.");

		return(TRUE);

	}

	/**
	 * Generate HTML revision table
	 *
     * ###Display Fields:
     * - field: Field is the field name in the actual table.
     * - label: the heading for that field in the display table.
     * - translation: if present it must be either and array or a function.
     *   - if an array, each index of the array must corrispond do a potential value.
     *   - if a function that function must take an argument, which is the value of the field.
     *
	 * @param $primaryIDValue
	 *        Value of the $primaryIDField. NOT SANITIZED, Expects clean value.
	 * @param $displayFields
	 *        An array that contains information about each field to be displayed in the revision table.
	 * @return bool|string
	 */
	public function generateRevisionTable($primaryIDValue,$displayFields) {

		$sql = sprintf("SELECT * FROM `%s` WHERE `productionTable`='%s' AND `primaryID`='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->productionTable),
			$primaryIDValue
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

		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {

			$metadata = $this->getMetadataForID($row['ID']);

			$temp     = array();

			if ($this->displayRevert === TRUE) {
				$temp["Revert"]    = '<input type="radio" name="revert"   value='.$row['secondaryID'].' />';
			}

			if ($this->displayCompare === TRUE) {
				$temp["Compare 1"] = '<input type="radio" name="compare1" value='.$row['secondaryID'].' />';
				$temp["Compare 2"] = '<input type="radio" name="compare2" value='.$row['secondaryID'].' />';
			}

			foreach ($displayFields as $I=>$V) { // foreach 1

				if ($firstItem === TRUE) {
					$tableHeaders[] = $V['label'];
				}

				if (isset($metadata[$V['field']])) {
					$value = $metadata[$V['field']];
				}
				else if (isset($row[$V['field']])) {
					$value = $row[$V['field']];
				}
				else {
					$value ="";
				}

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
	 * Display the comparison of the 2 provided revision IDs
	 *
     * ###Fields Array:
     * Array of how to compare the fields. If nothing is provided, just displays the fields side by side with default diff tool<br>
     * Note: custom diff function is NOT run through htmlSanitize before display, return from function should be sanitized
     *
     * - $fields['metadata']['fieldName']['display'] = create_function(); // used to display data (takes 1 arg)
     * - $fields['metadata']['fieldName']['diff']    = create_function(); // used to perform diff (takes 2 args)
     * - $fields['relatedData']['fieldName']         = create_function(); // used to translate value (takes 1 arg)
     * - $fields['digitalObjects']                   = create_function(); // used to display the digital object (or provide links), takes 1 arg
     *
	 * @param string $primaryIDValue_1
	 *        Primary id of first item to compare
	 * @param string $secondaryIDValue_1
	 *        Secondary id of first item to compare
	 * @param string $primaryIDValue_2
	 *        Primary id of second item to compare
	 * @param string $secondaryIDValue_2
	 *        Secondary id of second item to compare
	 * @param array $fields
	 *        Array of how to compare the fields. *See section above*
	 * @return bool|string
	 */
	public function compare($primaryIDValue_1, $secondaryIDValue_1, $primaryIDValue_2, $secondaryIDValue_2, $fields=NULL) {

		// Get the first item
		$sql = sprintf("SELECT * FROM `%s` WHERE `productionTable`='%s' AND `primaryID`='%s' AND `secondaryID`='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->productionTable),
			$primaryIDValue_1,
			$secondaryIDValue_1
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error retrieving first item", errorHandle::DEBUG);
			return(FALSE);
		}

		$row_1                   = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		$row_1['metadata']       = $this->getMetadataForID($row_1['ID']);
		$row_1['relatedData']    = $this->getMetadataForID($row_1['ID'],"relatedData");
		$row_1['digitalObjects'] = $this->getMetadataForID($row_1['ID'],"digitalObjects",FALSE);

		// Get the second item
		$sql = sprintf("SELECT * FROM `%s` WHERE `productionTable`='%s' AND `primaryID`='%s' AND `secondaryID`='%s'",
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($this->productionTable),
			$primaryIDValue_1,
			$secondaryIDValue_2
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Error retrieving second item", errorHandle::DEBUG);
			return(FALSE);
		}

		$row_2                   = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		$row_2['metadata']       = $this->getMetadataForID($row_2['ID']);
		$row_2['relatedData']    = $this->getMetadataForID($row_2['ID'],"relatedData");
		$row_2['digitalObjects'] = $this->getMetadataForID($row_2['ID'],"digitalObjects",FALSE);

		$output  = sprintf('<table class="engineRCSCompareTable" id="engineRCSCompareTable_%s">',
			htmlSanitize($this->productionTable)
			);

		$output .= '<tr>';
		$output .= '<th>Field Name</th>';
		$output .= sprintf('<th>%s</th>',date("m/d/Y H:i:s",$row_1['secondaryID']));
		$output .= sprintf('<th>%s</th>',date("m/d/Y H:i:s",$row_2['secondaryID']));
		$output .= '</tr>';

		foreach ($row_1['metadata'] as $I=>$V) {

			if (isset($fields['metadata'][$I]['display'])) {
				$convertedResult_1 = $fields['metadata'][$I]['display']($row_1['metadata'][$I]);
				$convertedResult_2 = $fields['metadata'][$I]['display']($row_2['metadata'][$I]);
			}
			else {
				$convertedResult_1 = htmlSanitize($row_1['metadata'][$I]);
				$convertedResult_2 = htmlSanitize($row_2['metadata'][$I]);
			}

			if (isset($fields['metadata'][$I]['diff'])) {
				$diff = $fields['metadata'][$I]['diff']($row_1['metadata'][$I],$row_2['metadata'][$I]);
			}
			else {
				$diff = htmlDiff(htmlSanitize($row_1['metadata'][$I]),htmlSanitize($row_2['metadata'][$I]));
			}

			$output .= '<tr>';
			$output .= sprintf('<td rowspan="2" class="fieldName">%s</td>',$I);
			$output .= sprintf('<td><code>%s</code></td>',$convertedResult_1);
			$output .= sprintf('<td><code>%s</code></td>',$convertedResult_2);
			$output .= '</tr>';

			$output .= '<tr>';
			$output .= sprintf('<td colspan="2"><code>%s</code></td>',$diff);
			$output .= '</tr>';

		}

		if (!isempty($row_1['relatedData'])) {
			$relatedData_1 = '<ul class="engineRCSCompareTable_UL_1">';
			foreach ($row_1['relatedData'] as $tableName=>$V) {
				$relatedData_1 .= sprintf('Table: <strong>%s</strong><ul>',htmlSanitize($tableName));

				foreach ($V as $I=>$fieldRow) {
					$relatedData_1 .= "<li>Row: <ul>";
					foreach ($fieldRow as $fieldName=>$fieldValue) {
						if (isset($fields['relatedData'][$fieldName])) {
							$convertedResult = $fields['relatedData'][$fieldName]($row_1['relatedData'][$tableName][$I][$fieldName]);
						}
						else {
							$convertedResult = $row_1['relatedData'][$tableName][$I][$fieldName];
						}
						$relatedData_1 .= sprintf("<li>%s : %s</li>",htmlSanitize($fieldName),htmlSanitize($convertedResult));
					}
					$relatedData_1 .= "</ul></li>";
				}

			}
			$relatedData_1 .= '</ul>';
		}

		if (!isempty($row_2['relatedData'])) {
			$relatedData_2 = '<ul class="engineRCSCompareTable_UL_2">';
			foreach ($row_2['relatedData'] as $tableName=>$V) {
				$relatedData_2 .= sprintf('Table: <strong>%s</strong><ul>',htmlSanitize($tableName));

				foreach ($V as $I=>$fieldRow) {
					$relatedData_2 .= "<li>Row: <ul>";
					foreach ($fieldRow as $fieldName=>$fieldValue) {
						if (isset($fields['relatedData'][$fieldName])) {
							$convertedResult = $fields['relatedData'][$fieldName]($row_2['relatedData'][$tableName][$I][$fieldName]);
						}
						else {
							$convertedResult = $row_1['relatedData'][$tableName][$I][$fieldName];
						}
						$relatedData_2 .= sprintf("<li>%s : %s</li>",htmlSanitize($fieldName),htmlSanitize($convertedResult));
					}
					$relatedData_2 .= "</ul></li>";
				}

			}
			$relatedData_2 .= '</ul>';
		}

		if (!isempty($relatedData_1) || !isempty($relatedData_2)) {
			$output .= '<tr>';
			$output .= '<td class="fieldName">Related Data</td>';
			$output .= sprintf('<td>%s</td>',$relatedData_1);
			$output .= sprintf('<td>%s</td>',$relatedData_2);
			$output .= '</tr>';
		}

		if (!isempty($row_1['digitalObjects']) || !isempty($row_2['digitalObjects'])) {

			if (isset($fields['digitalObjects'])) {
				$digitalObjects_1 = $fields['digitalObjects']($row_1['digitalObjects']);
				$digitalObjects_2 = $fields['digitalObjects']($row_2['digitalObjects']);
			}
			else {
				$digitalObjects_1 = $row_1['digitalObjects'];
				$digitalObjects_2 = $row_2['digitalObjects'];
			}

			$output .= '<tr>';
			$output .= '<td class="fieldName">Digital Objects</td>';
			$output .= sprintf('<td>%s</td>',$digitalObjects_1);
			$output .= sprintf('<td>%s</td>',$digitalObjects_2);
			$output .= '</tr>';
		}

		$output .= '</table>';

		return($output);
	}

	/**
	 * Add a field to be excluded from revision control
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public function addExcludedField($fieldName) {
		$this->excludeFields[] = $fieldName;
		return(TRUE);
	}

	/**
	 * Remove a field from the excluded list
	 *
	 * @param $fieldName
	 * @return bool
	 */
	public function removeExcludedField($fieldName) {
		if (isset($this->excludeFields[$fieldName])) {
			unset($this->excludeFields[$fieldName]);
			return(TRUE);
		}
		return(FALSE);
	}

	/**
	 * Clear list of excluded fields
	 *
	 * @return bool
	 */
	public function clearExcludedFields() {
		unset($this->excludeFields);
		$this->excludeFields = array();
		return(TRUE);
	}

	/**
	 * Related data allows revision control to keep track of revisions in other tables.
	 *
	 * @param $table
	 *        The table where the related data is stored
	 * @param $primaryKey
	 *        This is where the primary key of the main object (as passed into insertRevision)
	 * @return bool
	 */
	public function addRelatedDataMapping($table,$primaryKey) {

		// does the table exist?
		$sql       = sprintf("select 1 from `%s`",
			$this->openDB->escape($table)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - Invalid table", errorHandle::DEBUG);
			return(FALSE);
		}

		$this->relatedMappings[$table] = array(
			"table" 	 => $table,
			"primaryKey" => $primaryKey
			);

		return(TRUE);

	}

	/**
	 * Renames production tables in the revision table in the event that the name of a production
	 * WARNING: This method is not yet implemented!
	 *
	 * @todo finish the method
	 * @param $oldTableName
	 * @param $newTableName
	 */
	public function updateTableName($oldTableName,$newTableName) {

	}

	/**
	 * @todo finish the method
	 * @param $table
	 * @param $command
	 * @param $search
	 * @param $replace
	 */
	public function updateRevisionTableStructure($table,$command,$search,$replace) {

	}

	/**
	 * Retrieved metadata for given revision
	 * @param $revisionID
	 * @param string $type
	 * @param bool $decode
	 * @return bool|mixed|string
	 *         Returns empty string if nothing is found
	 */
	private function getMetadataForID($revisionID,$type="metadata",$decode=TRUE) {

		if (!validate::integer($revisionID)) {
			errorHandle::newError(__METHOD__."() - invalid ID passed for revisionID", errorHandle::DEBUG);
			return(FALSE);
		}

		$sql       = sprintf("SELECT `%s` FROM `%s` WHERE `ID`='%s'",
			$this->openDB->escape($type),
			$this->openDB->escape($this->revisionTable),
			$this->openDB->escape($revisionID)
			);
		$sqlResult = $this->openDB->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::newError(__METHOD__."() - retrieving row ".$type, errorHandle::DEBUG);
			return(FALSE);
		}

		$row       = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);

		// Retrieve metaData if it is a link
		if (validate::integer($row[$type])) {
			$sql       = sprintf("SELECT `%s` FROM %s WHERE `ID`='%s'",
				$this->openDB->escape($type),
				$this->openDB->escape($this->revisionTable),
				$this->openDB->escape($row[$type])
				);

			$sqlResult = $this->openDB->query($sql);

			if (!$sqlResult['result']) {
				errorHandle::newError(__METHOD__."() - retrieving linked ".$type, errorHandle::DEBUG);

				// roll back database transaction
				$this->openDB->transRollback();
				$this->openDB->transEnd();

				return(FALSE);
			}

			$row2             = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
			$row[$type]  = $row2[$type];

		}

		if (isempty($row[$type])) {
			return("");
		}

		if ($decode === FALSE) {
			return($row[$type]);
		}

		return(unserialize(base64_decode($row[$type])));

	}

}

?>