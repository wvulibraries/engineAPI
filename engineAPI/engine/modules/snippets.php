<?php

$GLOBALS['snippetObject'] = "FOO";

global $moduleFunctions;
$moduleFunctions['snippet']['pattern']  = "/\{snippet\s+(.+?)\}/";
$moduleFunctions['snippet']['function'] = "snippetDisplayTemplate";

function snippetDisplayTemplate($matches) {
	
	if ($GLOBALS['snippetObject'] == "FOO") {
		return("{snippet ".$matches[1]."}");
	}
	
	$snippet = &$GLOBALS['snippetObject'];
	
	$attPairs  = split("\" ",$matches[1]);

	foreach ($attPairs as $pair) {
		if (empty($pair)) {
			continue;
		}
		list($attribute,$value) = split("=",$pair,2);
		$temp[$attribute] = str_replace("\"","",$value);
	}
	
	$attPairs = $temp;
	
	$output = "Error in snippet.php";
	
	if (isset($attPairs['id']) && isset($attPairs['field'])) {
		$output = $snippet->display($attPairs['id'],$attPairs['field']);
	}
	
	return($output);
}

class Snippet {
	
	private $engine           = NULL;
	private $table            = NULL;
	private $field            = NULL;
	private $metaFields       = NULL;
	private $hiddenMetaFields = NULL;
	
	public $resultURL         = NULL;
	public $textSubmitButton  = "Submit";
	public $textPreviewButton = "Preview";
	public $textResetButton   = "Reset";
	public $snippetURL        = "/snippet.php?id=";
	public $snippetPublicURL  = "/snippetPublic.php?id=";
	
	function __construct($engine,$table,$field=NULL) {
		$this->engine    = $engine;
		$this->table     = $this->engine->openDB->escape($table);
		$this->field     = $this->engine->openDB->escape($field);
		
		// setup default result URL for snippetList
		if (isset($this->engine->cleanGet['HTML']['action'])) {
			$this->resultURL = $_SERVER['PHP_SELF']."?action=".$this->engine->cleanGet['HTML']['action'];
		}
	}
	
	function __destruct() {
	}
	

	
	/* valid Types:
	 ol = ordered List
	 ul = unordered List
	 li = list elements (no parent OL or UL tags)
	 br = <br /> seperated <a>'s 
	
	class is the class that is applied to the snippet list. 
	*/
	public function insertSnippetList($class="we_snippetList",$type="ul",$collapse=FALSE,$showURL=FALSE) {
		
		global $engineVars;
		
		$sql = sprintf("SELECT * FROM %s ORDER BY snippetName",
			$this->table
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error fetching snippets.");
		}
		
		?>
		<script type="text/javascript">
			function snippetAlert(id) {
				var txt = '<?= $engineVars["WEBROOT"].$this->snippetURL ?>'+id+'\n{snippet id='+id+'}';
				alert(txt);
			}
		</script>
		<?php
		
		$output = "";
		
		if ($type == "ul" || $type == "ol") {
			if ($collapse === FALSE) {
				$output .= "<".$type." class=\"".$class."\">";
			}
			else {
				$output .= "<span onclick=\"toggleMenu('".$class."');\" class=\"toggleLink\"><img src=\"".$engineVars['imgListRetractedIcon']."\" id=\"".$class."_img\" width=\"8px\" height=\"8px\" /> Snippet List</span>";
				$output .= "<".$type." id=\"".$class."\" class=\"".$class."\">";
			}
		}
		
		// $jsOutput is built here and inserted in the javascript below
		// we need each snippet entry to be in the array for the info toggle to work
		$jsOutput = "snippetInfoArray['".$class."'] = new Array();\n";
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			
			$jsOutput .= "snippetInfoArray['".$class."'][\"".$row['ID']."_snippet\"] = \"false\";\n";
			
			if ($type == "ul" || $type == "ol" || $type == "li") {
				$output .= "<li>";
			}
			$output .= "<span class=\"deleteSpan\"><a href=\"".$this->resultURL."&amp;deleteID=".$row['ID']."\" onclick=\"return engineDeleteConfirm('".htmlsanitize($row['snippetName'])."');\"><img src=\"".$engineVars['imgDeleteIcon']."\" alt=\"delete\"  style=\"cursor: not-allowed;\"/></a></span>";
			$output .= "&nbsp;";
			if ($showURL === TRUE && $collapse === TRUE) {
				$output .= "<span onclick=\"toggleSnippetInfo('".$row['ID']."_snippet');\" class=\"toggleLink\"><img style=\"cursor: help;\" src=\"".$engineVars['imgListRetractedIcon']."\" id=\"".$row['ID']."_snippet_img\" /> </span>";
			}
			$output .= "<a href=\"".$this->resultURL."&amp;snippetID=".htmlsanitize($row['ID'])."\">".htmlsanitize($row['snippetName'])."</a>";
			if ($showURL === TRUE && $collapse === TRUE) {
				$output .= "<".$type." id=\"".$row['ID']."_snippet\" style=\"display:none\">";
				$output .= "<li>Auth Required: <a href=\"".$engineVars['WEBROOT'].$this->snippetURL.$row['ID']."\">".$engineVars['WEBROOT'].$this->snippetURL.$row['ID']."</a></li>";
				$output .= "<li>Public: <a href=\"".$engineVars['WEBROOT'].$this->snippetPublicURL.$row['ID']."\">".$engineVars['WEBROOT'].$this->snippetPublicURL.$row['ID']."</a></li>";
				$output .= "<li>{snippet field=\"".$this->field."\" id=\"".$row['ID']."\"}</li>";
				$output .= "</".$type.">";
			}
			if ($type == "ul" || $type == "ol" || $type == "li") {
				$output .= "</li>";
			}
			else {
				$output .= "<br />";
			}
		}
		if ($type == "ul" || $type == "ol") {
			$output .= "</".$type.">";
		}
		
		$output .= "
			<script type=\"text/javascript\">
				var ID = '$class';
				
				var temp = document.getElementById(ID);
				if ($.cookie(ID) == null) {
					$.cookie(ID, \"false\", { path: '/admin' });
					temp.style.display = \"none\";
				}
				else {
					visible[ID] = $.cookie(ID);
					if (visible[ID] == \"true\") {
						temp.style.display = \"block\";
						var img = document.getElementById(ID+\"_img\");
						img.src=\"".$engineVars['imgListExpandedIcon']."\";
					}
					else {
						temp.style.display = \"none\";
						var img = document.getElementById(ID+\"_img\");
						img.src=\"".$engineVars['imgListRetractedIcon']."\";
					}
				}
			</script>";
			
		$output .= "
		<script type=\"text/javascript\">
		
		var ID = '$class';
		
		if (window.snippetInfoArray === undefined) {
			var snippetInfoArray = new Array();
		}
		";
		
		$output .= $jsOutput;
	
		$output .= "
		function toggleSnippetInfo(id) {			
			if (snippetInfoArray[ID][id] == \"false\") {
				$('#'+id+'').show('slow');
				snippetInfoArray[ID][id] = \"true\";
				$.cookie(id, \"true\");
				var img = document.getElementById(id+\"_img\");
				img.src=\"/images/minus.gif\";
			}
			else { 
				$('#'+id+'').hide('slow');
				snippetInfoArray[ID][id] = \"false\";
				$.cookie(id, \"false\");
				var img = document.getElementById(id+\"_img\");
				img.src=\"/images/plus.gif\";
			}
		}
		</script>
		";

		return($output);
	}
	
	public function display($id,$field) {
		
		$sql = sprintf("SHOW INDEXES FROM %s",
			$this->table
			);

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error fetching primary key.");
		}
		
		$row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		$key = $row['Column_name'];
		
		$sql = sprintf("SELECT * FROM %s WHERE %s='%s'",
			$this->table,
			$key,
			$this->engine->openDB->escape($id)
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error fetching snippet.");
		}
		
		$row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		
		return($row[$field]);
	}
	
	public function delete($id) {

		$sql = sprintf("SHOW INDEXES FROM %s",
			$this->table
			);

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error fetching primary key.");
		}
		
		$row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC);
		$key = $row['Column_name'];
		
		$sql = sprintf("DELETE FROM %s WHERE %s='%s'",
			$this->table,
			$key,
			$id
			);

		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Error fetching snippet.");
		}
		
		return webHelper_successMsg("Successfully Deleted Snippet");
		
	}
}

?>