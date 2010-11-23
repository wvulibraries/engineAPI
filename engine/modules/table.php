<?php

class tableObject {

	private $engine         = NULL;
	private $numColumns     = NULL;
	private $colHeaders     = NULL;
	private $colFooters     = NULL;
	
	// $tableType denotes how and where the object gets its data from.
	// "array" : Default -- Data comes in as an array of arrays. Each index is a row of data for the table
	// "mysql" : a mysql query is provided. each row of data returned from the query is a row of data for the table
 	private $tableType      = "array";
	
	public $class           = NULL; // CSS Class for the table
	public $id              = NULL; // CSS ID for the Table
	                                // If you have more than 1 table on a page, ID is highly recommended. Many items will prefix
	                                // id to other classes/IDs if it is available 
	public $title           = NULL; // HTML5 Title Attribute
	public $summary         = NULL; // Table Summary, required for accessibility. Will print an error if not provided
	public $summaryErrorMsg = TRUE; // if no summary is provided, print a reminder message
	
	public $rowStriping     = TRUE; // Zebtra stripe the edit table
	public $numberRows      = FALSE; // Rows are numbered on the far left
	public $sortable        = FALSE; // Edit Table is sortable by click on headers
	
	public $layout          = FALSE; // When true, add thead, tbody, and tfoot elements to the table
	
	public $groupBy         = NULL; // if groupby is not null, the array of rows will be examined and grouped by this column
	                                // this is the index number of the column, starting with 0

	function __construct($engine,$type=NULL) {

		if (!($engine instanceof EngineCMS)) {
			return(FALSE);
		}

		$this->engine = $engine;
		
		if (!isnull($type)) {
			if ($type != "array" && $type != "mysql") {
				return(FALSE);
			}
			$this->tableType = $type;
		}

	}
	
	function __destruct() {
	}
	
	// $header can be a string or an array. If it is a string it will be pushed onto the 
	// $colHeaders array. If it is an array, same thing ;-). 
	//
	// Returns TRUE on success, FALSE otherwise. 
	public function headers($header) {
		
		if (is_string($header)) {
			if (isnull($this->colHeaders)) {
				$this->colHeaders = array();
			}
			
			$this->colHeaders[] = $header;
			return(TRUE);
		}
		else if (is_array($header)) {
			if (isnull($this->colHeaders)) {
				$this->colHeaders = array();
			}
			
			foreach ($header as $I=>$V) {
				$this->colHeaders[] = $V;
			}
			return(TRUE);
		}
		
		return(FALSE);
		
	}
	
	// $footer can be a string or an array. If it is a string it will be pushed onto the 
	// $colfooters array. If it is an array, same thing ;-). 
	//
	// Returns TRUE on success, FALSE otherwise. 
	public function footers($footer) {
		
		if (is_string($footer)) {
			if (isnull($this->colFooters)) {
				$this->colFooters = array();
			}
			
			$this->colFooters[] = $footer;
			return(TRUE);
		}
		else if (is_array($footer)) {
			if (isnull($this->colFooters)) {
				$this->colFooters = array();
			}
			
			foreach ($footer as $I=>$V) {
				$this->colFooters[] = $V;
			}
			return(TRUE);
		}
		
		return(FALSE);
		
	}
	
	public function display($data) {
		
		$this->data = array();
		
		if ($this->tableType == "array") {
			if (!is_array($data)) {
				return webhelper_errorMsg("Data must be an array of arrays.");
			}
			$this->data = $data;
		}
		else if ($this->tableType == "mysql") {
			if (!is_string($data)) {
				return webhelper_errorMsg("Data must be a string (valid MySQL query).");
			}
			
			$data = $this->getMySQLdata($data);
			
			if ($data === FALSE || is_string($data)) {
				if (is_string($data)) {
					return webhelper_errorMsg($data);
				}
				return webhelper_errorMsg("Error retrieving dataset.");
			}
		}
		
		$output = "";
		
		if (isnull($this->summary) && $this->summaryErrorMsg === TRUE) {
			$output .= webhelper_errorMsg("Table Summary not provided");
		}
		
		if ($this->sortable === TRUE) {
			global $engineVars;
			$output .= "<script src=\"".$engineVars['sortableTables']."\" type=\"text/javascript\"></script>";
		}
		
		$output .= "<table";
		$output .= ($this->id)?' id="'.$this->id.'""':"";
		
		$output .= ' class="tableObject';
		$output .= ($this->sortable === TRUE)?" sortable":"";
		$output .= ($this->class)?' '.$this->class:"";
		$output .= '"';
		
		$output .= ($this->title)?' title="'.$this->title.'""':"";
		$output .= ($this->summary)?' summary="'.$this->summary.'"':"";
		$output .= ">";
		
		if (!isnull($this->colHeaders)) {
			if ($this->layout === TRUE) {
				$output .= "<thead>";	
			}
			
			$output .= '<tr class="headerRow">';
			
			if ($this->numberRows === TRUE) {
				$output .= "<th></th>";
			}
			
			foreach ($this->colHeaders as $I=>$V) {
				$output .= '<th id="'.(($this->id)?$this->id:"").'_header_'.$V.'">';
				$output .= $V;
				$output .= "</th>";
			}
			$output .= '</tr>';
			
			if ($this->layout === TRUE) {
				$output .= "</thead>";	
			}
		}
		
		if ($this->layout === TRUE) {
			$output .= "<tfoot>";
			$output .= '<tr>';
			if (!isnull($this->colFooters)) {
				if ($this->numberRows === TRUE) {
					$output .= "<td></td>";
				}
				foreach ($this->colFooters as $I=>$V) {
					$output .= '<td id="'.(($this->id)?$this->id:"").'_footer_'.$V.'">';
					$output .= $V;
					$output .= "</td>";
				}
			}
			$output .= "</tr>";
			$output .= "</tfoot>";	
		}
		
		if ($this->layout === TRUE) {
			$output .= "<tbody>";	
		}
		
		if ($this->groupBy !== NULL) {
			if (!is_integer($this->groupBy)) {
				return webhelpder_errorMsg("groupBy value must be an integer.");
			}
			$data = naturalSort($data,$this->groupBy);
		}
		
		$numberRowsCount = 1;
		$groupByRowCount = 1;
		$prevGroupBy     = NULL;
		foreach ($data as $I=>$row) {
			$output .= "<tr";
			if ($this->rowStriping === TRUE) {
				$output .= (is_odd($numberRowsCount))?" class=\"oddrow\"":" class=\"evenrow\"";
			}
			$output .= ">";
			
			if ($this->numberRows === TRUE) {
				$output .= '<td class="rowNumber">'.$numberRowsCount.'</td>';
			}
			
			$colCount = 0;
			foreach ($row as $K=>$V) {
				
				if (!isnull($this->groupBy) && $colCount == $this->groupBy && $V != $prevGroupBy) {
					$stripeGroupBy = (is_odd($groupByRowCount++))?"groupBy_oddrow":"groupBy_evenrow";
				}
				
				$output .= "<td";
				if (!isnull($this->groupBy) && $colCount == $this->groupBy) {
					$output .= ' class="'.$stripeGroupBy.' groupByCell"';
				}
				$output .= ">";
				if ($colCount++ != $this->groupBy || isnull($this->groupBy) || ($colCount != $this->groupBy && isset($this->groupBy) && $V != $prevGroupBy)) {
					$output .= $V;
				}
				$output .= "</td>";
			}
			
			$output .= "</tr>";
			
			$numberRowsCount++;
			
			if (!isnull($this->groupBy)) {
				$prevGroupBy = $row[$this->groupBy];
			}
		}
		
		if ($this->layout === TRUE) {
			$output .= "</tbody>";	
		}
		else if ($this->layout === FALSE) {
			$output .= '<tr class="sortbottom">';
			if (!isnull($this->colFooters)) {
				if ($this->numberRows === TRUE) {
					$output .= "<td></td>";
				}
				foreach ($this->colFooters as $I=>$V) {
					$output .= '<td id="'.(($this->id)?$this->id:"").'_footer_'.$V.'">';
					$output .= $V;
					$output .= "</td>";
				}
			}
			$output .= "</tr>";
		}
		
		$output .= "</table>";
	
		return($output);		
	}
	
	
	private function getMySQLdata($data) {
		if (!is_string($data)) {
			return FALSE;
		}

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($data);
		
		if (!$sqlResult['result']) {
			return($sqlResult['error']);
			return(FALSE);
		}
		
		unset($data);
		$data = array();
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			$data[] = $row;
		}
		
		return($data);
		
	}
}



?>