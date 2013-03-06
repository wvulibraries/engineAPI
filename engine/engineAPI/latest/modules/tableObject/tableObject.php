<?php
/**
 * EngineAPI tableObject module
 * @package EngineAPI\modules\tableObject
 */
class tableObject {

	/**
	 * @var EngineAPI
	 */
	private $engine         = NULL;

	/**
	 * @todo This doesn't look used
	 * @var int
	 */
	private $numColumns     = NULL;

	/**
	 * Array of column headers
	 * @var string[]
	 */
	private $colHeaders     = NULL;

	/**
	 * Array of column footers
	 * @var string[]
	 */
	private $colFooters = NULL;

	/**
	 * Denotes how and where the object gets its data from.
	 *   - array: [Default] Data comes in as an array of arrays. Each index is a row of data for the table
	 *   - mysql: A mysql query is provided. each row of data returned from the query is a row of data for the table
	 * @var string
	 */
	private $tableType = "array";

	/**
	 * CSS Class for the table
	 * @var string
	 */
	public $class = NULL;

	/**
	 * CSS ID for the Table
	 * If you have more than 1 table on a page, ID is highly recommended.
	 * Many items will prefix id to other classes/IDs if it is available
	 * @var string
	 */
	public $id = NULL;

	/**
	 * Prints the column number as a class on each TD in the Table
	 * @var bool
	 */
	public $classTD = FALSE;

	/**
	 * HTML5 Title Attribute
	 * @var string
	 */
	public $title = NULL;

	/**
	 * Table Summary
	 * Required for accessibility - will print an error if not provided
	 * @var null
	 */
	public $summary = NULL;

	/**
	 * If no summary is provided, print a reminder message
	 * @var bool
	 */
	public $summaryErrorMsg = TRUE;

	/**
	 * Zebra stripe the edit table
	 * @var bool
	 */
	public $rowStriping = TRUE;

	/**
	 * Rows are numbered on the far left
	 * @var bool
	 */
	public $numberRows = FALSE;

	/**
	 * Edit Table is sortable by click on headers
	 * @var bool
	 */
	public $sortable = FALSE;

	/**
	 * When true, add thead, tbody, and tfoot elements to the table
	 * @var bool
	 */
	public $layout = FALSE;

	/**
	 * If groupby is not null, the array of rows will be examined and grouped by this column
	 * this is the index number of the column, starting with 0
	 * @var int
	 */
	public $groupBy = NULL;

	/**
	 * Class constructor
	 *
	 * @param null $type
	 *   - array: [Default] Data comes in as an array of arrays. Each index is a row of data for the table
	 *   - mysql: A mysql query is provided. each row of data returned from the query is a row of data for the table
	 */
	function __construct($type=NULL) {

		$this->engine = EngineAPI::singleton();
		
		if (!isnull($type)) {
			if ($type != "array" && $type != "mysql") {
				return(FALSE);
			}
			$this->tableType = $type;
		}

	}
	
	/**
	 * Add column header(s)
	 *
	 * @param string|array $header
	 *        Item(s) will be pushed onto the $colHeaders array
	 * @return bool
	 *         TRUE on success, FALSE otherwise.
	 */
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
	
	/**
	 * Add column footer(s)
	 *
	 * @param string|array $footer
	 *        Item(s) will be pushed onto the $colHeaders array
	 * @return bool
	 *         TRUE on success, FALSE otherwise.
	 */
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

	/**
	 * Generates HTML table
	 *
	 * @todo Remove usage of deprecated webhelper_errorMsg()
	 * @param string|array $data
	 *        if tableType is 'array' $data must be an array or arrays (rows=>columns)
	 *        if tableType is 'mysql' $data must be a SQL string
	 * @return string
	 */
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
		$output .= ($this->id)?' id="'.$this->id.'"':"";
		
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
					$output .= ' class="'.(($this->classTD === TRUE)?"td".$colCount." ":"").''.$stripeGroupBy.' groupByCell"';
				}
				else if ($this->classTD === TRUE) {
					$output .= ' class="td'.$colCount.'"';
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
				$colCount = 0;
				foreach ($this->colFooters as $I=>$V) {
					$output .= '<td id="'.(($this->id)?$this->id:"").'_footer_'.$V.'" '.(($this->classTD === TRUE)?'class="td'.$colCount.'" ':"").'>';
					$output .= $V;
					$output .= "</td>";
					$colCount++;
				}
			}
			$output .= "</tr>";
		}
		
		$output .= "</table>";
	
		return($output);		
	}


	/**
	 * Get MySQL data from database to build the table with
	 *
	 * @param string $data
	 *        SQL query string
	 * @return array|bool
	 */
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