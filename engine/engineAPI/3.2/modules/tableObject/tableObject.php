<?php

class tableObject {

	private $engine;
	private $tableType = "array";
	public $class;
	public $id;
	public $classTD = false;
	public $title;
	public $summary;
	public $summaryErrorMsg = true;
	public $rowStriping = true;
	public $numberRows = false;
	public $sortable = false;
	public $layout = false;
	public $groupBy;

	private $colHeaders = array();
	private $colFooters = array();
	private $data = array();

	public function __construct($type = null) {
		$this->engine = EngineAPI::singleton();
		if (!is_null($type) && in_array($type, array("array", "mysql"))) {
			$this->tableType = $type;
		}
	}

	public function headers($header) {
		if (is_string($header)) {
			$this->colHeaders[] = $header;
			return true;
		} elseif (is_array($header)) {
			$this->colHeaders = array_merge($this->colHeaders, $header);
			return true;
		}
		return false;
	}

	public function footers($footer) {
		if (is_string($footer)) {
			$this->colFooters[] = $footer;
			return true;
		} elseif (is_array($footer)) {
			$this->colFooters = array_merge($this->colFooters, $footer);
			return true;
		}
		return false;
	}

	public function display($data) {
		$this->data = array();

		if ($this->tableType == "array") {
			if (!is_array($data)) {
				return $this->errorMsg("Data must be an array of arrays.");
			}
			$this->data = $data;
		} elseif ($this->tableType == "mysql") {
			if (!is_string($data)) {
				return $this->errorMsg("Data must be a string (valid MySQL query).");
			}

			$data = $this->getMySQLdata($data);

			if ($data === false || is_string($data)) {
				if (is_string($data)) {
					return $this->errorMsg($data);
				}
				return $this->errorMsg("Error retrieving dataset.");
			}
		}

		$output = "";

		if (is_null($this->summary) && $this->summaryErrorMsg === true) {
			$output .= $this->errorMsg("Table Summary not provided");
		}

		if ($this->sortable === true) {
			global $engineVars;
			$output .= "<script src=\"".$engineVars['sortableTables']."\" type=\"text/javascript\"></script>";
		}

		$output .= "<table";
		$output .= ($this->id) ? ' id="'.$this->id.'"' : "";

		$output .= ' class="tableObject';
		$output .= ($this->sortable === true) ? " sortable" : "";
		$output .= ($this->class) ? ' '.$this->class : "";
		$output .= '"';

		$output .= ($this->title) ? ' title="'.$this->title.'"' : "";
		$output .= ($this->summary) ? ' summary="'.$this->summary.'"' : "";
		$output .= ">";

		if (!empty($this->colHeaders)) {
			if ($this->layout === true) {
				$output .= "<thead>";
			}

			$output .= '<tr class="headerRow">';

			if ($this->numberRows === true) {
				$output .= "<th></th>";
			}

			foreach ($this->colHeaders as $header) {
				$output .= '<th id="'.(($this->id) ? $this->id : "").'_header_'.$header.'">';
				$output .= $header;
				$output .= "</th>";
			}
			$output .= '</tr>';

			if ($this->layout === true) {
				$output .= "</thead>";
			}
		}

		if ($this->layout === true) {
			$output .= "<tfoot>";
			$output .= '<tr>';
			if (!empty($this->colFooters)) {
				if ($this->numberRows === true) {
					$output .= "<td></td>";
				}
				foreach ($this->colFooters as $footer) {
					$output .= '<td id="'.(($this->id) ? $this->id : "").'_footer_'.$footer.'">';
					$output .= $footer;
					$output .= "</td>";
				}
			}
			$output .= "</tr>";
			$output .= "</tfoot>";
		}

		if ($this->layout === true) {
			$output .= "<tbody>";
		}

		if (!is_null($this->groupBy)) {
			if (!is_integer($this->groupBy)) {
				return $this->errorMsg("groupBy value must be an integer.");
			}
			$data = naturalSort($data, $this->groupBy);
		}

		$numberRowsCount = 1;
		$groupByRowCount = 1;
		$prevGroupBy = null;
		foreach ($data as $row) {
			$output .= "<tr";
			if ($this->rowStriping === true) {
				$output .= (is_odd($numberRowsCount)) ? " class=\"oddrow\"" : " class=\"evenrow\"";
			}
			$output .= ">";

			if ($this->numberRows === true) {
				$output .= '<td class="rowNumber">'.$numberRowsCount.'</td>';
			}

			$colCount = 0;
			foreach ($row as $key => $value) {
				if (!is_null($this->groupBy) && $colCount == $this->groupBy && $value != $prevGroupBy) {
					$stripeGroupBy = (is_odd($groupByRowCount++)) ? "groupBy_oddrow" : "groupBy_evenrow";
				}

				$output .= "<td";
				if (!is_null($this->groupBy) && $colCount == $this->groupBy) {
					$output .= ' class="'.(($this->classTD === true) ? "td".$colCount." " : "").''.$stripeGroupBy.' groupByCell"';
				} elseif ($this->classTD === true) {
					$output .= ' class="td'.$colCount.'"';
				}
				$output .= ">";
				if ($colCount++ != $this->groupBy || is_null($this->groupBy) || ($colCount != $this->groupBy && isset($this->groupBy) && $value != $prevGroupBy)) {
					# if value is an array then we need to read each one showing the creator name
					if (is_array($value)) {
						$creator = array();
						foreach ($value as $id) {
							// get creator of object
							$creatorObject = objects::get($id);
							
							// add $creator['data']['title'] to creator array
							$creator[] = $creatorObject['data']['title'];
						}
						$output .= implode(", ", $creator);
					} else {
						$output .= $value;
					}
				}
				$output .= "</td>";
			}

			$output .= "</tr>";

			$numberRowsCount++;

			if (!is_null($this->groupBy)) {
				$prevGroupBy = $row[$this->groupBy];
			}
		}

		if ($this->layout === true) {
			$output .= "</tbody>";
		} elseif ($this->layout === false) {
			$output .= '<tr class="sortbottom">';
			if (!empty($this->colFooters)) {
				if ($this->numberRows === true) {
					$output .= "<td></td>";
				}
				$colCount = 0;
				foreach ($this->colFooters as $footer) {
					$output .= '<td id="'.(($this->id) ? $this->id : "").'_footer_'.$footer.'" '.(($this->classTD === true) ? 'class="td'.$colCount.'" ' : "").'>';
					$output .= $footer;
					$output .= "</td>";
					$colCount++;
				}
			}
			$output .= "</tr>";
		}

		$output .= "</table>";

		return $output;
	}

	private function getMySQLdata($data) {
		if (!is_string($data)) {
			return false;
		}

		$this->engine->openDB->sanitize = false;
		$sqlResult = $this->engine->openDB->query($data);

		if (!$sqlResult['result']) {
			return $sqlResult['error'];
			return false;
		}

		$data = [];
		while ($row = mysqli_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
			$data[] = $row;
		}

		return $data;
	}

	private function errorMsg($message) {
		return "<div class=\"error\">".$message."</div>";
	}
}