<?php

class listManagement {

	private $engine        = NULL;
	private $table         = NULL;
	private $fields        = array();
	private $hiddenFields  = array();
	private $dateInputs    = FALSE;
	private $wysiwygInputs = FALSE;
	private $checkboxInput = FALSE;
	private $multiSelect   = FALSE;
	private $database      = NULL;  // database object. defaults to $engine->openDB;
	private $error         = NULL;
	
	public  $orderBy          = NULL;  // Custom sort for the edit table
	public  $numberRows       = FALSE; // Rows are numbered on the far left
	public  $numberRowsRight  = FALSE; // Rows are numbered on the far right
	public  $rowNumDelim      = ".";   // delimiter that appears after (or before for $numberRowsRight)
	public  $rowNumDelimRight = ".";   // delimiter that appears after (or before for $numberRowsRight)
	public  $sortable         = FALSE; // Edit Table is sortable by click on headers
	public  $dragOrdering     = FALSE; // Enable reordering of elements in the list, by dragging them around
	                                   // Drag and Drop ordering requires a field in the table called "position"

	public  $deleteBox      = TRUE;  // Provide a delete checkbox on far right of the edit table
	public  $deleteBoxLeft  = FALSE; // Provide a delete checkbox on far left of the edit table
	public  $rowStriping    = TRUE;  // Zebtra stripe the edit table
	public  $repost         = TRUE;  // When an item is added, if there are errors, fillout the form with previous values
	public  $noSubmit       = FALSE; // Submit button is not displayed. 
	public  $whereClause    = "";    // Where clause that is used for the edit list. This is NOT a sanitized in the module!
	public  $updateInsert   = FALSE; // The insert HTML form is treated as an "Update" instead of an insert.
	public  $updateInsertID = NULL; // If updateInsert == TRUE, we need to know the ID Field to match on.
	public  $sql            = NULL; // Used in the edit table, custom sql instead of the default
	public  $submitName     = NULL; // Name of the submit button, override the default
	
	public $helpIconDefault = "?"; // Default for the help Icon on the insert list
	
	public $primaryKey      = "ID"; // The field name of the primary key
	
	public $updateButtonText = "Update";
	public $insertButtonText = "Insert";
	
	public $deletedIDs       = array(); // After update is called, if anything is deleted its ID is stored here
	public $modifiedIDs      = array(); // If anything is updated/modified its ID is stored here
	
	public $debug           = FALSE; // If set to TRUE, will display SQL errors. MUST BE SET TO FALSE FOR 
									 // Production
	
	public $validateTypes   = array("alpha","alphaNoSpaces","alphaNumeric","alphaNumericNoSpaces","date","email","ipaddr","integer","integerSpaces","internalEmail","noSpaces","noSpecialChars","phone","url"); // A list of all the types that are in the validation function. Suitable for use in dropdown/etc ... 
	
	private $insertOnlyTypes = array("checkbox", "wysiwyg", "multiselect"); // These are not displayed in the edit table
	
	// For template matching
	public $pattern  = "/\{listObject\s+(.+?)\}/";
	public $function = "listManagement::templateMatches";
	
	public $eolChar  = "\n";
	
	function __construct($table,$database=NULL) {
		
		$this->table  = $table;
		$this->engine = EngineAPI::singleton();
		//$this->error  = new errorHandle();
	
		if (!isnull($database) && is_object($database) && get_class($database) == "engineDB") {
			$this->database = $database;
		}
		else {
			$this->database = $this->engine->openDB;
		}
	
		$this->engine->defTempPattern($this->pattern,$this->function,$this);
	
	}
	
	function __destruct() {
	}
	
	/* 
	$attPairs:
		display= insertForm || editTable
		addGet = TRUE || FALSE (default, FALSE); 
		debug = TRUE || FALSE (default, FALSE);
	*/
	public static function templateMatches($matches) {

		$engine   = EngineAPI::singleton();

		$obj      = $engine->retTempObj("listManagement");

		$attPairs = attPairs($matches[1]);

		$addGet = (isset($attPairs['addGet']) && strtolower($attPairs['addGet']) == "false")?FALSE:TRUE;
		$debug  = (isset($attPairs['debug'])  && strtolower($attPairs['debug'])  == "true")?TRUE:FALSE;

		$output = "Error.";
		$attPairs['display'] = (isset($attPairs['display']))?$attPairs['display']:"UnDefined";
		
		switch($attPairs['display']) {
			case "insertForm":
				$output = $obj->displayInsertForm($addGet);
				break;
			case "editTable":
				$output = $obj->displayEditTable($addGet,$debug);
				break;
			default:
				$output = "Unsupported display type";
				break;
		}
		

		return($output);

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
	yesNo : Select Box or Check box, works the same as yesNoText but in both insert and edit
	checkbox : check box field. Displays  on Insert form. 
	*/
	/* 
	'matchOn' does no sanitizing, expects everything to be sanitized already
	*/
	/*
	$options is used for select boxes. an array of arrays is expected. Each 
	index should have "value", "field", optionally "selected" can be set to TRUE || FALSE
	
	can also be used for textareas. height and width are valid options, and expect integers. 
	*/
	public function addField($field) {
		
		if (is_array($field)) {
			
			if (!isset($field['field'])) {
				return(FALSE);
			}
			
			$label       = (isset($field['label']))?$field['label']:NULL;
			$email       = (isset($field['email']))?$field['email']:FALSE;
			$disabled    = (isset($field['disabled']))?$field['disabled']:FALSE;
			$size        = (isset($field['size']))?$field['size']:"40";
			$dupes       = (isset($field['dupes']))?$field['dupes']:FALSE;
			$blank       = (isset($field['blank']))?$field['blank']:FALSE;
			$type        = (isset($field['type']))?$field['type']:"text";
			$options     = (isset($field['options']))?$field['options']:NULL;
			$readonly    = (isset($field['readonly']))?$field['readonly']:FALSE;
			$value       = (isset($field['value']))?$field['value']:NULL;
			$matchOn     = (isset($field['matchOn']))?$field['matchOn']:NULL;
			$validate    = (isset($field['validate']))?$field['validate']:NULL;
			$original    = (isset($field['original']))?$field['original']:FALSE;
			$placeholder = (isset($field['placeholder']))?$field['placeholder']:FALSE;
			$help        = (isset($field['help']) && is_array($field['help']))?$field['help']:FALSE;
			$field       = $field['field'];
		}
		else {
			return(FALSE);
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
		else if ($type == "checkbox") {
			$this->checkboxInput = TRUE;
		}
		
		$temp = array(
			'field'       => $field,
			'label'       => $label,
			'email'       => $email,
			'disabled'    => $disabled,
			'size'        => $size,
			'dupes'       => $dupes,
			'blank'       => $blank,
			'type'        => $type,
			'options'     => $options,
			'readonly'    => $readonly,
			'value'       => $value,
			'matchOn'     => $matchOn,
			'validate'    => $validate,
			'original'    => $original,
			'placeholder' => $placeholder,
			'help'        => $help
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
	
	// $type : any of the index names set in addField
	// $value, what $type should be set to. default == TRUE
	public function modifyField($field,$type,$value=TRUE) {
		$modify = NULL;
		foreach ($this->fields as $I=>$V) {
			if ($V['field'] == $field) {
				$modify = $I;
				break;
			}
		}
		if (isnull($modify)) {
			foreach ($this->hiddenFields as $I=>$V) {
				if ($V['field'] == $field) {
					$modify = $I;
					break;
				}
			}
		}
		
		if (isnull($modify)) {
			return FALSE;
		}
		
		$this->fields[$modify][$type] = $value;
		
		return(TRUE);
	}
	
	public function disableField($field) {
		return($this->modifyField($field,'disabled'));
	}
	
	public function disableAllFields() {
		foreach ($this->fields as $I) {
			$this->modifyField($I['field'],'disabled');
		}
		foreach ($this->hiddenFields as $I) {
			$this->modifyField($I['field'],'disabled');
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
		
		$output .= "<table class=\"engineListInsertTable\">";
		foreach ($this->fields as $I) {
			
			if ($I['type'] == "plainText" || $I['type'] == "radio") {
				continue;
			}
			
			unset($help);
			if (isset($I['help']) && isset($I['help']['url'])) {
				$help = sprintf('<a href="%s" %s %s>%s</a>',
					$I['help']['url'],
					((isset($I['help']['title']))?'title="'.$I['help']['title'].'"':""),
					((isset($I['help']['display']) && $I['help']['display'] == "newWindow")?'target="_blank"':""),
					((isset($I['help']['img']))?$I['help']['img']:$this->helpIconDefault)				
					);
			} 
			
			$output .= "<tr>";
			$output .= "<td>";
			
			// If the label is for a WYSIWYG canvas add a "we_" to the ID, for the javascript find
			$output .= "<label for=\"".$I['field']."_insert\">";
			
			if (isset($help) && ((isset($I['help']['location']) && $I['help']['location'] == "beforeLabel") || !isset($I['help']['location']))) {
				$output .= $help."&nbsp;";
			}
			
			$output .= ($I['blank'] == FALSE)?"<span class=\"requiredField\">":"";
			$output .= $I['label'];
			$output .= ($I['blank'] == FALSE)?"</span>":"";
			if (isset($help) && isset($I['help']['location']) && $I['help']['location'] == "afterLabel") {
				$output .= "&nbsp;".$help."&nbsp;";
			}
			$output .= ": </label>";
			$output .= "</td>";
			$output .= "<td>";
			if (isset($help) && isset($I['help']['location']) && $I['help']['location'] == "beforeField") {
				$output .= $help."&nbsp;";
			}
			if ($I['type'] == "text" || $I['type'] == "date") {
				
				if ($I['original'] === TRUE && isset($I['value'])) {
					$output .= '<input type="hidden" name="original_'.$I['field'].'_insert" value="'.(htmlentities($I['value'])).'" />';
				}
				
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
				$output .= ($I['placeholder'] !== FALSE)?' placeholder="'.(htmlentities($I['placeholder'])).'"':"";
				$output .= " />";
			}
			else if ($I['type'] == "select") {
				
				if ($I['original'] === TRUE && isset($I['value'])) {
					$output .= '<input type="hidden" name="original_'.$I['field'].'_insert" value="'.(htmlentities($I['value'])).'" />';
				}
				
				$output .= "<select name=\"".$I['field']."_insert\"";
				$output .= ($I['readonly'] === TRUE)?" readonly ":"";
				$output .= ($I['disabled'] === TRUE)?" disabled ":"";
				$output .= ">";
				if (isset($I['options'])) {
					foreach ($I['options'] as $option) {
						
						$output .= "<option value=\"".htmlentities($option['value'])."\"";
						
						//Handle if the form is being reposted after a failed submit attempt
						$output .= ($error === TRUE && $this->engine->cleanPost['HTML'][$I['field'].'_insert'] == $option['value'])?" selected":"";
						$output .= ($error === FALSE && isset($option['selected']) && $option['selected'] === TRUE)?" selected":"";

						$output .= ">".htmlentities($option['label'])."</option>";
					}
				}
				$output .= "</select>";
			}
			else if ($I['type'] == "yesNo") {
				if ((isset($I['options']['type']) && $I['options']['type'] != "checkbox") || !isset($I['options']['type'])) {
					$I['options']['type'] = "select";
				}
				
				if ($I['options']['type'] == "select") {
					$output .= "<select name=\"".$I['field']."_insert\"";
					$output .= ($I['readonly'] === TRUE)?" readonly ":"";
					$output .= ($I['disabled'] === TRUE)?" disabled ":"";
					$output .= ">";
					
					// Yes
					$output .= '<option value="1"';
					$output .= ($error === TRUE && $this->engine->cleanPost['HTML'][$I['field'].'_insert'] == "1")?" selected":"";
					$output .= ($error === FALSE && isset($I['value']) && $I['value'] == "1")?" selected":"";
					$output .= ">";
					$output .= (isset($I['options']['yesLabel']))?htmlentities($I['options']['yesLabel']):"Yes";
					$output .= "</option>";
					
					// No
					$output .= '<option value="0"';
					$output .= ($error === TRUE && $this->engine->cleanPost['HTML'][$I['field'].'_insert'] == "0")?" selected":"";
					$output .= ($error === FALSE && isset($I['value']) && $I['value'] == "0")?" selected":"";
					$output .= ">";
					$output .= (isset($I['options']['yesLabel']))?htmlentities($I['options']['noLabel']):"No";
					$output .= "</option>";
					
					$output .= "</select>";
				}
				else if ($I['options']['type'] == "checkbox") {
					$output .= '<input type="checkbox" name="'.$I['field'].'_insert" value="1"';
					$output .= ($error === TRUE && $this->engine->cleanPost['HTML'][$I['field'].'_insert'] == "1")?" checked":"";
					$output .= ($error === FALSE && isset($I['value']) && $I['value'] == "1")?" checked":"";
					$output .= ($I['readonly'] === TRUE)?" readonly ":"";
					$output .= ($I['disabled'] === TRUE)?" disabled ":"";
					$output .= ">";
				}
				else {
					$output .= "invalid type";
				}
				
			}
			else if ($I['type'] == "checkbox") {
				
				$checkboxError = FALSE;
				if (!isset($I['options']['valueTable'])) {
					errorHandle::errorMsg("Value table not set");
					$checkboxError = TRUE;
				}
				if (!isset($I['options']['valueDisplayID'])) {
					errorHandle::errorMsg("valueDisplayID not set");
					$checkboxError = TRUE;
				}
				if (!isset($I['options']['valueDisplayField'])) {
					errorHandle::errorMsg("valueDisplayField table not set");
					$checkboxError = TRUE;
				}
				
				if ($checkboxError === FALSE) {
					
					$sql = sprintf("SELECT * FROM %s",
						$this->database->escape($I['options']['valueTable']));

					$sqlResult = $this->database->query($sql);
					while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
						
						
						
						$output .= "<li>";
						$output .= '<input type="checkbox" name="'.$I['field'].'_insert[]" value="'.htmlsanitize($row[$I['options']['valueDisplayID']]).'" ';
						$output .= ($error === TRUE && isset($this->engine->cleanPost['HTML'][$I['field'].'_insert']) && in_array($row[$I['options']['valueDisplayID']],$this->engine->cleanPost['HTML'][$I['field'].'_insert']))?"checked":"";
						$output .= ($error === FALSE && isset($I['options']['selected']) && in_array($row[$I['options']['valueDisplayID']],$I['options']['selected']))?"checked":"";
						$output .= '/>';
						$output .= "&nbsp; ".htmlsanitize($row[$I['options']['valueDisplayField']]);
						$output .= "</li>";
					}
						$output .= "</ul>";
				} // if checkboxError
			}
			else if ($I['type'] == "textarea") {
				
				if ($I['original'] === TRUE && isset($I['value'])) {
					$output .= '<input type="hidden" name="original_'.$I['field'].'_insert" value="'.(htmlentities($I['value'])).'" />';
				}
				
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
					errorHandle::errorMsg("Value table not set");
					$multiSelectError = TRUE;
				}
				if (!isset($I['options']['valueDisplayID'])) {
					errorHandle::errorMsg("valueDisplayID not set");
					$multiSelectError = TRUE;
				}
				if (!isset($I['options']['valueDisplayField'])) {
					errorHandle::errorMsg("valueDisplayField table not set");
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
				errorHandle::errorMsg("Invalid Type");
			}
			if (isset($help) && isset($I['help']['location']) && $I['help']['location'] == "afterField") {
				$output .= "&nbsp;".$help;
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
		if (isnull($this->orderBy) && isset($this->fields[0]['type']) && $this->fields[0]['type'] != "plainText" ) {
			$this->orderBy = "ORDER BY ".$this->database->escape($this->fields[0]['field']);
		}
		else if (!isnull($this->orderBy)) {
			$this->orderBy = $this->database->escape($this->orderBy);
		}
		else {
			$this->orderBy = "";
		}

		if (!isnull($this->sql)) {
			$sql = $this->sql;	
		}
		else {
			$sql = sprintf("SELECT * FROM %s %s %s",
				$this->database->escape($this->table),
				$this->whereClause,
				$this->orderBy
				);
		}

		if ($this->debug === TRUE) {
			print "SQL: ".$sql."<br />";
		}

		$this->database->sanitize = FALSE;
		$sqlResult = $this->database->query($sql);

		if (!$sqlResult['result']) {
			if ($this->debug === TRUE) {
				errorHandle::errorMsg($sqlResult['error']."<br />");
				errorHandle::errorMsg($sqlResult['query']."<br />");
			}
			// Should be sending a debug error here
			errorHandle::errorMsg("SQL Error");
			return;
		}

		$cols = count($this->fields);
		$colspan = $cols;
		$colspan += ($this->deleteBox       === TRUE)?1:0;
		$colspan += ($this->numberRows      === TRUE)?1:0;
		$colspan += ($this->deleteBoxLeft   === TRUE)?1:0;
		$colspan += ($this->numberRowsRight === TRUE)?1:0;

		$output = "";

		if ($this->sortable === TRUE) {
			global $engineVars;
		
			$output .= "<script src=\"".$engineVars['sortableTables']."\" type=\"text/javascript\"></script>";
			$output .= '<script type="text/javascript">';
			$output .= '$(document).ready(function() 
			    { 
			        $("#'.$this->database->escape($this->table).'_table").tablesorter({textExtraction: function(node) { 
					            return  node.firstChild.nextSibling.value; 
					        }}); 
			    } 
			);';
			$output .= "</script>";
		}
		if ($this->dragOrdering === TRUE) {
			global $engineVars;
			$output .= "<script src=\"".$engineVars['tablesDragnDrop']."\" type=\"text/javascript\"></script>";
		}

		$output .= "\n<!-- engine Instruction break -->".'<!-- engine Instruction displayTemplateOff -->'."\n<!-- engine Instruction break -->";
		$output .= "<form action=\"".$_SERVER['PHP_SELF']."".$queryString."\" method=\"post\" onsubmit=\"return listObjDeleteConfirm(this);\">".$this->eolChar;
		$output .= sessionInsertCSRF();
		$output .= "\n";
		$output .= "<table border=\"0\" cellpadding=\"1\" cellspacing=\"0\" id=\"".$this->database->escape($this->table)."_table\"";
		
		$output .= " class=\"engineListDisplayTable";
		if ($this->sortable === TRUE) {
			$output .= " sortable tablesorter";
		}
		$output .= "\"";
		
		$output .= ">".$this->eolChar;
		$output .= "<thead>".$this->eolChar;
		$output .= "<tr>".$this->eolChar;
		
		if ($this->numberRows === TRUE) {
			$output .= "<th style=\"width: 25px;\">#</th>".$this->eolChar;
		}

		if ($this->deleteBoxLeft === TRUE) {
			$output .= "<th style=\"width: 100px;\">Delete</th>".$this->eolChar;
		}

		for($I=0;$I<(int)$cols;$I++) {
			
			if ($this->insertOnly($this->fields[$I]['type'])) {
				continue;
			}
			
			$output .= "<th style=\"text-align: left;\">".$this->fields[$I]['label']."</th>".$this->eolChar;
		}

		// Put delete box on the left
		if ($this->deleteBox === TRUE) {
			$output .= "<th style=\"width: 100px;\">Delete</th>".$this->eolChar;
		}
		
		if ($this->numberRowsRight === TRUE) {
			$output .= "<th style=\"width: 25px;\">#</th>".$this->eolChar;
		}
		
		$output .= "</tr>".$this->eolChar;
		$output .= "</thead>".$this->eolChar;
		
		$output .= "<tbody>".$this->eolChar;
		
		$numberRowsCount = 1;
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_BOTH)) {
			$output .= "<tr";
			if ($this->rowStriping === TRUE) {
				$output .= (is_odd($numberRowsCount))?" class=\"oddrow\"":" class=\"evenrow\"";
			}
			if ($this->dragOrdering === TRUE) {
				$output .= " id=\"".$row[0]."\"";
			}
			$output .= ">".$this->eolChar;
			
			if ($this->numberRows === TRUE) {
				$output .= "<td class=\"alignRight\">".$this->eolChar;
				$output .= $numberRowsCount . $this->rowNumDelim;
				$output .= "</td>".$this->eolChar;
			}
			
			if ($this->deleteBoxLeft === TRUE) {
				$output .= "<td class=\"alignCenter\">".$this->eolChar;
				$output .= "<input type=\"checkbox\" name=\"delete[]\" class=\"delete\" value=\"".$row[0]."\" />".$this->eolChar;
				$output .= "</td>".$this->eolChar;
			}

			for($I=0;$I<(int)$cols;$I++) {
				
				if ($this->insertOnly($this->fields[$I]['type'])) {
					continue;
				}
				
				$output .= "<td>".$this->eolChar;
				
				// if ($this->sortable === TRUE && $this->fields[$I]['type'] != "plainText") {
				// 					$output .= "sorttable_customkey=\"".htmlentities($row[$this->fields[$I]['field']])."\"";
				// 				}
				
				// $output .= ">";
				
				if ($this->sortable === TRUE && $this->fields[$I]['type'] != "plainText") {
					$output .= "<input type=\"hidden\" name=\"".$this->fields[$I]['field']."_sortable_".$row[0]."\" value=\"".htmlentities($row[$this->fields[$I]['field']])."\" />".$this->eolChar;
				}
				
				if ($I == 0) {
					$output .= "<input type=\"hidden\" name=\"check_".$row[0]."\" value=\"".$row[0]."\" />".$this->eolChar;
					
					foreach ($this->hiddenFields as $hiddenField) {
					
						$output .= "<input type=\"hidden\" name=\"".$hiddenField['field']."_".$row[0]."\" id=\"".$hiddenField['field']."_".$row[0]."\" class=\"".$hiddenField['field']."\" value=\"".htmlentities($row[$hiddenField['field']])."\" ";
						$output .= "/>".$this->eolChar;

					}
					
				}
				
				if ($this->fields[$I]['type'] == "text") {
					
					$value = $row[$this->fields[$I]['field']];
					if (!isnull($this->fields[$I]['matchOn'])) {
						$sql = "SELECT ".$this->database->escape($this->fields[$I]['matchOn']['field'])." FROM ".$this->database->escape($this->fields[$I]['matchOn']['table'])." WHERE ".$this->database->escape($this->fields[$I]['matchOn']['key'])."='".$this->database->escape($row[$this->fields[$I]['field']])."'";
						
						$this->database->sanitize = FALSE;
						$matchOnSqlResult               = $this->database->query($sql);
						$matchOnValueResult             = mysql_fetch_array($matchOnSqlResult['result'], MYSQL_BOTH);
						
						if (isset($this->fields[$I]['matchOn']['field'])) {
							$value = $matchOnValueResult[$this->fields[$I]['matchOn']['field']];
						}
					}
					
					if ($this->fields[$I]['original'] === TRUE && isset($value)) {
						$output .= '<input type="hidden" name="original_'.$this->fields[$I]['field'].'_'.$row[0].'" value="'.(htmlentities($value)).'" />'.$this->eolChar;
					}
					
					$output .= "<input type=\"text\" size=\"".$this->fields[$I]['size']."\" name=\"".$this->fields[$I]['field']."_".$row[0]."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']."";
					$output .= "\" value=\"".htmlentities($value)."\" ";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= "/>".$this->eolChar;
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
					$output .= "/>".$this->eolChar;
				}
				else if ($this->fields[$I]['type'] == "date") {
					
					$value = $row[$this->fields[$I]['field']];
					
					if ($value == "0") {
						$value = "";
					}
					
					if ($this->fields[$I]['original'] === TRUE && isset($value)) {
						$output .= '<input type="hidden" name="original_'.$this->fields[$I]['field'].'_'.$row[0].'" value="'.(htmlentities($value)).'" />'.$this->eolChar;
					}
					
					$output .= "<input type=\"text\" size=\"".$this->fields[$I]['size']."\" name=\"".$this->fields[$I]['field']."_".$row[0]."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']." date_input\" value=\"".(!is_empty($value)?htmlentities(date("m/d/Y",$value)):"")."\" ";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= "/>".$this->eolChar;
				}
				else if ($this->fields[$I]['type'] == "select") {

					$value = $row[$this->fields[$I]['field']];

					if ($this->fields[$I]['original'] === TRUE && isset($value)) {
						$output .= '<input type="hidden" name="original_'.$this->fields[$I]['field'].'_'.$row[0].'" value="'.(htmlentities($value)).'" />'.$this->eolChar;
					}

					$output .= "<select name=\"".$this->fields[$I]['field']."_".$row[0]."\"";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= ">".$this->eolChar;
					if (isset($this->fields[$I]['options'])) {
						foreach ($this->fields[$I]['options'] as $option) {

							$output .= "<option value=\"".htmlsanitize($option['value'])."\"";
							$output .= ($row[$this->fields[$I]['field']] == $option['value'])?" selected":"";
							$output .= ">".htmlsanitize($option['label'])."</option>";
						}
					}
					$output .= "</select>".$this->eolChar;
				}
				else if ($this->fields[$I]['type'] == "yesNo") {
					if ((isset($this->fields[$I]['options']['type']) && $this->fields[$I]['options']['type'] != "checkbox") || !isset($this->fields[$I]['options']['type'])) {
						$this->fields[$I]['options']['type'] = "select";
					}

					$value = $row[$this->fields[$I]['field']];

					if ($this->fields[$I]['options']['type'] == "select") {
						$output .= "<select name=\"".$this->fields[$I]['field']."_".$row[0]."\"";
						$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
						$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
						$output .= ">".$this->eolChar;

						// Yes
						$output .= '<option value="1"';
						$output .= ($value == "1")?" selected":"";
						$output .= ">";
						$output .= (isset($this->fields[$I]['options']['yesLabel']))?htmlentities($this->fields[$I]['options']['yesLabel']):"Yes";
						$output .= "</option>".$this->eolChar;

						// No
						$output .= '<option value="0"';
						$output .= ($value == "0")?" selected":"";
						$output .= ">";
						$output .= (isset($this->fields[$I]['options']['yesLabel']))?htmlentities($this->fields[$I]['options']['noLabel']):"No";
						$output .= "</option>".$this->eolChar;

						$output .= "</select>".$this->eolChar;
					}
					else if ($this->fields[$I]['options']['type'] == "checkbox") {
						$output .= '<input type="checkbox" name="'.$this->fields[$I]['field'].'_'.$row[0].'" value="1"';
						$output .= ($value == "1")?" checked":"";
						$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
						$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
						$output .= ">";
					}
					else {
						$output .= "invalid type";
					}

				}
				else if ($this->fields[$I]['type'] == "radio") {
					$output .= "<input type=\"radio\" name=\"".$this->fields[$I]['field']."\" value=\"".htmlsanitize($row[0])."\" id=\"".$this->fields[$I]['field']."_".$row[0]."\" class=\"".$this->fields[$I]['field']."\"";
					$output .= ($row[$this->fields[$I]['field']] == 1)?" checked":"";
					$output .= ($this->fields[$I]['disabled'] === TRUE)?" disabled ":"";
					$output .= ($this->fields[$I]['readonly'] === TRUE)?" readonly ":"";
					$output .= " />".$this->eolChar;
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
					$output .= $tempField.$this->eolChar;
				}
				else if ($this->fields[$I]['type'] == "textarea") {
					
					
					$value = $row[$this->fields[$I]['field']];
					
					if ($this->fields[$I]['original'] === TRUE && isset($value)) {
						$output .= '<input type="hidden" name="original_'.$this->fields[$I]['field'].'_'.$row[0].'" value="'.(htmlentities($value)).'" />';
					}
					
					if (!isnull($this->fields[$I]['matchOn'])) {
						$sql = "SELECT ".$this->database->escape($this->fields[$I]['matchOn']['field'])." FROM ".$this->database->escape($this->fields[$I]['matchOn']['table'])." WHERE ".$this->database->escape($this->fields[$I]['matchOn']['key'])."='".$this->database->escape($row[$this->fields[$I]['field']])."'";
						
						$this->database->sanitize = FALSE;
						$matchOnSqlResult               = $this->database->query($sql);
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
					$output .= "</textarea>".$this->eolChar;
				}
				else if ($this->fields[$I]['type'] == "multiselect") {

					errorHandle::errorMsg("Multi Select type not supported in table edit");
				}
				else if ($this->fields[$I]['type'] == "checkbox") {

					errorHandle::errorMsg("Checkbox type not supported in table edit");
				}
				
				$output .= "</td>".$this->eolChar;
			}

			$output .= "<td class=\"alignCenter\">";
			if ($this->deleteBox === TRUE) {
				$output .= "<input type=\"checkbox\" name=\"delete[]\" class=\"delete\" value=\"".$row[0]."\" />".$this->eolChar;
			}
			$output .= "</td>".$this->eolChar;
			
			if ($this->numberRowsRight === TRUE) {
				$output .= "<td class=\"alignRight\">";
				$output .= $this->rowNumDelimRight . $numberRowsCount;
				$output .= "</td>".$this->eolChar;
			}
			$numberRowsCount++;
			
			$output .= "</tr>".$this->eolChar;

		}
		$output .= "</tbody>".$this->eolChar;
		$output .= "</table>".$this->eolChar;
		if ($this->noSubmit === FALSE) {
			$submitButtonName = (isnull($this->submitName))?$this->table.'_update':$this->submitName;
			$output .= "<input type=\"submit\" value=\"Update\" name=\"".$submitButtonName."\" />".$this->eolChar;
		}
		$output .= "</form>";
		$output .= "\n<!-- engine Instruction break -->".'<!-- engine Instruction displayTemplateOn -->'."\n<!-- engine Instruction break -->";

		if ($this->dateInputs) {
			$output .= "<script>$($.date_input.initialize);</script>";
		}

		if ($this->dragOrdering === TRUE) {
			$output .= '<script type="text/javascript">';
			$output .= "var table = document.getElementById('".$this->database->escape($this->table)."_table');";
			$output .= 'var tableDnD = new TableDnD();';
			$output .= 'tableDnD.init(table);';
			$output .= '</script>';
		}

		return($output); 
	}
	
	// returns TRUE if insert is completely successful
	// otherwise FALSE
	public function insert() {
		
		$error = array();
		$error['string'] = "";
		$error["error"]  = FALSE;
		
		$multiSelectFields = array();
		
		if ($this->updateInsert === TRUE) {
			
			if (isnull($this->updateInsertID)) {
				errorHandle::errorMsg("Update Error: updateInsertID was not defined");
				 return(!$error['error']);
			}
			else if (!isset($this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"])) {
				errorHandle::errorMsg("Update Error: updateInsertID was not set");
				return(!$error['error']);
			}
		}
		
		$checkboxFields    = NULL;
		$multiSelectFields = NULL;
		
		foreach ($this->fields as $I) {
			
			if ($I['type'] == "plainText") {
				continue;
			}
			else if ($I['type'] == "multiselect") {
				if (isnull($multiSelectFields)) {
					$multiSelectFields = array();
				}
				$multiSelectFields[] = $I;
			}
			else if ($I['type'] == "checkbox") {
				if (isnull($checkboxFields)) {
					$checkboxFields = array();
				}
				$checkboxFields[] = $I;
			}
			
			// Check boxes don't return if they aren't checked. Deal with that
			if ($I["type"] == "yesNo" && $I["options"]["type"] == "checkbox") {
				if (!isset($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'])) {
					$this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] = "0";
					$this->engine->cleanPost['HTML'][$I['field'].'_insert']  = "0";
					$this->engine->cleanPost['RAW'][$I['field'].'_insert']   = "0";
				}
			}
			
			// Check for blanks
			// If the field is NOT allowed to have blanks
			if ($I["blank"] === FALSE) {
				
				// perform a blank check. 
				if ($I['type'] != "multiselect" && $I['type'] != "checkbox") {
					if (is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'],FALSE)) {
						$error['string'] .= errorHandle::errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
						$error['error'] = TRUE;
						continue;
					}

					if ($I['type'] == "select" && $this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] == "NULL") {
						$error['string'] .= errorHandle::errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
						$error['error'] = TRUE;
						continue;
					}
				}
				else if ($I['type'] == "multiselect" && !isset($this->engine->cleanPost['MYSQL'][$I['field']])) {
					$error['string'] .= errorHandle::errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
					$error['error'] = TRUE;
					continue;
				}
				else if ($I['type'] == "checkbox" && !isset($this->engine->cleanPost['MYSQL'][$I['field']."_insert"])) {
					$error['string'] .= errorHandle::errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
					$error['error'] = TRUE;
					continue;
				}
			}
			
			//Do the Email Check
			//check if the current field should have a valid email address
			if ($I["email"] === TRUE) {
				// If its not empty, and it does not have a valid email address, continue
				if(!empty($this->engine->cleanPost['MYSQL'][$I["field"].'_insert']) && !validateEmailAddr($this->engine->cleanPost['MYSQL'][$I["field"].'_insert'])) {
					$error['string'] .= errorHandle::errorMsg("Invalid E-Mail Address: ". htmlentities($this->engine->cleanPost['MYSQL'][$I["field"].'_insert']).". Other records may be updated still.");
					$error['error'] = TRUE;
					continue;
				}
			}
			
			// Check for duplicates
			// If the field is NOT allowed to have dupes
			if ($I["dupes"] === FALSE) {
				
				// Irrelevant to multiselect && checkboxes
				if ($I['type'] == "multiselect" || $I['type'] == "checkbox") {
					continue;
				}
				
				// perform a dupe check. 
				if ($I["email"] === TRUE && $this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] == "dev@null.com") {
					// dev@null.com is a special case email. We allow duplicates of it
				}
				else if ($this->duplicateCheck($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'],$I["field"])) {
					$error['string'] .= errorHandle::errorMsg("Entry, ".htmlentities($this->engine->cleanPost['MYSQL'][$I['field'].'_insert']).", already in database. Other records may be updated still.");
					$error['error'] = TRUE;
					continue;
				}
			}
			
			if (isset($I['validate'])) {
				
				// Irrelevant to multiselect && checkboxes
				if ($I['type'] == "multiselect" || $I['type'] == "checkbox") {
					continue;
				}
				
				if ($I['blank'] === TRUE && is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'])) {
					// skip validation if field is blank and allowed to be blank
					//continue;
				}
				else {
					$validateResult = $this->validateData($I['validate'],$this->engine->cleanPost['MYSQL'][$I['field'].'_insert']);
					if ($validateResult !== FALSE) {
						$error['string'] .= $validateResult;
						$error['error'] = TRUE;
						continue;
					}
				}
			}
			
			// Change dates into unix time stamps
			if ($I['type'] == "date") {
				if (!is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_insert'])) {
					list($m,$d,$y) = explode("/",$this->engine->cleanPost['MYSQL'][$I['field'].'_insert']);
					$this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] = mktime(0,0,0,$m,$d,$y);
				}
			}
			
			// Multiselect doesn't have a _insert variable, checkbox is an array
			if ($I['type'] != "multiselect" && $I['type'] != "checkbox") {
				$this->engine->cleanPost['MYSQL'][$I['field'].'_insert'] = stripCarriageReturns($this->engine->cleanPost['MYSQL'][$I['field'].'_insert']);
			}
		}
		
		if ($error['error'] === TRUE) {
			return(!$error['error']);
		}

		$result = $this->database->transBegin($this->database->escape($this->table));

		if ($result !== TRUE) {
			errorHandle::errorMsg("Transaction could not begin.");
			$error['error'] = TRUE; // Yeah. i know. this doesn't need set. just staying consistent. 
			if ($this->debug === TRUE) {
				errorHandle::errorMsg($result['error']."<br />");
				errorHandle::errorMsg($result['query']."<br />");
			}
			return(!$error['error']);
		}

		if ($this->updateInsert === FALSE) {
			$sql = sprintf("INSERT INTO %s (%s) VALUES(%s)",
				$this->database->escape($this->table),
				$this->buildFieldListInsert(),
				$this->buildFieldValueInsert()			
				);
		}
		else {
			
			$sql = sprintf("UPDATE %s SET %s WHERE %s='%s'",
				$this->database->escape($this->table),
				$this->buildInsertUpdateString(),
				$this->database->escape($this->updateInsertID),
				$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"]
				);
					
		}

		$sqlResult = $this->database->query($sql);
		
		$output = "";
		
		if (!$sqlResult['result']) {
			$this->database->transRollback();
			$this->database->transEnd();
			$error['error'] = TRUE;
			errorHandle::errorMsg("Insert Error: ");
			if ($this->debug === TRUE) {
				errorHandle::errorMsg($sqlResult['error']."<br />");
				errorHandle::errorMsg($sqlResult['query']."<br />");
			}
			
		}
		else {
			
			// Clear the submit button name on a success submit, so we don't repopulate 
			// the form
			if ($this->repost === TRUE) {
				$submitButtonName = (isnull($this->submitName))?$this->table.'_submit':$this->submitName;
				$this->engine->cleanPost['MYSQL'][$submitButtonName] = NULL;
			}
			
			if (!isnull($checkboxFields)) {
				if ($this->updateInsert === FALSE) {
					$linkObjectID = $sqlResult['id'];
				}
				else {
					$linkObjectID = $this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"];
				}
				
				foreach ($checkboxFields as $I) {
					
					if (isset($I['options']['linkTable']) && isset($I['options']['linkObjectField']) && isset($I['options']['linkValueField'])) {
						$sql = sprintf("DELETE FROM %s WHERE %s='%s'",
							$this->database->escape($I['options']['linkTable']),
							$this->database->escape($I['options']['linkObjectField']),
							$linkObjectID
							);

						$sqlResult                      = $this->database->query($sql);
						
						if (!$sqlResult['result']) {
							$this->database->transRollback();
							$this->database->transEnd();
							$error['error'] = TRUE;
							errorHandle::errorMsg("Checkbox Delete Error: <br />");
							if ($this->debug === TRUE) {
								errorHandle::errorMsg($sqlResult['error']."<br />");
								errorHandle::errorMsg($sqlResult['query']."<br />");
							} // if debug
							return(!$error['error']);
						} // if sql error
						
						//if (isset($this->engine->cleanPost['MYSQL'][$I['field']."_insert"])) {
							foreach ($this->engine->cleanPost['MYSQL'][$I['field']."_insert"] as $K=>$value) {
								$sql = sprintf("INSERT INTO %s(%s, %s) VALUES('%s','%s')",
									$this->database->escape($I['options']['linkTable']),
									$this->database->escape($I['options']['linkValueField']),
									$this->database->escape($I['options']['linkObjectField']),
									$value,
									$linkObjectID
									);
							
								$this->database->sanitize = FALSE;
								$sqlResult                = $this->database->query($sql);
							
								if (!$sqlResult['result']) {
									$this->database->transRollback();
									$this->database->transEnd();
									$error['error'] = TRUE;
									errorHandle::errorMsg("Checkbox Insert Error: <br />");
									if ($this->debug === TRUE) {
										errorHandle::errorMsg($sqlResult['error']."<br />");
										errorHandle::errorMsg($sqlResult['query']."<br />");
									} // if debug
									return(!$error['error']);
								} // if sql error
							} // foreach insert item
						//} // if field is set
						
					}
					else {
						if (!isset($I['options']['linkTable'])) {
							errorHandle::errorMsg("Link table not defined"."<br />");
						}
						if (!isset($I['options']['linkObjectField'])) {
							errorHandle::errorMsg("Link Object not defined"."<br />");
						}
						if (!isset($I['options']['linkValueField'])) {
							errorHandle::errorMsg("Link Value Field not defined"."<br />");
						}
						$error['error'] = TRUE;
					}
					
				}
				
			}
			
			// If we have any multiselect types, deal with them here
			if (!isnull($multiSelectFields)) {
				if ($this->updateInsert === FALSE) {
					$linkObjectID = $sqlResult['id'];
				}
				else {
					$linkObjectID = $this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"];
				}	
				
				foreach ($multiSelectFields as $I) {
					
					$sql = sprintf("DELETE FROM %s WHERE %s='%s'",
						$this->database->escape($I['options']['linkTable']),
						$this->database->escape($I['options']['linkObjectField']),
						$linkObjectID
						);

					$this->database->sanitize = FALSE;
					$sqlResult                      = $this->database->query($sql);
					
					if (!$sqlResult['result']) {
						$this->database->transRollback();
						$this->database->transEnd();
						$error['error'] = TRUE;
						errorHandle::errorMsg("Multiselect Delete Error: <br />");
						if ($this->debug === TRUE) {
							errorHandle::errorMsg($sqlResult['error']."<br />");
							errorHandle::errorMsg($sqlResult['query']."<br />");
						} // if debug
						return(!$error['error']);
					} // if sql error
					
					if (isset($this->engine->cleanPost['MYSQL'][$I['field']])) {
						foreach ($this->engine->cleanPost['MYSQL'][$I['field']] as $K=>$value) {
							$sql = sprintf("INSERT INTO %s(%s, %s) VALUES('%s','%s')",
								$this->database->escape($I['options']['linkTable']),
								$this->database->escape($I['options']['linkValueField']),
								$this->database->escape($I['options']['linkObjectField']),
								$value,
								$linkObjectID
								);

							$this->database->sanitize = FALSE;
							$sqlResult                = $this->database->query($sql);
							
							if (!$sqlResult['result']) {
								$this->database->transRollback();
								$this->database->transEnd();
								$error['error'] = TRUE;
								errorHandle::errorMsg("Multiselect Insert Error: <br />");
								if ($this->debug === TRUE) {
									errorHandle::errorMsg($sqlResult['error']."<br />");
									errorHandle::errorMsg($sqlResult['query']."<br />");
								} // if debug
								return(!$error['error']);
							} // if sql error
						} // foreach $K=>$value
					} // if isset($this->engine->cleanPost['MYSQL'][$I['field']]
				} // foreach $multiSelectFields
			} // If Multiselect
			
		} // else

		if ($error['error'] === FALSE) {
			
			$this->database->transCommit();
			$this->database->transEnd();
			
			errorHandle::successMsg("Entry successfully added to the database.");

			// Drop the Insert ID into a local variable suitable for framing
			if ($this->updateInsert === FALSE) {
				$this->engine->localVars("listObjInsertID",$sqlResult['id']);
			}
			else {
				$this->engine->localVars("listObjInsertID",$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"]);
			}
		}

		if ($this->dragOrdering === TRUE) {
			$sql = sprintf("UPDATE %s SET position=%s WHERE %s=%s",
				$this->database->escape($this->table),
				((int)$sqlResult['id'] - 1),
				$this->primaryKey,
				$sqlResult['id']
			);
			$this->database->sanitize = FALSE;
			$sqlResult = $this->database->query($sql);
		}

		return(!$error['error']);
	}
	
	// returns TRUE if insert is completely successful
	// otherwise FALSE
	//
	// NOTE: will return false if just 1 record fails
	public function update($returnBool=FALSE) {

		$error           = array();
		$error["error"]  = FALSE;

		if (isset($this->engine->cleanPost['MYSQL']['delete'])) {
			
			if (!empty($this->deletedIDs)) {
				$this->deletedIDs = array();
			}
			
			foreach($this->engine->cleanPost['MYSQL']['delete'] as $value) {

				$this->deletedIDs[] = $value;

				$sql = sprintf("DELETE FROM %s WHERE %s=%s",
					$this->database->escape($this->table),
					$this->primaryKey,
					$this->database->escape($value)
					);

				$this->database->sanitize = FALSE;
				$sqlResult = $this->database->query($sql);

				if (!$sqlResult['result']) {
					errorHandle::errorMsg("Delete Error. <br />");
					if ($this->debug === TRUE) {
						errorHandle::errorMsg($sqlResult['error']."<br />");
						errorHandle::errorMsg($sqlResult['query']."<br />");
					}
					$error["error"]   = TRUE;
				}

			}
		}

		$sql = sprintf("SELECT * FROM %s",
			$this->database->escape($this->table)
			);

		$this->database->sanitize = FALSE;
		$sqlResult = $this->database->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::errorMsg("Error fetching table entries. <br />");
			if ($this->debug === TRUE) {
				errorHandle::errorMsg($sqlResult['error']."<br />");
				errorHandle::errorMsg($sqlResult['query']."<br />");
			}
			$error["error"]   = TRUE;
		}

		while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_BOTH)) {

			//grab the first column in the current row, if it is set, throw it in $temp
			if (!isset($this->engine->cleanPost['MYSQL']["check_".$row[0]])) {
				continue;
			}

			$disabledCount = 0; // Count how many disabled fields we have for this record
			$fieldCount    = 0; // Count total fields for this record. If $fieldCount == $disabledCount nothing to do.
			
			// FOr each defined field
			foreach ($this->fields as $I) {
				
				if ($this->insertOnly($I['type'])) {
					continue;
				}
				
				$fieldCount++;
				if ($I['disabled'] == TRUE) {
					$disabledCount++;
					continue;
				}
				
				// Check boxes don't return if they aren't checked. Deal with that
				if ($I["type"] == "yesNo" && $I["options"]["type"] == "checkbox") {
					if (!isset($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]])) {
						$this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]] = "0";
						$this->engine->cleanPost['HTML'][$I['field'].'_'.$row[0]]  = "0";
						$this->engine->cleanPost['RAW'][$I['field'].'_'.$row[0]]   = "0";
					}
				}
				
				//Check for blanks
				// If the field is NOT allowed to have blanks
				if ($I["blank"] === FALSE && $I['disabled'] === FALSE) {
					// perform a blank check. Continue to the next row in the database if there is a blank. 
					if (is_empty($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]],FALSE)) {
						errorHandle::errorMsg("Blank entries not allowed in ".htmlentities($I['label']).". Other records may be updated still.");
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
						errorHandle::errorMsg("Invalid E-Mail Address: ". htmlentities($this->engine->cleanPost['MYSQL'][$I["field"].'_'.$row[0]]).". Other records may be updated still.");
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
					// Suspect -- $this->primaryKey ???
					// $this->updateInsertID = $this->primaryKey;
					$this->updateInsertID = (isset($this->updateInsertID))?$this->updateInsertID:$this->primaryKey;
					
					$this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"] = $row[0];			
							
					// perform a dupe check. Continue to the next row in the database if there is a dupe in that field. 
					if ($row[$I["field"]] != $this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]] && $this->duplicateCheck($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]],$I["field"])) {
						errorHandle::errorMsg("Entry, ".htmlentities($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]]).", already in database. Other records may be updated still.");
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
							$error["error"]   = TRUE;
							continue 2;
						}
					}
				}
				
				$this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]] = stripCarriageReturns($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]]);
			
			} // For each defined field
			
			if ($disabledCount == $fieldCount) {
				continue;
			}
			
			$sql = sprintf("SELECT COUNT(*) FROM %s WHERE %s AND %s='%s'",
				$this->database->escape($this->table),
				$this->buildUpdateString($row[0],TRUE),
				$this->primaryKey,
				$row[0]
				);

			$sqlResultUpdates = $this->database->query($sql);

			$rowUpdate = mysql_fetch_array($sqlResultUpdates['result'],  MYSQL_ASSOC);
		
			if ($rowUpdate["COUNT(*)"] == 0) {
				$this->modifiedIDs[] = $row[0];
			}

			$sql = sprintf("UPDATE %s SET %s WHERE %s='%s'",
				$this->database->escape($this->table),
				$this->buildUpdateString($row[0]),
				$this->primaryKey,
				$row[0]
				);

			$this->database->sanitize = FALSE;
			$sqlResult2 = $this->database->query($sql);

			if (!$sqlResult2['result']) {
				errorHandle::errorMsg("Update Error.<br />");
				if ($this->debug === TRUE) {
					errorHandle::errorMsg($sqlResult2['error']."<br />");
					errorHandle::errorMsg($sqlResult2['query']."<br />");
				}
				$error["error"]   = TRUE;
				
			}

		}

		if($error["error"] === FALSE) {
			errorHandle::successMsg("Database successfully updated.");
			return(!$error["error"]);
		}

		return(!$error["error"]);
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
	
	// Returns array of IDs when there are modifications
	// FALSE otherwise.
	// NOTE: if there is an error it will set an error message as well as FALSE
	public function haveUpdates() {
		$updateIDs = array();
		
		$sql = sprintf("SELECT * FROM %s",
			$this->database->escape($this->table)
			);

		$this->database->sanitize = FALSE;
		$sqlResult = $this->database->query($sql);

		if (!$sqlResult['result']) {
			errorHandle::errorMsg("Error fetching table entries. <br />");
			if ($this->debug === TRUE) {
				errorHandle::errorMsg($sqlResult['error']."<br />");
				errorHandle::errorMsg($sqlResult['query']."<br />");
			}
			$error["error"]   = TRUE;
			return(FALSE);
		}

		while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_BOTH)) {
			
			foreach ($this->fields as $I) {
				// Check boxes don't return if they aren't checked. Deal with that
				if ($I["type"] == "yesNo" && $I["options"]["type"] == "checkbox") {
					if (!isset($this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]])) {
						$this->engine->cleanPost['MYSQL'][$I['field'].'_'.$row[0]] = "0";
						$this->engine->cleanPost['HTML'][$I['field'].'_'.$row[0]]  = "0";
						$this->engine->cleanPost['RAW'][$I['field'].'_'.$row[0]]   = "0";
					}
				}
			}
			
			$sql = sprintf("SELECT COUNT(*) FROM %s WHERE %s AND %s='%s'",
				$this->database->escape($this->table),
				$this->buildUpdateString($row[0],TRUE),
				$this->primaryKey,
				$row[0]
				);

			$sqlResultUpdates = $this->database->query($sql);

			if ($sqlResultUpdates['result']) {
				$rowUpdate = mysql_fetch_array($sqlResultUpdates['result'],  MYSQL_ASSOC);

				if ($rowUpdate["COUNT(*)"] == 0) {
					$updateIDs[] = $row[0];
				}
			}
			else {
				errorHandle::errorMsg("Error comparing results. Likely all fields disabled. <br />");
			}
		}
		
		if (is_empty($updateIDs)) {
			return(FALSE);
		}
		return($updateIDs);
	}
	
	private function duplicateCheck($new,$col) {
		
		$idMatch = "";
		if ($this->updateInsert === TRUE) {
			$idMatch = sprintf(" AND %s!='%s'",
				$this->database->escape($this->updateInsertID),
				$this->database->escape($this->engine->cleanPost['MYSQL'][$this->updateInsertID."_insert"])
			);
		}
		$sql = sprintf("SELECT * FROM %s WHERE %s='%s'%s",
			$this->database->escape($this->table),
			$this->database->escape($col),
			$this->database->escape($new),
			$idMatch
			);
			
		$this->database->sanitize = FALSE;
		$sqlResult = $this->database->query($sql);

		//We should probably do a SQL check here
		if (!$sqlResult['result']) {
			return(TRUE);
		}
		
		if (mysql_num_rows($sqlResult['result']) == 0) {
			return(FALSE);
		}

		return(TRUE);
	}
	
	private function buildUpdateString($row,$and=FALSE) {

		$sep = ($and === TRUE)?" and ":",";
		
		$temp = array();
		foreach ($this->fields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			if ($this->insertOnly($I['type'])) {
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
			
			$temp[] = $this->database->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_".$row]."'";
		}
		foreach ($this->hiddenFields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			$temp[] = $this->database->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_".$row]."'";
		}
		$output = implode($sep,$temp);
		
		return($output);

	}
	
	private function buildInsertUpdateString() {

		$temp = array();
		foreach ($this->fields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			if ($I['type'] == "plainText" || $I['type'] == "radio" || $I['type'] == "multiselect" || $I['checkbox']) {
				continue;
			}
			
			$temp[] = $this->database->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_insert"]."'";
		}
		foreach ($this->hiddenFields as $I) {
			if($I['disabled'] === TRUE) {
				continue;
			}
			$temp[] = $this->database->escape($I["field"])."='".$this->engine->cleanPost['MYSQL'][$I["field"]."_insert"]."'";
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
			if ($I['type'] == "plainText" || $I['type'] == "radio" || $I['type'] == "multiselect" || $I['type'] == "checkbox") {
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
			if ($I['type'] == "plainText" || $I['type'] == "radio" || $I['type'] == "multiselect" || $I['type'] == "checkbox") {
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
				$error .= errorHandle::errorMsg("Entry, ".htmlentities($data).", not a valid URL.");
			}
		}
		else if ($validate == "email") {
			if (!validateEmailAddr($data)) {
				$error .= errorHandle::errorMsg("Entry, ".htmlentities($data).", not a valid Email Address.");
			}
		}
		else if ($validate == "internalEmail") {
			if (!validateEmailAddr($data,TRUE)) {
				$error .= errorHandle::errorMsg("Entry, ".htmlentities($data).", not a valid Email Address.");
			}
		}
		else if ($validate == "phone") {
			if (!validPhoneNumber($data)) {
				$error .= errorHandle::errorMsg("Entry, ".htmlentities($data).", not a valid Phone.");
			}
		}
		else if ($validate == "ipaddr") {
			if (!validIPAddr($data)) {
				$error .= errorHandle::errorMsg("Entry, ".htmlentities($data).", not a valid IP Address Range.");
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
				
				case "alphaNoSpaces":
					$regexp = "/^[a-zA-Z]+$/";
					break;

				case "noSpaces":
					$regexp = "/^[^\ ]+$/";
					break;

				case "noSpecialChars":
					$regexp = "/^[^\W]+$/";
				break;

				case "date":
					$regexp = "/^\d\d\/\d\d\/\d\d\d\d$/";
					break;

				default:
				break;
			}


			$return = @preg_match($regexp,$data);

			// if the regular expression fails, returns false. If there is no match, returns "0" otherwise "1"
			if ($return === FALSE) {
				$error .= errorHandle::errorMsg("Invalid Regular Expression Passed.");
				$return = -1;
			}
			else if ($return == 0) {
				$error .= errorHandle::errorMsg("Entry, ".htmlentities($data).", is not valid.");
			}

		}

		if (is_empty($error)) {
			return(FALSE);
		}

		return($error);
	}
	
	private function insertOnly($type) {
		
		if (in_array($type,$this->insertOnlyTypes)) {
			return(TRUE);
		}
		
		return(FALSE);
	}

}



?>