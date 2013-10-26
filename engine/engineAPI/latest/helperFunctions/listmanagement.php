<?php
/**
 * Helper functions for listManagement
 * @package EngineAPI\modules\listManagement
 * @todo Review these functions - Are they needed, can be cleaned up, etc
 */

/**
 * Generate HTML add form
 *
 * @todo Figure out attrPairs
 * @param $attPairs
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listAdd($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
		
	$queryString = "";
	if (!empty($attPairs['addget']) && $attPairs['addget'] == "true") {
		foreach ($_GET['HTML'] as $var=>$val) {
			$queryString .= ((!empty($queryString))?"&amp;":"?").$var."=".$val;
		}
	}
		
	$output = "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\">";
	$output .= sessionInsertCSRF();
	$output .= "<input type=\"hidden\" name=\"formTable\" value=\"".$engine->openDB->escape($attPairs['table'])."\" />";
	$output .= "<label for=\"newListItem\">New ".$attPairs['label'].":</label> &nbsp;&nbsp;";
	$output .= "<input type=\"text\" size=\"25\" name=\"newListItem\" id=\"newListItem\" value=\"\" />";
	$output .= "<br /><br />";
	$output .= "<input type=\"submit\" value=\"Add ".$attPairs['label']."\" name=\"newSubmit\" />";
	$output .= "</form>";
	
	return($output);
	
}

/**
 * Generate HTML insert form
 * @param $table
 * @param $label
 * @param EngineAPI $engine NOT USED
 * @param bool $emailCheck
 * @return string
 */
function webHelper_listInsert($table,$label,$engine,$emailCheck=FALSE) {
	
	$engine = EngineAPI::singleton();
		
	if (empty($_POST['MYSQL']['newListItem'])) {
		return webHelper_errorMsg($label ." was left blank.");
	}
	else {

		if ($emailCheck === TRUE) {
			if(!validateEmailAddr($_POST['MYSQL']['newListItem'])) {
				return webHelper_errorMsg("Invalid E-Mail Address: ". $_POST['MYSQL']['newListItem']);
			}
		}
		
		// Check for duplicates
		if (webHelper_listDupeCheck($_POST['MYSQL']['newListItem'],$table,$engine)) {
			return webHelper_errorMsg("Entry, ".$_POST['HTML']['newListItem'].", already in database.");
		}
				
		$sql = sprintf("INSERT INTO %s (name) VALUES('%s')",
			$engine->openDB->escape($table),
			$_POST['MYSQL']['newListItem']
			);

		$engine->openDB->sanitize = FALSE;
		$sqlResult = $engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("SQL Error:".$sqlResult['error']);
		}
		else {
			return webHelper_successMsg("Entry, ".$_POST['HTML']['newListItem'].", successfully added to the database.");
		}
		
	}
	
	return webHelper_errorMsg("Something went horribly wrong. listManagement.pl -- webHelper_listInsert()");
}

/**
 * Generate HTML edit form
 * @param $attPairs
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listEditList($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$dbTables = $engine->dbTablesExport();
		
	$queryString = "";
	if (!empty($attPairs['addget']) && $attPairs['addget'] == "true") {
		foreach ($_GET['HTML'] as $var=>$val) {
			$queryString .= ((!empty($queryString))?"&amp;":"?").$var."=".$val;
		}
	}
	
	$sql = sprintf("SELECT * FROM %s ORDER BY name",
		$engine->openDB->escape($attPairs['table'])
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	if (!$sqlResult['result']) {
		return webHelper_errorMsg($attPairs['table']."<br />SQL Error: ".$sqlResult['error']);
	}
	
	$output  = "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\">";
	$output .= sessionInsertCSRF();
	$output .= "<table border=\"0\" cellpadding=\"1\" cellspacing=\"0\" >";
	$output .= "<tr style=\"background-color: #EEEEFF;\">";
	$output .= "<th style=\"width: 300px; text-align: left;\">Title</th>";
	$output .= "<th style=\"width: 100px;\">Delete</th>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td colspan=\"3\" style=\"background-color: #000000;\"></td>";
	$output .= "</tr>";
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM)) {
		$output .= "<tr>";
		$output .= "<td style=\"padding-top: 3px;padding-bottom: 3px;\">";
		$output .= "<input type=\"text\" size=\"40\" name=\"title_".$row[0]."\" value=\"".htmlentities($row[1])."\" />";
		$output .= "</td>";
		$output .= "<td style=\"text-align: center;padding-top: 3px;padding-bottom: 3px;\">";
		$output .= "<input type=\"checkbox\" name=\"delete[]\" value=\"".$row[0]."\" />";
		$output .= "</td>";
		$output .= "</tr>";
	    
	}
	$output .= "</table>";
	$output .= "<input type=\"submit\" value=\"Update\" name=\"updateSubmit\" />";
	$output .= "</form>";
	
	return($output); 
}

/**
 * Generate HTML update form
 * @param $table
 * @param EngineAPI $engine NOT USED
 * @param bool $emailCheck
 * @return string
 */
function webHelper_listUpdate($table,$engine,$emailCheck=FALSE) {
	
	$engine = EngineAPI::singleton();
	
	$dbTables = $engine->dbTablesExport();
	
	$output = "";
	
	$sqlTable = NULL;
	if (isset($_GET['MYSQL']['type'])) {
		$sqlTable = $dbTables[$_GET['MYSQL']['type']]["prod"];
	}
	else if (isset($table)) {
		$sqlTable = $engine->openDB->escape($dbTables[$table]["prod"]);
	}
	
	if ($sqlTable == NULL) {
		$output .= webHelper_errorMsg("SQL Error: No table defined");
		return($output);
	}
	
	if (isset($_POST['MYSQL']['delete'])) {
		foreach($_POST['MYSQL']['delete'] as $value) {

			$sql = sprintf("DELETE FROM %s WHERE ID=%s",
				$sqlTable,
				$engine->openDB->escape($value)
				);

			$engine->openDB->sanitize = FALSE;
			$sqlResult = $engine->openDB->query($sql);

			if (!$sqlResult['result']) {
				$output .= webHelper_errorMsg("SQL Error".$sqlResult['error']);
			}
			
		}
	}
	
	$sql = sprintf("SELECT * FROM %s",
		$sqlTable
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	if (!$sqlResult['result']) {
		$output .= webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM)) {
		if (isset($_POST['MYSQL']['title_'.$row[0]])) {
			$temp = $_POST['MYSQL']['title_'.$row[0]];
		}
		else {
			continue;
		}

		if ($emailCheck === TRUE) {
			if(!validateEmailAddr($_POST['MYSQL']['title_'.$row[0]])) {
				$output .= webHelper_errorMsg("Invalid E-Mail Address: ". $_POST['MYSQL']['title_'.$row[0]]);
				continue;
			}
		}

		if (empty($temp) || $row[1] == $temp) {
			continue;
		}

		// Check for duplicates
		if (webHelper_listDupeCheck($temp,$table,$engine)) {
			$output .= webHelper_errorMsg("Entry, ".$temp.", already in database. Other records may be updated still.");
			continue;
		}

		$sql = sprintf("UPDATE %s SET name='%s' WHERE ID='%s'",
			$engine->openDB->escape($sqlTable),
			$temp,
			$row[0]
			);

		$engine->openDB->sanitize = FALSE;
		$sqlResult2 = $engine->openDB->query($sql);

		if (!$sqlResult2['result']) {
			$output .= webHelper_errorMsg("SQL Error".$sqlResult2['error']);
		}
		
	}

	if(empty($output)) {
		$output = webHelper_successMsg("Database successfully updated.");
	}
	
	return($output);
}


/**
 * Duplicate check helper for list object helpers?
 * @param $new
 * @param $table
 * @param EngineAPI $engine NOT USED
 * @return bool
 */
function webHelper_listDupeCheck($new,$table,$engine) {
	
	$engine = EngineAPI::singleton();
	
	$sql = sprintf("SELECT * FROM %s WHERE name='%s'",
		$engine->openDB->escape($table),
		$engine->openDB->escape($new)
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	//We should probably do a SQL check here
	
	if (mysql_num_rows($sqlResult['result']) == 0) {
		return(FALSE);
	}
	
	return(TRUE);
}

?>