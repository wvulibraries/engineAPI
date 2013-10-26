<?php
/**
 * Helper functions for listManagement
 * @package EngineAPI\modules\listManagement
 * @todo Review these functions - Are they needed, can be cleaned up, etc
 */

/**
 * Generate HTML multi-select box form (Add)
 *
 * @todo Figure out attrPairs
 * @param $attPairs
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMultiAdd($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$queryString = "";
	if (!empty($attPairs['addget']) && $attPairs['addget'] == "true") {
		foreach ($_GET['HTML'] as $var=>$val) {
			$queryString .= ((!empty($queryString))?"&amp;":"?").$var."=".$val;
		}
	}
	
	$cols = $attPairs['cols'];
	
	$output = "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\">";
	$output .= sessionInsertCSRF();
	$output .= "<table>";
	for($I=1;$I<=(int)$cols;$I++) {
		$table = $attPairs['col'.$I];
		$label = $attPairs['col'.$I.'label'];
		
		$output .= "<tr><td>";
		$output .= "<label for=\"$table\">New $label:</label> &nbsp;&nbsp;";
		$output .= "</td><td>";
		$output .= "<input type=\"text\" size=\"25\" name=\"$table\" id=\"$table\" value=\"\" />";
		$output .= "</td></tr>";
		
	}
	$output .= "</table>";
	
	$output .= "<br />";
	$output .= "<input type=\"submit\" value=\"Add ".$attPairs['label']."\" name=\"newSubmit\" />";
	$output .= "</form>";
	
	return($output);
	
}

/**
 * Generate HTML multi-select box form (Insert)
 *
 * @param $table
 * @param $label NOT USED
 * @param $cols
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMultiInsert($table,$label,$cols,$engine=null) {
	
	$engine = EngineAPI::singleton();
		
	if (empty($_POST['MYSQL'][$cols[1]["table"]])) {
		return webHelper_errorMsg($cols[1]["label"] ." was left blank.");
	}
	else {
		
		//Do the Email Check
		foreach ($cols as $I) {
			if (isset($I["email"]) && $I["email"] === TRUE) {
				if(!empty($_POST['MYSQL'][$I["table"]]) && !validateEmailAddr($_POST['MYSQL'][$I["table"]])) {
					return webHelper_errorMsg("Invalid E-Mail Address: ". $_POST['MYSQL'][$I["table"]]);
				}
			}
		}
		
		// Check for duplicates
		// Dupe checks on col1 
		if (webHelper_listMultiDupeCheck($_POST['MYSQL'][$cols[1]['table']],$table,$cols[1]['table'],$engine)) {
			return webHelper_errorMsg("Entry, ".$_POST['HTML'][$cols[1]["table"]].", already in database.");
		}
				
		$sql = sprintf("INSERT INTO %s (%s) VALUES(%s)",
			$engine->openDB->escape($table),
			webHelper_listMultiColInsert($cols,$engine),
			webHelper_listMultiValInsert($cols,$engine)
			
			);

		$engine->openDB->sanitize = FALSE;
		$sqlResult = $engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("SQL Error:".$sqlResult['error']);
		}
		else {
			return webHelper_successMsg("Entry, ".$_POST['HTML'][$cols[1]["table"]].", successfully added to the database.");
		}
		
	}
	
	return webHelper_errorMsg("Something went horribly wrong. listManagement.pl -- webHelper_listInsert()");
}

/**
 * Generate HTML multi-select col form (Insert)
 * @param $cols
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMultiColInsert($cols,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$temp = array();
	foreach ($cols as $I) {
		$temp[] = $I["table"];
	}
	$output = implode(",",$temp);
	return($output);
	
}

/**
 * Generate HTML multi-select val form (Insert)
 * @param $cols
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMultiValInsert($cols,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$temp = array();
	foreach ($cols as $I) {
		$temp[] = "'".$_POST['MYSQL'][$I["table"]]."'";
	}
	$output = implode(",",$temp);
	return($output);
	
}

/**
 * Generate HTML multi-select box form (Edit)
 * @param $attPairs
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMultiEditList($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$dbTables = $engine->dbTablesExport();
	
	$queryString = "";
	if (!empty($attPairs['addget']) && $attPairs['addget'] == "true") {
		foreach ($_GET['HTML'] as $var=>$val) {
			$queryString .= ((!empty($queryString))?"&amp;":"?").$var."=".$val;
		}
	}
	
	$sql = sprintf("SELECT * FROM %s ORDER BY %s",
		$engine->openDB->escape($attPairs['table']),
		$engine->openDB->escape($attPairs['col1'])
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	if (!$sqlResult['result']) {
		return webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	$cols = $attPairs['cols'];
	
	$output = "";
	
	$output  = "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\">";
	$output .= sessionInsertCSRF();
	$output .= "<table border=\"0\" cellpadding=\"1\" cellspacing=\"0\" id=\"".$engine->openDB->escape($attPairs['table'])."_table\">";
	$output .= "<tr style=\"background-color: #EEEEFF;\">";
	
	for($I=1;$I<=(int)$cols;$I++) {
		$output .= "<th style=\"text-align: left;\">".$attPairs['col'.$I.'label']."</th>";
	}
		
	$output .= "<th style=\"width: 100px;\">Delete</th>";
	$output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td colspan=\"".((int)$cols+1)."\" style=\"background-color: #000000;\"></td>";
	$output .= "</tr>";
	while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_BOTH)) {
		$output .= "<tr>";
		
		for($I=1;$I<=(int)$cols;$I++) {
			$output .= "<td style=\"padding-top: 3px;padding-bottom: 3px;\">";
			$output .= "<input type=\"text\" size=\"40\" name=\"".$attPairs['col'.$I]."_".$row[0]."\" id=\"".$attPairs['col'.$I]."_".$row[0]."\" class=\"".$attPairs['col'.$I]."\" value=\"".htmlentities($row[$attPairs['col'.$I]])."\" />";
			$output .= "</td>";
		}

		$output .= "<td style=\"text-align: center;padding-top: 3px;padding-bottom: 3px;\">";
		$output .= "<input type=\"checkbox\" name=\"delete[]\" class=\"delete\" value=\"".$row[0]."\" />";
		$output .= "</td>";
		$output .= "</tr>";
	    
	}
	$output .= "</table>";
	$output .= "<input type=\"submit\" value=\"Update\" name=\"updateSubmit\" />";
	$output .= "</form>";
	
	return($output); 
}

/**
 * Generate HTML multi-select box form (Update)
 * @param $table
 * @param $cols
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMultiUpdate($table,$cols,$engine=null) {
	
	$engine = EngineAPI::singleton();

	$dbTables = $engine->dbTablesExport();
	
	$output = "";
	
	if (isset($_POST['MYSQL']['delete'])) {
		foreach($_POST['MYSQL']['delete'] as $value) {

			$sql = sprintf("DELETE FROM %s WHERE ID=%s",
				$engine->openDB->escape($dbTables[$table]["prod"]),
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
		$engine->openDB->escape($dbTables[$table]["prod"])
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	if (!$sqlResult['result']) {
		$output .= webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_BOTH)) {
		
		if (isset($_POST['MYSQL'][$cols[1]["table"].'_'.$row[0]])) {
			$temp = $_POST['MYSQL'][$cols[1]["table"].'_'.$row[0]];
		}
		else {
			continue;
		}

		if (empty($temp)) {
			continue;
		}
		
		//Do the Email Check
		foreach ($cols as $I) {
			if (isset($I["email"]) && $I["email"] === TRUE) {
				if(!empty($_POST['MYSQL'][$I["table"].'_'.$row[0]]) && !validateEmailAddr($_POST['MYSQL'][$I["table"].'_'.$row[0]])) {
					$output .= webHelper_errorMsg("Invalid E-Mail Address: ". $_POST['MYSQL'][$I["table"].'_'.$row[0]]);
					continue 2;
				}
			}
		}

		// Check for duplicates
		if ($row[$cols[1]["table"]] != $temp && webHelper_listMultiDupeCheck($temp,$table,$cols[1]['table'],$engine)) {
			$output .= webHelper_errorMsg("Entry, ".$temp.", already in database. Other records may be updated still.");
			continue;
		}

		$sql = sprintf("UPDATE %s SET %s WHERE ID='%s'",
			$engine->openDB->escape($dbTables[$table]["prod"]),
			webHelper_listMulticolUpdate($cols,$row[0],$engine),
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
 * Generate HTML multi-select col form (Update)
 * @param $cols
 * @param $row
 * @param EngineAPI $engine NOT USED
 * @return string
 */
function webHelper_listMulticolUpdate($cols,$row,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$temp = array();
	foreach ($cols as $I) {
		$temp[] = $engine->openDB->escape($I["table"])."='".$_POST['MYSQL'][$I["table"]."_".$row]."'";
	}
	$output = implode(",",$temp);
	return($output);
	
}

/**
 * Duplicate check for multi-select helper functions?
 * @param $new
 * @param $table
 * @param $col
 * @param EngineAPI $engine NOT USED
 * @return bool
 */
function webHelper_listMultiDupeCheck($new,$table,$col,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	$sql = sprintf("SELECT * FROM %s WHERE %s='%s'",
		$engine->openDB->escape($table),
		$engine->openDB->escape($col),
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