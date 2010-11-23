<?php

class listManagement {

	private $engine        = NULL;
	private $table         = NULL;
	private $fields        = array();
	private $hiddenFields  = array();
	private $dateInputs    = FALSE;
	private $wysiwygInputs = FALSE;
	private $multiSelect   = FALSE;
	
	public  $orderBy      = NULL;  // Custom sort for the edit table
	public  $numberRows   = FALSE; // Rows are numbered on the far left
	public  $sortable     = FALSE; // Edit Table is sortable by click on headers
	public  $dragOrdering = FALSE; // Enable reordering of elements in the list, by dragging them around
	                               // Drag and Drop ordering requires a field in the table called "position"

	public  $deleteBox      = TRUE;  // Provide a delete checkbox on edit table
	public  $rowStriping    = TRUE;  // Zebtra stripe the edit table
	public  $repost         = TRUE;  // When an item is added, if there are errors, fillout the form with previous values
	public  $noSubmit       = FALSE; // Submit button is not displayed. 
	public  $whereClause    = "";    // Where clause that is used for the edit list. This is NOT a sanitized in the module!
	public  $updateInsert   = FALSE; // The insert HTML form is treated as an "Update" instead of an insert.
	public  $updateInsertID = NULL; // If updateInsert == TRUE, we need to know the ID Field to match on.
	public  $sql            = NULL; // Used in the edit table, custom sql instead of the default
	public  $submitName     = NULL; // Name of the submit button, override the default
	
	public $updateButtonText = "Update";
	public $insertButtonText = "Insert";
	
	function __construct($engine,$table) {
		
		if (!($engine instanceof EngineCMS)) {
			return(FALSE);
		}
		
		$this->table  = $table;
		$this->engine = $engine;
	}
	
	function __destruct() {
	}
	
	/* Valid Types
	text : input type="text"
	select : select box
	plainText : plainText, not an input or database field. Can contain HTML. 
	            Plain text can contain field place holders between {$field} 
	            that will be replaced with the current row value when replacing 
	            in the edit table
	hidden : input type="hidden" ... All hidden fields are written in the column
	         of the first visible field. 
	radio : input type="radio" ... not displayed on the "insert" form. The 
	        one that is selected will return "1" all other rows will return "0";
	date : returns date in unixtime. 
	textarea : 
	wysiwyg : wysiwyg canvas object
	yesNoText : text input with "yes" or "no" corrisponding to "1", "TRUE", TRUE 
	            or "0", "FALSE", FALSE respectively from the table
	            Only valid for Table Display
	*/
	/* 
	'matchOn' does no sanitizing, expects everything to be sanitized already
	*/
	/*
	$options is used for select boxes. an array of arrays is expected. Each 
	index should have "value", "field", optionally "selected" can be set to TRUE || FALSE
	
	can also be used for textareas. height and width are valid options, and expect integers. 
	*/
	public function addField($field,$label=NULL,$dupes=FALSE,$blank=FALSE,$email=FALSE,$disabled=FALSE,$size="40",$type="text",$options=NULL,$readonly=FALSE,$value=NULL,$matchOn=NULL,$validate=NULL) {
		
		if (is_array($field)) {
			
			if (!isset($field['field'])) {
				return(FALSE);
			}
			
			$label    = (isset($field['label']))?$field['label']:$label;
			$email    = (isset($field['email']))?$field['email']:$email;
			$disabled = (isset($field['disabled']))?$field['disabled']:$disabled;
			$size     = (isset($field['size']))?$field['size']:$size;
			$dupes    = (isset($field['dupes']))?$field['dupes']:$dupes;
			$blank    = (isset($field['blank']))?$field['blank']:$blank;
			$type     = (isset($field['type']))?$field['type']:$type;
			$options  = (isset($field['options']))?$field['options']:$options;
			$readonly = (isset($field['readonly']))?$field['readonly']:$readonly;
			$value    = (isset($field['value']))?$field['value']:$value;
			$matchOn  = (isset($field['matchOn']))?$field['matchOn']:$matchOn;
			$validate = (isset($field['validate']))?$field['validate']:$validate;
			$field    = $field['field'];
		}

		if ($type == "date") {
			$this->dateInputs = TRUE;
		}
		else if ($type == "wysiwyg") {
			$this->wysiwygInputs = TRUE;
		}
		else if ($type == "multiselect") {
			$this->multiSelect = TRUE;
		}
		
		$temp = array(
			'field'    => $field,
			'label'    => $label,
			'email'    => $email,
			'disabled' => $disabled,
			'size'     => $size,
			'dupes'    => $dupes,
			'blank'    => $blank,
			'type'     => $type,
			'options'  => $options,
			'readonly' => $readonly,
			'value'    => $value,
			'matchOn'  => $matchOn,
			'validate' => $validate
			);

		if ($type == "hidden") {
			$this->hiddenFields[] = $temp;
			return(TRUE);
		}

		$this->fields[] = $temp;
		
		return(TRUE);
	}
	
	public function removeField($field) {
		$remove = NULL;
		foreach ($this->fields as $I=>$V) {
			if ($V['field'] == $field) {
				$remove = $I;
				break;
			}
		}
		foreach ($this->hiddenFields as $I=>$V) {
			if ($V['field'] == $field) {
				$remove = $I;
				break;
			}
		}
		
		if (isnull($remove)) {
			return FALSE;
		}
		
		unset($this->fields[$remove]);
		$this->fields = array_values($this->fields);
		
		return(TRUE);
	}
	
	public function disableField($field) {
		$disable = NULL;
		foreach ($this->fields as $I=>$V) {
			if ($V['field'] == $field) {
				$disable = $I;
				break;
			}
		}
		if (isnull($disable)) {
			foreach ($this->hiddenFields as $I=>$V) {
				if ($V['field'] == $field) {
					$disable = $I;
					break;
				}
			}
		}
		
		if (isnull($disable)) {
			return FALSE;
		}
		
		$this->fields[$disable]['disabled'] = TRUE;
		
		return(TRUE);
	}
	
	public function disableAllFields() {
		foreach ($this->fields as $I) {
			$this->disableField($I['field']);
		}
		foreach ($this->hiddenFields as $I) {
			$this->disableField($I['field']);
		}
		return TRUE;
	}
	
	public function displayInsertForm($addGet=TRUE) {
		$queryString = "";
		if ($addGet === TRUE) {
			$queryString = "?".$_SERVER['QUERY_STRING'];
		}
		
		$submitButtonName = (isnull($this->submitName))?$this->table.'_submit':$this->submitName;
		
		$error = (isset($this->engine->cleanPost['MYSQL'][$submitButtonName]) && $this->repost === TRUE)?TRUE:FALSE;
		
		$output  = "";
		$output .= "<!-- engine Instruction break -->".'<!-- engine Instruction displayTemplateOff -->'."<!-- engine Instruction break -->";
		$output .= "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\">";
		$output .= sessionInsertCSRF();
		foreach ($this->hiddenFields as $I) {
			$output .= '<input type="hidden" name="'.$I['field'].'_insert" value="'.htmlentities($I['value']).'" />';
		}
		
		$output .= "<table class=\".engineListInsertTable\">";
		foreach ($this->fields as $I) {
			
			if ($I['type'] == "plainText" || $I['type'] == "radio") {
				continue;
			}
			
			$output .= "<tr>";
			$output .= "<td>";
			
			// If the label is for a WYSIWYG canvas add a "we_" to the ID, for the javascript find
			$output .= "<label for=\"".$I['field']."_insert\">";
			$output .= ($I['blank'] == FALSE)?"<span class=\"requiredField\">":"";
			$output .= $I['label'];
			$output .= ($I['blank'] == FALSE)?"</span>":"";
			$output .= ": </label>";
			$output .= "</td>";
			$output .= "<td>";
			if ($I['type'] == "text" || $I['type'] == "date") {
				
				$value = ($error === TRUE)?$this->engine->cleanPost['HTML'][$I['field'].'_insert']:"";
				if (is_empty($value) && !isnull($I['value'])) {
					$value = htmlentities($I['value']);
				}
				
				$output .= "<input type=\"text\" name=\"".$I['field']."_insert\" id=\"".$I['field']."_insert\"";
				$output .= ' size="'.$I['size'].'"';
				$output .= ($I['type'] == "date")?" class=\"date_input\"":"";
				//Handle if the form is being reposted after a failed submit attempt
				$output .= " value=\"".$value."\""; 
				$output .= ($I['readonly'] === TRUE)?" readonly ":"";
				$output .= ($I['disabled'] === TRUE)?" disabled ":"";
				$output .= " />";
			}
			else if ($I['type'] == "select") {
				
				$output .= "<select name=\"".$I['field']."_insert\"";
				$output .= ($I['readonly'] === TRUE)?" readonly ":"";
				$output .= ($I['disabled'] === TRUE)?" disabled ":"";
				$output .= ">";
				foreach ($I['options'] as $option) {
					
					$output .= "<option value=\"".htmlentities($option['value'])."\"";
					
					//Handle if the form is being reposted after a failed submit attempt
					$output .= ($error === TRUE && $this->engine->cleanPost['HTML'][$I['field'].'_insert'] == $option['value'])?" selected":"";
					$output .= ($error === FALSE && isset($option['selected']) && $option['selected'] === TRUE)?" selected":"";

					$output .= ">".htmlentities($option['label'])."</option>";
				}
				$output .= "</select>";
			}
			else if ($I['type'] == "textarea") {
				$output .= "<textarea name=\"".$I['field']."_insert\"";
				if (isset($I['options']['width'])) {
					$output .= " cols=\"".(htmlSanitize($I['options']['width']))."\"";
				}
				if (isset($I['options']['height'])) {
					$output .= " rows=\"".(htmlSanitize($I['options']['height']))."\"";
				}
				$output .= ($I['readonly'] === TRUE)?" readonly ":"";
				$output .= ($I['disabled'] === TRUE)?" disabled ":"";
				$output .= ">";
				$output .= ($error === TRUE)?$this->engine->cleanPost['HTML'][$I['field'].'_insert']:"";
				$output .= ($error === FALSE)?htmlentities($I['value']):"";
				$output .= "</textarea>";
			}
			else if ($I['type'] == "wysiwyg") {
				$output .= '<canvas id="'.$I['field'].'_insert" name="'.$I['field'].'"_insert" class="engineWYSIWYG '.$I['field'].'_insert">';
				$output .= ($error === TRUE)?$this->engine->cleanPost['RAW'][$I['field'].'_insert']:$I['value'];
				$output .= '</canvas>';
				
				$output .= '<script type="text/javascript">wysiwygInit(\''.$I['field'].'_insert\');</script>';
				
			}
			else if ($I['type'] == "multiselect") {
				
				$multiSelectError = FALSE;
				
				if (!isset($I['options']['valueTable'])) {
					$output .= webhelper_errorMsg("Value table not set");
					$multiSelectError = TRUE;
				}
				if (!isset($I['options']['valueDisplayID'])) {
					$output .= webhelper_errorMsg("valueDisplayID not set");
					$multiSelectError = TRUE;
				}
				if (!isset($I['options']['valueDisplayField'])) {
					$output .= webhelper_errorMsg("valueDisplayField table not set");
					$multiSelectError = TRUE;
				}
				
				if ($multiSelectError === FALSE) {
					
					if ($error === TRUE) {
						if (isset($this->engine->cleanPost['MYSQL'][$I['field']])) {
							 $I['options']['select'] = implode(",",$this->engine->cleanPost['MYSQL'][$I['field']]);
						}
					}
					
					$attPairs = array();
					$attPairs['select']   = (isset($I['options']['select']))?$I['options']['select']:NULL;
					$attPairs['table']    = $I['options']['valueTable'];
					$attPairs['valuecol'] = $I['options']['valueDisplayID'];
					$attPairs['labelcol'] = $I['options']['valueDisplayField'];
					$attPairs['orderby']  = (isset($I['options']['orderBy']))?$I['options']['orderBy']:NULL;
				
					$output .= emod_msww($attPairs,$this->engine);
				}
			}
			else  {
				$output .= webhelper_errorMsg("Invalid Type");
			}
			$output .= "</td>";
			$output .= "</tr>";
		}
		$output .= "</table>";
		
		if ($this->noSubmit === FALSE) {
			$output .= "<input type=\"submit\" name=\"".$submitButtonName."\" value=\"";
			$output .= ($this->updateInsert === TRUE)?$this->updateButtonText:$this->insertButtonText;
			$output .= '"';
			if ($this->multiSelect === TRUE) {
				$output .= 'onclick="emod_msww_entrySubmit()"';
			}
			$output .= " />";
		}
		
		$output .= "</form>";
		$output .= "<!-- engine Instruction break -->".'<!-- engine Instruction displayTemplateOn -->'."<!-- engine Instruction break -->";
		
		if ($this->dateInputs) {
			$output .= "<script>$($.date_input.initialize);</script>";
		}

		$output .= "";
		
		return($output);
		
	}
	
	public function displayEditTable($addGet=TRUE,$debug=FALSE) {
		$queryString = "";
		if ($addGet === TRUE) {
			$queryString = "?".$_SERVER['QUERY_STRING'];
		}
		
		//Build "ORDER BY"
		if (isnull($this->orderBy) && $this->fields[0]['type'] != "plainText" ) {
			$this->orderBy = "ORDER BY ".$this->engine->openDB->escape($this->fields[0]['field']);
		}
		else if (!isnull($this->orderBy)) {
			$this->orderBy = $this->engine->openDB->escape($this->orderBy);
		}
		else {
			$this->orderBy = "";
		}

		if (!isnull($this->sql)) {
			$sql = $this->sql;	
		}
		else {
			$sql = sprintf("SELECT * FROM %s %s %s",
				$this->engine->openDB->escape($this->table),
				$this->whereClause,
				$this->orderBy
				);
		}

		if ($debug === TRUE) {
			print "SQL: ".$sql."<br />";
		}

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);

		if (!$sqlResult['result']) {
			if ($debug === TRUE) {
				print "SQL Error: ".$sqlResult['error']."<br />";
			}
			//return webHelper_errorMsg("Error fetching list for Edit table.");
			return webHelper_errorMsg("SQL Error");
		}

		$cols = count($this->fields);
		$colspan = $cols;
		$colspan += ($this->deleteBox  === TRUE)?1:0;
		$colspan += ($this->numberRows === TRUE)?1:0;

		$output = "";

		if ($this->sortable === TRUE) {
			global $engineVars;
			$output .= "<script src=\"".$engineVars['sortableTables']."\" type=\"text/javascript\"></script>";
		}
		if ($this->dragOrdering === TRUE) {
			global $engineVars;
			$output .= "<script src=\"".$engineVars['tablesDragnDrop']."\" type=\"text/javascript\"></script>";
		}

		$output .= "\n<!-- engine Instruction break -->".'<!-- engine Instruction displayTemplateOff -->'."\n<!-- engine Instruction break -->";
		$output .= "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\" onsubmit=\"return listObjDeleteConfirm(this);\">";
		$output .= sessionInsertCSRF();
		$output .= "<table border=\"0\" cellpadding=\"1\" cellspacing=\"0\" id=\"".$this->engine->openDB->escape($this->table)."_table\"";
		
		$output .= " class=\"engineListDisplayTable";
		if ($this->sortable === TRUE) {
			$output .= " sortable";
		}
		$output .= "\"";
		
		$output .= ">";
		$output .= "<thead>";
		$output .= "<tr>";
		
		if ($this->numberRows === TRUE) {
			$output .= "<th style=\"width: 25px;\">#</th>";
		}

		for($I=0;$I<(int)$cols;$I++) {
			$output .= "<th style=\"text-align: left;\">".$this->fields[$I]['label']."</th>";
		}

		if ($this->deleteBox === TRUE) {
			$output .= "<th style=\"width: 100px;\">Delete</th>";
		}
		
		$output .= "</tr>";
		$output .= "</thead>";
		
		$output .= "<tbody>";
		
		$numberRowsCount = 1;
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_BOTH)) {
			$output .= "<tr";
			if ($this->rowStriping === TRUE) {
				$output .= (is_odd($numberRowsCount))?" class=\"oddrow\"":" class=\"evenrow\"";
			}
			if ($this->dragOrdering === TRUE) {
				$output .= " id=\"".$row[0]."\"";
			}
			$output .= ">";
			
			if ($this->numberRows === TRUE) {
				$output .= "<td class=\"alignRight\">";
				$output .= $numberRowsCount .".";
				$output .= "</td>";
			}
			$numberRowsCount++;

			for($I=0;$I<(int)$cols;$I++) {
				$output .= "<td ";
				
				if ($this->sortable === TRUE && $this->fields[$I]['type'] != "plainText") {
					$output .= "sorttable_customkey=\"".htmlentities($row[$this->fields[$I]['field']])."\"";
				}
				
				$output .= ">";
				
				if ($I == 0) {
					$output .= "<input type=\"hidden\" name=\"check_".$row[0]."\" value=\"".$row[0]."\" />";
					
					foreach ($this->hiddenFields as $hiddenField) {
					
						$output .= "<input type=\"hidden\" name=\"".$hiddenField['field']."_".$row[0]."\" id=\"".$hiddenField['field']."_".$row[0]."\" class=\"".$hiddenField['field']."\" value=\"".htmlentities($row[$hiddenField['field']])."\" ";
						$output .= "/>";

					}
					
				}
				
				if ($this->fields[$I]['type'] == "text") {
					
					$value = $row[$this->fields[$I]['field']];
					if (!isnull($this->fields[$I]['matchOn'])) {
						$sql = "SELECT ".$this->engine->openDB->escape($this->fields[$I]['matchOn']['field'])." FROM ".$this->engine->openDB->escape($this->fields[$I]['matchOn']['table'])." WHERE ".$this->engine->openDB->escape($this->fields[$I]['matchOn']['key'])."='".$this->engine->openDB->escape($row[$this->fields[$I]['field']])."'";
						
						$this->engine->openDB->sanitize = FALSE;
						$matchOnSqlResult               = $this->engine->openDB->query($sql);
						$matchOnValueResult             = mysql_fetch_array($matchOnSqlResult['result'], MYSQL_BOTH);
						
						if (isset($this->fields[$I]['matchOn']['field'])) {
							$value = $matchOnValueResult[$this->fields[$I]['matchOn']['field']];
						}
					}
					
					$output .= "<input type=\"text\" size=\"".$this->fields[$I]['size']."\" name=\"".$this->fields[$I]['field']."_".$row[0]."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']."";
					$output .= "\" value=\"".htmlentities($value)."\" ";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= "/>";
				}
				else if ($this->fields[$I]['type'] == "yesNoText") {
					
					switch($row[$this->fields[$I]['field']]) {
						case "1":
						case TRUE:
						case "TRUE":
						    $value = "Yes";
							break;
						case "0":
						case FALSE:
						case "FALSE":
						    $value = "No";
						    break;
						default:
						    $value = "Data Error";
						    break;
					}
					
					$output .= "<input type=\"text\" size=\"".$this->fields[$I]['size']."\" name=\"".$this->fields[$I]['field']."_".$row[0]."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']."";
					$output .= "\" value=\"".htmlentities($value)."\" ";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= "/>";
				}
				else if ($this->fields[$I]['type'] == "date") {
					
					if ($row[$this->fields[$I]['field']] == "0") {
						$row[$this->fields[$I]['field']] = "";
					}
					
					$output .= "<input type=\"text\" size=\"".$this->fields[$I]['size']."\" name=\"".$this->fields[$I]['field']."_".$row[0]."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']." date_input\" value=\"".(!is_empty($row[$this->fields[$I]['field']])?htmlentities(date("m/d/Y",$row[$this->fields[$I]['field']])):"")."\" ";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= "/>";
				}
				else if ($this->fields[$I]['type'] == "select") {

					$output .= "<select name=\"".$this->fields[$I]['field']."_".$row[0]."\"";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= ">";
					foreach ($this->fields[$I]['options'] as $option) {

						$output .= "<option value=\"".htmlsanitize($option['value'])."\"";
						$output .= ($row[$this->fields[$I]['field']] == $option['value'])?" selected":"";
						$output .= ">".htmlsanitize($option['label'])."</option>";
					}
					$output .= "</select>";
				}
				else if ($this->fields[$I]['type'] == "radio") {
					$output .= "<input type=\"radio\" name=\"".$this->fields[$I]['field']."\" value=\"".htmlsanitize($row[0])."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']."\"";
					$output .= ($row[$this->fields[$I]['field']] == 1)?" checked":"";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= " />";
				}
				else if ($this->fields[$I]['type'] == "plainText") {
					$tempField = $this->fields[$I]['field'];
					preg_match_all('/{(\w+)}/', $tempField, $matches);
					foreach ($matches[1] as $mIndex=>$mValue) {
						foreach ($this->fields as $field) {
							if ($field['field'] == $mValue) {
								$tempValue = $row[$mValue];
							}
						}
						foreach ($this->hiddenFields as $field) {
							if ($field['field'] == $mValue) {
								$tempValue = $row[$mValue];
							}
						}
						$tempField = preg_replace('/{'.$mValue.'}/',$tempValue,$tempField);
					}
					$output .= $tempField;
				}
				else if ($this->fields[$I]['type'] == "textarea") {
					$value = $row[$this->fields[$I]['field']];
					if (!isnull($this->fields[$I]['matchOn'])) {
						$sql = "SELECT ".$this->engine->openDB->escape($this->fields[$I]['matchOn']['field'])." FROM ".$this->engine->openDB->escape($this->fields[$I]['matchOn']['table'])." WHERE ".$this->engine->openDB->escape($this->fields[$I]['matchOn']['key'])."='".$this->engine->openDB->escape($row[$this->fields[$I]['field']])."'";
						
						$this->engine->openDB->sanitize = FALSE;
						$matchOnSqlResult               = $this->engine->openDB->query($sql);
						$matchOnValueResult             = mysql_fetch_array($matchOnSqlResult['result'], MYSQL_BOTH);
						
						if (isset($this->fields[$I]['matchOn']['field'])) {
							$value = $matchOnValueResult[$this->fields[$I]['matchOn']['field']];
						}
					}
					
					$output .= "<textarea type=\"text\" rows=\"1\" cols=\"".$this->fields[$I]['size']."\" name=\"".$this->fields[$I]['field']."_".$row[0]."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']."";
					$output .= "\""; 
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= ' onfocus="convert2Textarea(this);" onblur="convert2TextInput(this)"';
					$output .= ">";
					$output .= htmlentities($value);
					$output .= "</textarea>";
				}
				else if ($I['type'] == "multiselect") {

					$output .= webhelper_errorMsg("Multi Select type not supported in table edit");
				}
				
				$output .= "</td>";
			}

			$output .= "<td class=\"alignCenter\">";
			if ($this->deleteBox === TRUE) {
				$output .= "<input type=\"checkbox\" name=\"delete[]\" class=\"delete\" value=\"".$row[0]."\" />";
			}
			$output .= "</td>";
			$output .= "</tr>";

		}
		$output .= "</tbody>";
		$output .= "</table>";
		if ($this->noSubmit === FALSE) {
			$submitButtonName = (isnull($this->submitName))?$this->table.'_update':$this->submitName;
			$output .= "<input type=\"submit\" value=\"Update\" name=\"".$submitButtonName."\" />";
		}
		$output .= "</form>";
		$output .= "\n<!-- engine Instruction break -->".'<!-- engine Instruction displayTemplateOn -->'."\n<!-- engine Instruction break -->";

		if ($this->dateInputs) {
			$output .= "<script>$($.date_input.initialize);</script>";
		}

		if ($this->dragOrdering === TRUE) {
			$output .= '<script type="text/javascript">';
			$output .= "var table = document.getElementById('".$this->engine->openDB->escape($this->table)."_table');";
			$output .= 'var tableDnD = new TableDnD();';
			$output .= 'tableDnD.init(table);';
			$output .= '</script>';
		}

		return($output); 
	}
	
	public function insert() {
		
		$error = "";
		$haveMultiSelect   = FALSE;
		$multiSelectFields = array();
		
		if ($this->updateInsert === TRUE) {
			
			if (isnull($this->updateInsertID)) {
				 return webHelper_errorMsg("Update Error: updateInsertID was not defined");
			}
			else if (!isset($this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"])) {
				return webHelper_errorMsg("Update Error: updateInsertID was not set");
			}
		}
		
		foreach ($this->fields as $I) {
			
			if ($I['type'] == "plainText") {
				continue;
			}
			
			if ($I['type'] == "multiselect") {
				$haveMultiSelect = TRUE;
				$multiSelectFields[] = $I;
			}
			
			// Check for blanks
			// If the field is NOT allowed to have blanks
			if ($I["blank"] === FALSE) {
				// perform a blank check. 
				if ($I['type'] != "multiselect") {
					if (is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'],FALSE)) {
						$error .= webHelper_errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
						continue;
					}

					if ($I['type'] == "select" && $this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] == "NULL") {
						$error .= webHelper_errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
						continue;
					}
				}
				else if ($I['type'] == "multiselect" && !isset($this->engine->cleanPost['MYSQL'][$I['field']])) {
					$error .= webHelper_errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
					continue;
				}
			}
			
			// Change dates into unix time stamps
			if ($I['type'] == "date") {
				list($m,$d,$y) = explode("/",$this->engine->cleanPost['MYSQL'][$I['field'].'_insert']);
				$this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] = mktime(0,0,0,$m,$d,$y);
			}
			
			//Do the Email Check
			//check if the current field should have a valid email address
			if ($I["email"] === TRUE) {
				// If its not empty, and it does not have a valid email address, continue
				if(!empty($this->engine->cleanPost['MYSQL'][$I["field"].'_insert']) && !validateEmailAddr($this->engine->cleanPost['MYSQL'][$I["field"].'_insert'])) {
					$error .= webHelper_errorMsg("Invalid E-Mail Address: ". htmlentities($this->engine->cleanPost['MYSQL'][$I["field"].'_insert']).". Other records may be updated still.");
					continue;
				}
			}
			
			// Check for duplicates
			// If the field is NOT allowed to have dupes
			if ($I["dupes"] === FALSE) {
				
				// Irrelevant to multiselect 
				if ($I['type'] == "multiselect") {
					continue;
				}
				
				// perform a dupe check. 
				if ($I["email"] === TRUE && $this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] == "dev@null.com") {
					// dev@null.com is a special case email. We allow duplicates of it
				}
				else if ($this->duplicateCheck($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'],$I["field"])) {
					$error .= webHelper_errorMsg("Entry, ".htmlentities($this->engine->cleanPost['MYSQL'][$I['field'].'_insert']).", already in database. Other records may be updated still.");
					continue;
				}
			}
			
			if (isset($I['validate'])) {
				
				// Irrelevant to multiselect 
				if ($I['type'] == "multiselect") {
					continue;
				}
				
				if ($I['blank'] === TRUE && is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'])) {
					// skip validation if field is blank and allowed to be blank
					//continue;
				}
				else {
					$validateResult = $this->validateData($I['validate'],$this->engine->cleanPost['MYSQL'][$I['field'].'_insert']);
					if ($validateResult !== FALSE) {
						$error .= $validateResult;
						continue;
					}
				}
			}
			
			$this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] = stripCarriageReturns($this->engine->cleanPost['MYSQL'][$I['field'].'_insert']);
		}
		
		if (!is_empty($error)) {
			return($error);
		}

		if ($this->updateInsert === FALSE) {
			$sql = sprintf("INSERT INTO %s (%s) VALUES(%s)",
				$this->engine->openDB->escape($this->table),
				$this->buildFieldListInsert(),
				$this->buildFieldValueInsert()			
				);
		}
		else {
			
			$sql = sprintf("UPDATE %s SET %s WHERE %s='%s'",
				$this->engine->openDB->escape($this->table),
				$this->buildInsertUpdateString(),
				$this->engine->openDB->escape($this->updateInsertID),
				$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"]
				);
					
		}

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);
		
		$output = "";
		
		if (!$sqlResult['result']) {
			$output .= webHelper_errorMsg("Insert Error: ");
			
		}
		else {
			$output .= webHelper_successMsg("Entry successfully added to the database.");
			
			// Drop the Insert ID into a local variable suitable for framing
			if ($this->updateInsert === FALSE) {
				$this->engine->localVars("listObjInsertID",$sqlResult['id']);
			}
			else {
				$this->engine->localVars("listObjInsertID",$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"]);
			}
			
			// Clear the submit button name on a success submit, so we don't repopulate 
			// the form
			if ($this->repost === TRUE) {
				$submitButtonName = (isnull($this->submitName))?$this->table.'_submit':$this->submitName;
				$this->engine->cleanPost['MYSQL'][$submitButtonName] = NULL;
			}
			
			// If we have any multiselect types, deal with them here
			if ($haveMultiSelect === TRUE) {
				if ($this->updateInsert === FALSE) {
					$linkObjectID = $sqlResult['id'];
				}
				else {
					$linkObjectID = $this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"];
				}	
				
				foreach ($multiSelectFields as $I) {
					
					$sql = sprintf("DELETE FROM %s WHERE %s='%s'",
						$this->engine->openDB->escape($I['options']['linkTable']),
						$this->engine->openDB->escape($I['options']['linkObjectField']),
						$linkObjectID
						);

					$this->engine->openDB->sanitize = FALSE;
					$sqlResult                      = $this->engine->openDB->query($sql);
					
					if (isset($this->engine->cleanPost['MYSQL'][$I['field']])) {
						foreach ($this->engine->cleanPost['MYSQL'][$I['field']] as $K=>$value) {
							$sql = sprintf("INSERT INTO %s(%s, %s) VALUES('%s','%s')",
								$this->engine->openDB->escape($I['options']['linkTable']),
								$this->engine->openDB->escape($I['options']['linkValueField']),
								$this->engine->openDB->escape($I['options']['linkObjectField']),
								$value,
								$linkObjectID
								);

							$this->engine->openDB->sanitize = FALSE;
							$sqlResult                = $this->engine->openDB->query($sql);
							
							if (!$sqlResult['result']) {
								$output .= webHelper_errorMsg("MultiSelect Insert Error: ".$sqlResult['error']);
							}
						}
					}
				}
			}
			
		}

		if ($this->dragOrdering === TRUE) {
			$sql = sprintf("UPDATE %s SET position=%s WHERE ID=%s",
				$this->engine->openDB->escape($this->table),
				((int)$sqlResult['id'] - 1),
				$sqlResult['id']
			);
			$this->engine->openDB->sanitize = FALSE;
			$sqlResult = $this->engine->openDB->query($sql);
		}

		return($output);
	}
	
	public function update($returnBool=FALSE) {

		$error           = array();
		$error["error"]  = FALSE;
		$error['string'] = "";
		$output = "";

		if (isset($this->engine->cleanPost['MYSQL']['delete'])) {
			foreach($this->engine->cleanPost['MYSQL']['delete'] as $value) {

				$sql = sprintf("DELETE FROM %s WHERE ID=%s",
					$this->engine->openDB->escape($this->table),
					$this->engine->openDB->escape($value)
					);

				$this->engine->openDB->sanitize = FALSE;
				$sqlResult = $this->engine->openDB->query($sql);

				if (!$sqlResult['result']) {
					$output .= webHelper_errorMsg("Delete Error.");
					$error["error"]   = TRUE;
				}

			}
		}

		$sql = sprintf("SELECT * FROM %s",
			$this->engine->openDB->escape($this->table)
			);

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);

		if (!$sqlResult['result']) {
			$output .= webHelper_errorMsg("Error fetching table entries");
			$error["error"]   = TRUE;
			//$sqlResult['error']
		}

		while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_BOTH)) {

			//grab the first column in the current row, if it is set, throw it in $temp
			if (!isset($this->engine->cleanPost['MYSQL']["check_".$row[0]])) {
				continue;
			}

			
			// FOr each defined field
			foreach ($this->fields as $I) {
				
				if ($I['type'] == "plainText") {
					continue;
				}
				
				//Check for blanks
				// If the field is NOT allowed to have blanks
				if ($I["blank"] === FALSE && $I['disabled'] === FALSE) {
					// perform a blank check. Continue to the next row in the database if there is a blank. 
					if (is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]],FALSE)) {
						$output .= webHelper_errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
						$error["error"]   = TRUE;
						continue 2;
					}
				}
				
				// Change dates into unix time stamps
				if ($I['type'] == "date") {
					
					if (!is_empty($this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]])) {
						list($m,$d,$y) = explode("/",$this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]]);
						$this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]] = mktime(0,0,0,(int)$m,(int)$d,(int)$y);
					}
					else {
						$this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]] = "";
					}
				}
				
				//Do the Email Check
				//check if the current field should have a valid email address
				if ($I["email"] === TRUE && $I['disabled'] === FALSE) {
					// If its not empty, and it does not have a valid email address, continue next database row
					if(!empty($this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]]) && !validateEmailAddr($this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]])) {
						$output .= webHelper_errorMsg("Invalid E-Mail Address: ". htmlentities($this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]]).". Other records may be updated still.");
						$error["error"]   = TRUE;
						continue 2;
					}
				}
				
				// Check for duplicates
				// If the field is NOT allowed to have dupes
				if ($I["dupes"] === FALSE && $I['disabled'] === FALSE) {
					
					
					// We have to make sure that update insert is true, so that Capital and Lower Case letters 
					// don't scream on changes. Saving the old, just in case it was set. 
					$tempUI = $this->updateInsert;
					$tempID = $this->updateInsertID;
					$tempPT = (isset($this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"]))?$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"]:NULL;
					
					$this->updateInsert   = TRUE;
					$this->updateInsertID = "ID";
					$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"] = $row[0];			
							
					// perform a dupe check. Continue to the next row in the database if there is a dupe in that field. 
					if ($row[$I["field"]] != $this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]] && $this->duplicateCheck($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]],$I["field"])) {
						$output .= webHelper_errorMsg("Entry, ".htmlentities($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]]).", already in database. Other records may be updated still.");
						$error["error"]   = TRUE;
						
						// Set updateInsert back to original Values
						$this->updateInsert   = $tempUI;
						$this->updateInsertID = $tempID;
						$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"] = $tempPT;
						
						continue 2;
					}
					
					// Set updateInsert back to original Values
					$this->updateInsert   = $tempUI;
					$this->updateInsertID = $tempID;
					$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"] = $tempPT;
					
				}
				
				if (isset($I['validate'])) {
					if ($I['blank'] === TRUE && is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]])) {
						// skip validation if field is blank and allowed to be blank
						// continue;
					}
					else {
						$validateResult = $this->validateData($I['validate'],$this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]]);
						if ($validateResult !== FALSE) {
							$output .= $validateResult;
							$error["error"]   = TRUE;
							continue 2;
						}
					}
				}
				
				$this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]] = stripCarriageReturns($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]]);
			
			}

			$sql = sprintf("UPDATE %s SET %s WHERE ID='%s'",
				$this->engine->openDB->escape($this->table),
				$this->buildUpdateString($row[0]),
				$row[0]
				);

			$this->engine->openDB->sanitize = FALSE;
			$sqlResult2 = $this->engine->openDB->query($sql);

			if (!$sqlResult2['result']) {
				$output .= webHelper_errorMsg("Update Error.");
				$error["error"]   = TRUE;
				//$output .= $sql;
			}

		}

		if(empty($output)) {
			$output = webHelper_successMsg("Database successfully updated.");
		}

		if ($returnBool === TRUE) {
			$error["string"] = $output;
			return($error);
		}
		return($output);
	}
	
	public function haveDeletes() {
		$deleteIDs = array();
		if (isset($this->engine->cleanPost['MYSQL']['delete'])) {
			foreach($this->engine->cleanPost['MYSQL']['delete'] as $value) {
				$deleteIDs[] = $value;
			}
			return($deleteIDs);
		}
		return(FALSE);
	}
	
	private function duplicateCheck($new,$col) {
		
		$idMatch = "";
		if ($this->updateInsert === TRUE) {
			$idMatch = sprintf(" AND %s!='%s'",
				$this->engine->openDB->escape($this->updateInsertID),
				$this->engine->openDB->escape($this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"])
			);
		}
		$sql = sprintf("SELECT * FROM %s WHERE %s='%s'%s",
			$this->engine->openDB->escape($this->table),
			$this->engine->openDB->escape($col),
			$this->engine->openDB->escape($new),
			$idMatch
			);
			
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);

		//We should probably do a SQL check here
		if (!$sqlResult['result']) {
			return(TRUE);
		}
		
		if (mysql_num_rows($sqlResult['result']) == 0) {
			return(FALSE);
		}

		return(TRUE);
	}
	
	private function buildUpdateString($row) {

		$temp = array();
		foreach ($this->fields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			if ($I['type'] == "plainText") {
				continue;
			}
			
			if ($I['type'] == "radio") {
				if (isset($this->engine->cleanPost['MYSQL'][$I["field"]]) && 
				          $this->engine->cleanPost['MYSQL'][$I["field"]] == $row) {
					$this->engine->cleanPost['MYSQL'][$I["field"]."_".$row] = 1;
				}
				else {
					$this->engine->cleanPost['MYSQL'][$I["field"]."_".$row] = 0;
				}
			}
			
			$temp[] = $this->engine->openDB->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_".$row]."'";
		}
		foreach ($this->hiddenFields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			$temp[] = $this->engine->openDB->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_".$row]."'";
		}
		$output = implode(",",$temp);
		return($output);

	}
	
	private function buildInsertUpdateString() {

		$temp = array();
		foreach ($this->fields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			if ($I['type'] == "plainText" || $I['type'] == "radio" || $I['type'] == "multiselect") {
				continue;
			}
			
			$temp[] = $this->engine->openDB->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_insert"]."'";
		}
		foreach ($this->hiddenFields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			$temp[] = $this->engine->openDB->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_insert"]."'";
		}
		$output = implode(",",$temp);
		return($output);

	}
	
	private function buildFieldListInsert() {
		$temp = array();
		foreach ($this->fields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			if ($I['type'] == "plainText" || $I['type'] == "radio" || $I['type'] == "multiselect") {
				continue;
			}
			$temp[] = $I["field"];
		}
		foreach ($this->hiddenFields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			$temp[] = $I["field"];
		}
		$output = implode(",",$temp);
		return($output);

	}

	private function buildFieldValueInsert() {

		$temp = array();
		foreach ($this->fields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			if ($I['type'] == "plainText" || $I['type'] == "radio" || $I['type'] == "multiselect" ) {
				continue;
			}
			
			$temp[] = "'".$this->engine->cleanPost['MYSQL'][$I["field"]."_insert"]."'";
		}
		foreach ($this->hiddenFields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			$temp[] = "'".$this->engine->cleanPost['MYSQL'][$I["field"]."_insert"]."'";
		}
		$output = implode(",",$temp);
		return($output);

	}

	private function validateData($validate,$data) {
		
		$error = "";
		
		if ($validate == "url") {
			if (!validURL($data)) {
				$error .= webHelper_errorMsg("Entry, ".htmlentities($data).", not a valid URL.");
			}
		}
		else if ($validate == "email") {
			if (!validateEmailAddr($data)) {
				$error .= webHelper_errorMsg("Entry, ".htmlentities($data).", not a valid Email Address.");
			}
		}
		else if ($validate == "phone") {
			if (!validPhoneNumber($data)) {
				$error .= webHelper_errorMsg("Entry, ".htmlentities($data).", not a valid Phone.");
			}
		}
		else if ($validate == "ipaddr") {
			if (!validIPAddr($data)) {
				$error .= webHelper_errorMsg("Entry, ".htmlentities($data).", not a valid IP Address Range.");
			}
		}
		else {

			$regexp = $validate;

			switch($validate) {
				case "integer":
				$regexp = "/^[0-9]+$/";
				break;

				case "integerSpaces":
				$regexp = "/^[0-9\ ]+$/";
				break;

				case "alphaNumeric":
				$regexp = "/^[a-zA-Z0-9\-\_\ ]+$/";
				break;

				case "alphaNumericNoSpaces":
				$regexp = "/^[a-zA-Z0-9\-\_]+$/";
				break;

				case "alpha":
				$regexp = "/^[a-zA-Z\ ]+$/";
				break;

				case "alphaNoSpaces";
				$regexp = "/^[a-zA-Z]+$/";
				break;

				case "noSpaces";
				$regexp = "/^[^\ ]+$/";
				break;

				case "noSpecialChars";
				$regexp = "/^[^\W]+$/";
				break;

				default:
				break;
			}


			$return = @preg_match($regexp,$data);

			// if the regular expression fails, returns false. If there is no match, returns "0" otherwise "1"
			if ($return === FALSE) {
				$error .= webHelper_errorMsg("Invalid Regular Expression Passed.");
				$return = -1;
			}
			else if ($return == 0) {
				$error .= webHelper_errorMsg("Entry, ".htmlentities($data).", is not valid.");
			}

		}

		if (is_empty($error)) {
			return(FALSE);
		}

		return($error);
	}

}



?>