<?php

function webHelper_listAdd($attPairs) {
	
	global $cleanGet;
	
	$output = "<form action=\"".$_SERVER['PHP_SELF']."?type=".$cleanGet['MYSQL']['type']."\" method=\"post\">";
	$output .= sessionInsertCSRF();
	$output .= "<label for=\"newListItem\">New ".$attPairs['label'].":</label> &nbsp;&nbsp;";
	$output .= "<input type=\"text\" size=\"25\" name=\"newListItem\" id=\"newListItem\" value=\"\" />";
	$output .= "<br /><br />";
	$output .= "<input type=\"submit\" value=\"Add ".$attPairs['label']."\" name=\"newSubmit\" />";
	$output .= "</form>";
	
	return($output);
	
}

function webHelper_listInsert($table,$label) {
	
	global $cleanPost;
	global $engineVars;
		
	if (empty($cleanPost['MYSQL']['newListItem'])) {
		return webHelper_errorMsg($label ." was left blank.");
	}
	else {
		
		// Check for duplicates
		if (webHelper_listDupeCheck($cleanPost['MYSQL']['newListItem'],$table)) {
			return webHelper_errorMsg("Entry, ".$cleanPost['HTML']['newListItem'].", already in database.");
		}
				
		$sql = sprintf("INSERT INTO %s (name) VALUES('%s')",
			$engineVars['openDB']->escape($table),
			$engineVars['openDB']->escape($cleanPost['MYSQL']['newListItem'])
			);

		$engineVars['openDB']->sanitize = FALSE;
		$sqlResult = $engineVars['openDB']->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("SQL Error:".$sqlResult['error']);
		}
		else {
			return webHelper_successMsg("Entry, ".$cleanPost['HTML']['newListItem'].", successfully added to the database.");
		}
		
	}
	
	return webHelper_errorMsg("Something went horribly wrong. listManagement.pl -- webHelper_listInsert()");
}

function webHelper_listEditList($attPairs) {
	global $cleanGet;
	global $engineVars;
	global $dbTables;
	
	$sql = sprintf("SELECT * FROM %s ORDER BY name",
		$engineVars['openDB']->escape($dbTables[$cleanGet['MYSQL']['type']]["prod"])
		);
		
	$engineVars['openDB']->sanitize = FALSE;
	$sqlResult = $engineVars['openDB']->query($sql);
	
	if (!$sqlResult['result']) {
		return webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	$output = "";
	
	$output  = "<form action=\"".$_SERVER['PHP_SELF']."?type=".$cleanGet['MYSQL']['type']."\" method=\"post\">";
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
		$output .= "<input type=\"text\" size=\"40\" name=\"title_".$row[0]."\" value=\"".$row[1]."\" />";
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

function webHelper_listUpdate($table) {

	global $cleanPost;
	global $cleanGet;
	global $engineVars;
	global $dbTables;
	
	$output = "";
	
	if (isset($cleanPost['MYSQL']['delete'])) {
		foreach($cleanPost['MYSQL']['delete'] as $value) {

			$sql = sprintf("DELETE FROM %s WHERE ID=%s",
				$engineVars['openDB']->escape($dbTables[$cleanGet['MYSQL']['type']]["prod"]),
				$engineVars['openDB']->escape($value)
				);

			$engineVars['openDB']->sanitize = FALSE;
			$sqlResult = $engineVars['openDB']->query($sql);

			if (!$sqlResult['result']) {
				$output .= webHelper_errorMsg("SQL Error".$sqlResult['error']);
			}
			
		}
	}

	$sql = sprintf("SELECT * FROM %s",
		$engineVars['openDB']->escape($dbTables[$cleanGet['MYSQL']['type']]["prod"])
		);
		
	$engineVars['openDB']->sanitize = FALSE;
	$sqlResult = $engineVars['openDB']->query($sql);
	
	if (!$sqlResult['result']) {
		$output .= webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM)) {
		$temp = $cleanPost['MYSQL']['title_'.$row[0]];

		if (empty($temp) || $row[1] == $temp) {
			continue;
		}

		// Check for duplicates
		if (webHelper_listDupeCheck($temp,$table)) {
			$output .= webHelper_errorMsg("Entry, ".$temp.", already in database. Other records may be updated still.");
			continue;
		}

		$sql = sprintf("UPDATE %s SET name='%s' WHERE ID='%s'",
			$engineVars['openDB']->escape($dbTables[$cleanGet['MYSQL']['type']]["prod"]),
			$engineVars['openDB']->escape($temp),
			$row[0]
			);

		$engineVars['openDB']->sanitize = FALSE;
		$sqlResult2 = $engineVars['openDB']->query($sql);

		if (!$sqlResult2['result']) {
			$output .= webHelper_errorMsg("SQL Error".$sqlResult2['error']);
		}
		
	}

	if(empty($output)) {
		$output = webHelper_successMsg("Database successfully updated.");
	}
	
	return($output);
}

function webHelper_listSelect($attPairs) {
	
	global $engineVars;
	global $dbTables;
		
	$multiselect = "";
	if (isset($attPairs['type']) && $attPairs['type'] == "multi") {
		$multiselect = 'multiple="multiple"';
	}
		
	$output = "<select name=\"".$attPairs['table']."\" ".$multiselect.">";
	
	$sql = sprintf("SELECT * FROM %s",
		$engineVars['openDB']->escape($dbTables[$attPairs['table']]["prod"])
		);
		
	$engineVars['openDB']->sanitize = FALSE;
	$sqlResult = $engineVars['openDB']->query($sql);
	
	if (!$sqlResult['result']) {
		return webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM)) {
		$output .= "<option value=\"".$row[0]."\">".$row[1]."</option>";
	}
	
	$output .= "</select>";
	
	return($output);
}

function webHelper_listDupeCheck($new,$table) {
	
	global $engineVars;
	
	$sql = sprintf("SELECT * FROM %s WHERE name='%s'",
		$engineVars['openDB']->escape($table),
		$engineVars['openDB']->escape($new)
		);
		
	$engineVars['openDB']->sanitize = FALSE;
	$sqlResult = $engineVars['openDB']->query($sql);
	
	//We should probably do a SQL check here
	
	if (mysql_num_rows($sqlResult['result']) == 0) {
		return(FALSE);
	}
	
	return(TRUE);
}

?>