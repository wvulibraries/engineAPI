<?php
class search {
	
	private $engine         = NULL;
	private $searchArray    = array();
	private $searchString   = array();
	private $boolOperands   = array();
	private $tables         = array();
	private $fields         = array();
	private $query          = array();
	
	public $tempTablePrefix = "engineSearchModule_";
	public $searchRows      = 1;
	public $whereClause     = NULL;
	public $orderBy         = NULL;
	public $relevanceSearch = TRUE;
	
	
	function __construct() {
		
		$this->engine   = EngineAPI::singleton();
		$this->boolOperands = array('or'  => '',
									'and' => '+',
									'not' => '-');

	}
	
	function __destruct() {
	}
	

	public function createTable($table) {
		
		if (empty($this->fields)) {
			return webHelper_errorMsg("No fields defined.");
		}
		
		$tableName    = $this->tempTablePrefix.$table;
		$fromClause   = NULL;
		$selectClause = NULL;
		$fieldNames   = array();
		$from         = array();
		

		if (is_array($this->links)) {
			foreach ($this->links as $link) {
				if (is_empty($from)) {
					$from[$link['table1']][] = $link['table1'];
				}
				$from[$link['table2']][] = $link['table2'].".".$link['field2']."=".$link['table1'].".".$link['field1'];
			}
		}

		foreach ($from as $tName => $fields) {
			if (isnull($fromClause)) {
				$fromClause .= $tName;
			}
			else {
				$fromClause .= " LEFT JOIN ".$tName." ON (";
				foreach ($fields as $key => $value) {
					$fromClause .= (($key!=0)?" AND ":"").$value;
				}
				$fromClause .= ")";
			}
		}

		foreach ($this->fields as $tmp) {
			$fieldNames[] = $tmp['fieldName'];
			foreach ($tmp as $field) {
				if (isnull($fromClause)) {
					$fromClause .= $field['table'];
				}

				if (is_array($field)) {
					$selectClause .= isnull($selectClause)?"":", ";
					if (!isset($field['function']) || $field['function'] === FALSE) {
						$selectClause .= $field['table'].".";
					}
					$selectClause .= $field['field'];
					$selectClause .= isset($field['alias'])?" AS ".$field['alias']:"";
				}
			}
		}
		

		$sql = sprintf("CREATE TEMPORARY TABLE %s (%s TEXT, FULLTEXT (%s)) ENGINE=MyISAM",
			$this->engine->openDB->escape($tableName),
			implode(" TEXT, ",$fieldNames),
			implode(", ",$fieldNames)
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Failed to create table: $table");
		}

		
		$sql = sprintf("INSERT INTO %s (%s) SELECT DISTINCT %s FROM %s %s",
			$this->engine->openDB->escape($tableName),
			$this->engine->openDB->escape(implode(", ",$fieldNames)),
			$selectClause,
			$this->engine->openDB->escape($fromClause),
			$this->whereClause
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (debugNeeded("populate")) {
			print "<pre>";
			print_r($sqlResult);
			print "</pre>";
		}
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error populating table: $table");
		}

		// Debug: To view contents of the temp table
		// $sql = sprintf("SELECT * FROM %s",
		// 	$this->engine->openDB->escape($tableName)
		// 	);
		// $this->engine->openDB->sanitize = FALSE;
		// $sqlResult                      = $this->engine->openDB->query($sql);
		// while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		// 	print "<pre>";
		// 	print_r($row);
		// 	print "</pre>";
		// }
		
		$thisTable = array();
		$thisTable['name']   = $this->tempTablePrefix.$table;
		$thisTable['fields'] = $this->fields;
		$thisTable['links']  = $this->links;
		
		$this->tables[] = $thisTable;
		
		$this->destroyTableDefs();
		

	}

	public function addField($attPairs) {
		$this->fields[] = $attPairs;
	}
	
	public function addLink($attPairs) {
		$this->links[] = $attPairs;
	}

	private function destroyTableDefs() {
		
		unset($this->fields);
		unset($this->links);

		$this->fields = array();
		$this->links  = array();

		$this->whereClause = NULL;
		$this->orderBy     = NULL;

	}

	
	public function submit() {
		
		$output  = NULL;
		$results = array();
		$this->searchArray  = $this->createSearchArray();
		$this->searchString = $this->convertSearchArrayToBoolForm();
		
		if (debugNeeded("search")) {
			print "<pre>";
			print_r($this->searchArray);
			print "</pre>";
			
		}

		$fieldNames = array();
		foreach ($this->tables as $table) {
			foreach ($table['fields'] as $field) {
				if (isset($field['search']) && $field['search'] === FALSE) {
					continue;
				}
				$fieldNames[$table['name']][] = $field['fieldName'];
			}
			
			$match = "MATCH(".implode(", ",$fieldNames[$table['name']]).") AGAINST ('".$this->searchString."' IN BOOLEAN MODE)";

			// where
			if (!is_empty($this->whereClause)) {
				$whereStr = $this->whereClause." AND ";
			}
			else {
				$whereStr = "WHERE";
			}
			
			$sql = sprintf("SELECT DISTINCT *, %s AS relevance FROM %s %s %s HAVING relevance > 0.2",
				$match,
				$this->engine->openDB->escape($table['name']),
				$whereStr,
				$match
				);
			
			// order by
			if ($this->relevanceSearch === TRUE) {
				if (isnull($this->orderBy)) {
					$sql .= " ORDER BY relevance DESC";
				}
				else {
					$orderBy = str_ireplace("ORDER BY","",$this->orderBy);
					$sql .= " ORDER BY relevance DESC,".$orderBy;
				}
			}
			else {
				$sql .= " ".$this->orderBy;
			}
			
			if (debugNeeded("query")) {
				print "<br />$sql<br /><br />";
			}

			$this->engine->openDB->sanitize = FALSE;
			$sqlResult                      = $this->engine->openDB->query($sql);

			while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
				$row[$this->tempTablePrefix.'tableName'] = $table['name'];
				$results[] = $row;
			}
				
		}

		$output .= $this->displayResults($results);
		
		return $output;
		
	}


	private function displayResults($results) {
		
		if (count($results) ==0) {
			return webHelper_errorMsg("No results found.");
		}
		
		$output = NULL;
		
		if ($this->relevanceSearch === TRUE) {
			$tmp = array(); 
			foreach($results as &$ma) {
			    $tmp[] = &$ma['relevance']; 
			}
			array_multisort($tmp, SORT_DESC, $results); 
		}

		
		$output .= "<table>";
		
		foreach ($results as $key => $result) {
			foreach ($this->tables as $table) {
				if ($result['engineSearchModule_tableName'] == $table['name']) {
					foreach ($table['fields'] as $field) {

						if (!isset($field['display']) || $field['display'] !== TRUE || is_empty($result[$field['fieldName']])) {
							continue;
						}

						if (isset($result[$field['fieldName']])) {
							$output .= "<tr>";
							$output .= "<td>".$field['label']."</td>";
							$output .= "<td>";

							if (isset($field['link'])) {

								$link = $field['link'];
								preg_match_all('/{(\w+)}/', $link, $matches);
								foreach ($matches[1] as $mValue) {
									foreach ($result as $key => $val) {
										if ($key == $mValue) {
											$link = preg_replace('/{'.$mValue.'}/',$val,$link);
										}
									}
								}

								$output .= '<a href="'.$link.'">';

							}
							
							$value = $result[$field['fieldName']];
							foreach ($this->searchArray as $keyword) {
								if (!array_key_exists(strtolower($keyword),$this->boolOperands)) {
									$value = kwic(htmlSanitize(trim($keyword,"+-*")),$value);
								}
							}
							$output .= $value;
							
							if (isset($field['link'])) {
								$output .= '</a>';
							}

							$output .= "</td>";
							$output .= "</tr>";
						}
					}
					
					// separate results
					$output .=  "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";

				}
			}
		}

		$output .= "</table>";
		
		return $output;
		
	}
	
	private function super_unique($array) {
		$result = array_map("unserialize", array_unique(array_map("serialize", $array)));
		
		foreach ($result as $key => $value) {
			if ( is_array($value) ) {
				$result[$key] = $this->super_unique($value);
			}
		}
		return $result;
	}
	
	private function createSearchArray() {
		
		$searchString  = isset($this->engine->cleanPost['RAW']['searchString'])?$this->engine->cleanPost['RAW']['searchString']:array();
		$bool          = isset($this->engine->cleanPost['MYSQL']['bool'])?$this->engine->cleanPost['MYSQL']['bool']:array();
		$keywords      = array();
		$fullSearchStr = NULL;
		
		// loop through each row in the form
		for ($i = 0; $i < $this->searchRows; $i++) { 
			
			if (!isset($searchString[$i]) || is_empty($searchString[$i])) {
				continue;
			}

			// add dropdown boolean operator to array
			if (isset($bool[$i]) && !is_empty($bool[$i])) {
				$fullSearchStr .= " ".$bool[$i];
			}
			
			$fullSearchStr .= " ".$searchString[$i]."";

		}

		$keywords = $this->searchStringToArray($fullSearchStr);

		return $keywords;

	}
	
	private function searchStringToArray($searchStr) {

		$keywords = array();
		$result   = NULL;
		
		$l = strlen($searchStr);

		for ($i=0; $i<=$l; $i++) {
			
			$char = substr($searchStr, $i, 1);
		    
			if ($char != ' ' && $char != '(' && $char != ')') {
				$result .= trim($char);
			}
			else {
				
				if (!empty($result)) {
					$keywords[] = $result;
				}
				
				$char = trim($char);
				
				if (!empty($char)) {
					$keywords[] = $char;
				}
				
				$result = '';

			}
		}
		
		if (!empty($result)) {
			$keywords[] = $result;
		}
		
		return $keywords;

	}


	private function convertSearchArrayToBoolForm($searchArray=NULL) {
		
		if (isnull($searchArray)) {
			$searchArray = $this->searchArray;
		}

		$keywords = array_reverse($searchArray);
		$results = array();
		$i = 0;
		$j = 0;

		while ($i < 1) {

			$keyword = strtolower(array_pop($keywords));
			
			if ($keyword == '(') {
				
				$parens = 0;
				$tmp = array();	
				
				while ($j < 1) {
					
					$keyword2 = strtolower(trim(array_pop($keywords)));
					
					if ($keyword2 == '(') {
						$tmp[] = $keyword2;
						$parens++;
						$keyword2 = array_pop($keywords);
					}
					elseif ($keyword2 == ')') {
						$parens--;
						$keyword2 = array_pop($keywords);
					}
						
					if ($keyword2 == ')' && $parens <= 0)	{
						$results[] = '('.$this->convertSearchArrayToBoolForm($tmp).')';
						break;		
					}
					
					$tmp[] = trim($keyword2);
					if (count($keywords)<=0)	{
						$results[] = '('.trim($this->convertSearchArrayToBoolForm($tmp)).')';
						break;
					}

				}

			}
			else {
				$results[] = $keyword;
			}
			
			if (count($keywords) <= 0) {
				break;
			}
		}


		$tmp = array_reverse($results);
		$results = array();
		
		while (count($tmp))	{
			
			$a = array_pop($tmp);
			$b = array_pop($tmp);
			$c = array_pop($tmp);
			
			if (!$a) {
				break;
			}

			$aistoken = array_key_exists($a, $this->boolOperands)*1;
			$bistoken = array_key_exists($b, $this->boolOperands)*2;
			$cistoken = array_key_exists($c, $this->boolOperands)*4;
			$sw = $aistoken + $bistoken + $cistoken;
			
			switch ($sw) {
				// No bool operands in the 3 elements, therefore all are OR
				case 0:
					$results[] = $a;
					$results[] = $b;
					$results[] = $c;
					break;
				
				// $a is a bool operand (shouldn't ever happen), therefore $b gets the sign and $c gets pushed back
				case 1:
					$results[] = $this->boolOperands[$a].$b;	
					array_push($tmp, $c);
					break;

				// $b is a bool operand, therefore both $a and $c get the sign, unless $b is NOT, then only $c gets the sign
				case 2:
					if ($b == 'not') {
						$results[] = $a;
					}
					else {
						$results[] = $this->boolOperands[$b].$a;
					}
					$results[] = $this->boolOperands[$b].$c;
					break;

				// $a and $c are both bool operands, this is either an error or it's an AND/OR NOT clause
				case 3:
					if ($a == $b) {
						// $a and $b are identical, this is an error - assumes one was meant
						$results[] = $this->boolOperands[$b].$c;
						$a = trim($a);
						break;
					}
					
					// If we have AND and OR consecutively it is an error and we'll go with OR
					if (($a == 'or' && $b == 'and') || ($a == 'and' && $b == 'or'))	{
						$results[] = $this->boolOperands['or'].$c;
						break;
					}
					
					// Every thing else is an AND/OR NOT clause, so we just need the NOT sign 
					// anything else should be apparent
					$results[] = $this->boolOperands['not'].$c;
					break;

				// $c is a bool operand, $a and $b are OR, $c is pushed back to the array
				case 4:
					$results[] = $this->boolOperands['or'].$a;
					$results[] = $this->boolOperands['or'].$b;
					array_push($tmp, $c);
					break;

				// $a and $c are bool operands, $b gets the sign and $c is pushed back to the array
				case 5:
					$results[] = $this->boolOperands[$a].$b;
					array_push($tmp, $c);
					break;

				// $b and $c are bool operands, $a gets the sign and $b and $c are pushed back to the array
				case 6:
					$results[] = $this->boolOperands[$b].$a;
					array_push($tmp, $b);
					array_push($tmp, $c);
					break;

				// all 3 are bool operands, this is always an error
				case 7:
					// if $a == $b == $c we'll asume only one was meant and return that
					if ($a == $b && $b == $c) {
						array_push($tmp, $a);
					}
					
					// if $a == $b != $c we'll turn $a and $c into one and return that and $c
					// if $a != $b == $c we'll turn $b and $c into one and return that and $a
					elseif (($a == $b && $b != $c) || $b == $c)	{
						array_push($tmp, $a);
						array_push($tmp, $c);
					}
					
					// if $a != $b != $c we're hopelessly lost
					$a = trim($a);
					$b = trim($b);
					$c = trim($c);
					break;
					
				default:
					break;
				
			}

		}
		
		return implode($results, ' ');

	}
	

	public function displayForm($rows=1,$addGet=TRUE) {
		
		$this->searchRows = $rows;
		
		$output = NULL;
		$bool   = isset($this->engine->cleanPost['HTML']['bool'])?$this->engine->cleanPost['HTML']['bool']:array();
		$type   = isset($this->engine->cleanPost['HTML']['searchTypes'])?$this->engine->cleanPost['HTML']['searchTypes']:array();
		$search = isset($this->engine->cleanPost['HTML']['searchString'])?$this->engine->cleanPost['HTML']['searchString']:array();
		
		
		$output .= '<form method="post" action="'.$_SERVER['PHP_SELF'].($addGet?'?'.$_SERVER['QUERY_STRING']:'').'">';
		$output .= '<table>';
		
		for ($i = 0; $i < $this->searchRows; $i++) {
			$output .= '<tr>';
			
			$output .= '<td>';
			if ($i == 0) {
				$output .= '<input type="hidden" name="bool[]" value="" />';
			}
			else {
				if (!isset($bool[$i])) {
					$bool[$i] = NULL;  // avoids errors on new forms
				}
				
				$output .= '<select name="bool[]">';
				$output .= '<option value="and"'.($bool[$i]=='and'?' selected':'').'>AND</option>';
				$output .= '<option value="or"'.($bool[$i]=='or'?' selected':'').'>OR</option>';
				$output .= '<option value="not"'.($bool[$i]=='not'?' selected':'').'>NOT</option>';
				$output .= '</select>';
			}
			$output .= '</td>';
			
			$output .= '<td>';
			if (!empty($this->searchTypes)) {
				$output .= '<select name="searchTypes[]">';
				foreach ($this->searchTypes as $searchType) {
					if (isset($type[$i]) && $type[$i]==$searchType['name']) {
						$output .= '<option value="'.$searchType['name'].'" selected>'.$searchType['label'].'</option>';
					}
					else {
						$output .= '<option value="'.$searchType['name'].'">'.$searchType['label'].'</option>';
					}
				}
				$output .= '</select>';
			}
			$output .= '</td>';
			
			$output .= '<td><input type="search" name="searchString[]" value="'.(isset($search[$i])?$search[$i]:"").'" />';
			if ($i == 0) {
				$output .= '<input type="submit" name="searchSubmit" value="Search" />';
			}
			$output .= '</td>';
			$output .= '</tr>';
		}
		
		$output .= '</table>';
		$output .= sessionInsertCSRF();
		$output .= '</form>';
		
		return $output;		
		
	}
	
}
?>
