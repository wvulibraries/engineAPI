<?

class fileHandler {
	
	private $engine = NULL;
	private $file = array();
	private $allowedExtensions = array();
	
	public $maxSize = 2097152; // 2mb
	public $basePath = NULL;
	
	
	function __construct($engine) {
		
		$this->engine = $engine;
		
	}
	
	function __destruct() {
	}
	
	public function validate($files,$i) {
		
		if (empty($files['name'][$i])) {
			return webHelper_errorMsg("File skipped: No File Name");
		}
		else {
			
			$fileSize = dbSanitize($files['size'][$i]);
			$fileName = dbSanitize($files['name'][$i]);
			
			// file not uploaded correctly, display PHP error
			if ($files['error'][$i] == 1) {
				return webHelper_errorMsg("File skipped: ".$fileName." exceeds the maximum file size set in PHP.");
			}
			
			if ($fileSize > $this->maxSize) {
				return webHelper_errorMsg("File skipped: ".$fileName." (".$this->displayFileSize($fileSize).") exceeds size limit of ".$this->displayFileSize($this->maxSize).".");
			}
			
			if (($output = $this->checkAllowedExtensions($fileName)) !== TRUE) {
				return webHelper_errorMsg("File skipped: ".$output);
			}
			
		}
		
		return TRUE;
		
	}
	
	public function retrieve($type,$name,$location,$fields=NULL) {
		
		switch($type) {
			case "database":
				return $this->retrieveFromDB($name,$location,$fields);
				
			case "folder":
				return $this->retrieveFromFolder($name,$location);
				
			default:
				return FALSE;
		}
		
	}
	
	public function store($type,$files,$location,$fields=NULL) {
		
		$files = $this->normalizeArrayFormat($files);
		
		switch ($type) {
			case "database":
				return $this->storeInDB($files,$location,$fields);
				
			case "folder":
				return $this->storeInFolder($files,$location);
				
			default:
				return FALSE;
		}
		
	}
	
	private function normalizeArrayFormat($files) {
		
		if (!is_array($files['name'])) {
			$tmp = array();
			foreach ($files as $key => $val) {
				$tmp[$key][0] = $val;
			}
			$files = $tmp;
		}
		
		return $files;
		
	}
	
	private function retrieveFromFolder($name,$location) {
		
		$filePath = $this->basePath."/".$location.'/'.$name;
		
		if (!file_exists($filePath)) {
			return FALSE;
		}
		
		$content = file_get_contents($filePath);
		$type = $this->getMimeType($filePath);
		
		$output['name'] = utf8_encode($name);
		$output['type'] = $type;
		$output['data'] = $content;
		
		return $output;
		
	}
	
	private function retrieveFromDB($name,$table,$fields) {
		
		$select = "";
		foreach ($fields AS $val) {
			$select .= (empty($select)?"":", ").$val;
		}
		
		$sql = sprintf("SELECT %s FROM %s WHERE %s='%s' LIMIT 1",
			$select,
			$this->engine->openDB->escape($this->engine->dbTables($table)),
			$fields['name'],
			$name
			);
		$engine->openDB->sanitize = FALSE;
		$sqlResult                = $engine->openDB->query($sql);
		
		if ($sqlResult['affectedRows'] == 0) {
			return FALSE;
		}
		
		$file = mysql_fetch_assoc($sqlResult['result']);
		
		$output['name'] = $file[$fields['name']];
		$output['type'] = $file[$fields['type']];
		$output['data'] = $file[$fields['data']];
		
		return $output;
		
	}
	
	private function storeInDB($files,$table,$fields) {
		
		$errorMsg  = NULL;
		$fileCount = count($files['name']);
		
		for ($i = 0; $i < $fileCount; $i++) {
			
			if (($valid = $this->validate($files,$i)) !== TRUE) {
				$errorMsg .= $valid;
				continue;
			}
			
			$fileName = $files['name'][$i];
			$fileType = $files['type'][$i];
			$fileData = file_get_contents($files['tmp_name'][$i]);
			
			$sql = sprintf("INSERT INTO %s SET %s='%s', %s='%s', %s='%s'",
				$this->engine->openDB->escape($this->engine->dbTables($table)),
				$this->engine->openDB->escape($fields['name']),
				$this->engine->openDB->escape($files['name'][$i]),
				$this->engine->openDB->escape($fields['data']),
				$this->engine->openDB->escape(file_get_contents($files['tmp_name'][$i])),
				$this->engine->openDB->escape($fields['type']),
				$this->engine->openDB->escape($fileType)
				);
			$this->engine->openDB->sanitize = FALSE;
			$sqlResult                      = $this->engine->openDB->query($sql);
			
			if (!$sqlResult['result']) {
				$errorMsg .= webHelper_errorMsg("Failed to upload ".$fileName);
			}
			
		}
		
		if (!isnull($errorMsg)) {
			return $errorMsg;
		}
		else {
			return TRUE;
		}
		
	}
	
	private function storeInFolder($files,$folder) {
		
		$errorMsg = NULL;
		
		$location = $this->basePath."/".$folder;
		if (!is_dir($location)) {
			mkdir($location, 0700, TRUE);
		}

		$fileCount = count($files['name']);
		
		for ($i = 0; $i < $fileCount; $i++) {
			
			if (($valid = $this->validate($files,$i)) !== TRUE) {
				$errorMsg .= $valid;
				continue;
			}
			
			$fileName = utf8_decode($files['name'][$i]);
			$fileType = $files['type'][$i];
			$fileData = $files['tmp_name'][$i];
			
			if (file_exists($location."/".$fileName)) {
				$errorMsg .= webHelper_errorMsg("Conflicting filename: ".$fileName);
			}
			else {
				if (is_uploaded_file($fileData)) {
					$output = move_uploaded_file($fileData,$location."/".$fileName);
				}
				else {
					$output = $this->copyFile($fileName,$location."/".$fileName);
				}
				
				if ($output === FALSE) {
					$errorMsg .= webHelper_errorMsg("Error storing ".$fileName);
				}
			}
			
		}
		
		if (!isnull($errorMsg)) {
			return $errorMsg;
		}
		else {
			return TRUE;
		}
		
	}
	
	public function uploadForm($name,$multiple=FALSE,$hiddenFields=NULL) {
		
		$output = NULL;
		
		$output .= "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&amp;uploadID=".(sessionGet("fileUploadID"))."\" enctype=\"multipart/form-data\">";
		$output .= "<input type=\"file\" name=\"".$name."[]\" id=\"".$name."_fileInsert\" ".(($multiple)?"multiple":"")." />";
		
		if (!isnull($hiddenFields)) {
			foreach($hiddenFields as $I=>$V) {
				$output .= "<input type=\"hidden\" name=\"".$V['field']."\" value=\"".$V['value']."\" />";
			}
		}
		
		$output .= sessionInsertCSRF();
		$output .= "<input type=\"submit\" name=\"".$name."_submit\" value=\"Upload\" />";
		$output .= "</form>";
		
		return $output;
	}
	
	public function dbInsert($table,$fields) {
		
		$sqlStr = NULL;
		
		foreach ($fields as $val) {
			if(!empty($val['field'])) {
				
				$sqlStr .= (isnull($sqlStr)?"":", ").dbSanitize($val['field'])." = '".dbSanitize($val['value'])."'";
				
			}
		}
		
		$sql = sprintf("INSERT INTO %s SET %s",
			$this->engine->openDB->escape($this->engine->dbTables($table)),
			$sqlStr
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Insert Error: ");
		}
		
		return TRUE;		
		
	}
	
	public function copyDbRecord($oldTable,$newTable,$fields,$mysqlFileName=TRUE) {
		
		// Get fields from Old Table
		$oldTableFields = array();
		
		$sql = sprintf("SHOW FIELDS FROM $oldTable");
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($sql);
		while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			$oldTableFields[$row['Field']] = TRUE;
		}
		
		// Get fields from New Table
		$newTableFields = array();
		
		$sql = sprintf("SHOW FIELDS FROM $newTable");
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($sql);
		while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			$newTableFields[$row['Field']] = TRUE;
		}
		
		
		// Grab data from oldTable from the database
		$sql = sprintf("SELECT * FROM %s WHERE %s = '%s'",
			$this->engine->openDB->escape($this->engine->dbTables($oldTable)),
			$this->engine->openDB->escape($fields['id']['field']),
			$this->engine->openDB->escape($fields['id']['value'])
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);
		
		$row = mysql_fetch_assoc($sqlResult['result']);
		
		// save and nullify old ID
		$oldID = $row[$fields['id']['field']];
		$row[$fields['id']['field']] = NULL;
		
		// Remove the ID, so we aren't copying it over
		unset($oldTableFields[$fields['id']['field']]);
		
		// Get rid of fields that are in oldTable but NOT in newTable
		$nullFields = array_diff_key($oldTableFields,$newTableFields);
		foreach ($nullFields as $I=>$V) {
			unset($oldTableFields[$I]);
		}
		
		// Build the list of fields we are inserting
		$insertFieldNames = "(".(implode(",",array_keys($oldTableFields))).")";
		
		// Grab the values from the oldTable that will be inserted in the newTable
		foreach (array_keys($oldTableFields) as $I) {
			$vals[] = "'".$row[$I]."'";
		}
		$insertFieldVals = implode(",",$vals);
			
		// insert record into new table
		$sql = sprintf("INSERT INTO %s %s VALUES(%s)",
			$this->engine->openDB->escape($this->engine->dbTables($newTable)),
			$insertFieldNames,
			$insertFieldVals
			);
		$this->engine->openDB->sanitize = FALSE;
		
		$sqlResult = $this->engine->openDB->query($sql);

		if (!$sqlResult['result']) {
			return(FALSE);
		}
		
		$return       = array();
		$return['id'] = $sqlResult['id'];
		
		// delete record from old table
		$sql = sprintf("DELETE FROM %s WHERE %s = '%s' LIMIT 1",
			$this->engine->openDB->escape($this->engine->dbTables($oldTable)),
			$this->engine->openDB->escape($fields['id']['field']),
			$oldID
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);

		// generate new filename based on $newID
		if ($mysqlFileName === TRUE) {
			
			$output = $this->genMysqlFileName($newTable,$fields,$return['id']);
			
			if ($output === FALSE) {
				return webHelper_errorMsg("Update Error.");
			}
			
			$return['fileName'] = $output['fileName'];
			$return['paddedID'] = $output['paddedID'];
			$return['dir']      = $output['dir'];
			
			$return["oldFileName"] = $row['directory']."/".$row['fileName'];
			$return["newFileName"] = $output['dir']."/".$output['fileName'];
			
		}
		
		return $return;
		
	}
	
	public function genMysqlFileName($table,$fields,$ID,$updateDB=TRUE) {
		
		$paddedID = str_pad($ID, 3, "0", STR_PAD_LEFT);
		$ext = pathinfo($fields['name']['value'], PATHINFO_EXTENSION );
		$dir = $this->basePath."/".substr($paddedID,"0","1")."/".substr($paddedID,"1","1")."/".substr($paddedID,"2","1");
		
		$newName = $paddedID.".".$ext;
		
		if ($updateDB === TRUE) {
			$sql = sprintf("UPDATE %s SET %s = '%s', %s = '%s' WHERE %s = '%s'",
				$this->engine->openDB->escape($this->engine->dbTables($table)),
				$this->engine->openDB->escape($fields['name']['field']),
				$this->engine->openDB->escape($newName),
				$this->engine->openDB->escape($fields['dir']['field']),
				$this->engine->openDB->escape($dir),
				$this->engine->openDB->escape($fields['id']['field']),
				$this->engine->openDB->escape($ID)
				);
			$this->engine->openDB->sanitize = FALSE;
			$sqlResult                      = $this->engine->openDB->query($sql);
		
			if (!$sqlResult['result']) {
				return FALSE;
			}
		}
		
		$return = array();
		$return['fileName'] = $newName;
		$return['paddedID'] = $paddedID;
		$return['dir']      = $dir;
		
		return $return;
		
	}
	
	public function moveFile($oldFileName,$newFileName) {
		
		if (!file_exists($oldFileName)) {
			return webHelper_errorMsg("Error moving file: $newFileName source not found");
		}
		if (file_exists($newFileName)) {
			return webHelper_errorMsg("Error moving file: Conflicting filename in target directory");
		}
		
		return rename($oldFileName,$newFileName);
		
	}
	
	public function copyFile($sourceFile,$destFile) {
		if (!file_exists($sourceFile)) {
			return webHelper_errorMsg("Error copying file: $sourceFile source not found");
		}
		if (file_exists($destFile)) {
			return webHelper_errorMsg("Error copying file: Conflicting filename in target directory");
		}
		
		return copy($sourceFile,$destFile);
	}
	
	public function deleteFile($fileName) {
		if (file_exists($fileName)) {
			return unlink($fileName);
		}
		return webHelper_errorMsg("Error deleting file: $fileName");
	}
	
	public function displayFile($file,$display=NULL) {
		
		global $engineVars;
		
		if (isnull($display)) {
			$display = "window";
		}
		
		sessionSet("FMfileName",$file['name']);
		sessionSet("FMfileType",$file['type']);
		sessionSet("FMfileData",$file['data']);
		sessionSet("FMdisplay",$display);
		
		switch ($display) {
			case "inline":
				return $this->displayFileInline($file);
				break;
			case "download":
			case "window":
			default:
				header("Location: " . $engineVars['downloadPage']);
		}
		
	}
	
	private function displayFileInline($file) {
		
		global $engineVars;
		
		if (strpos($file['type'],'image') !== FALSE) {
			$output = "<img src=\"".$engineVars['downloadPage']."\" />";
		}
		else {
			$output = $file['data'];
		}
		
		return $output;
		
	}
	
	private function displayFileSize($filesize){
		
		if (is_numeric($filesize)) {
			$decr = 1024;
			$step = 0;
			$prefix = array('Byte','KB','MB','GB','TB','PB');
			
			while (($filesize / $decr) > 0.9) {
				$filesize = $filesize / $decr;
				$step++;
			}
			
			return round($filesize,2).' '.$prefix[$step];
		}
		else {
			return 'NaN';
		}
	}
	
	public function addAllowedExtension($extension) {
		
		if (!in_array($extension,$this->allowedExtensions)) {
			$this->allowedExtensions[] = $extension;
		}
		
		return TRUE;
		
	}
	
	private function checkAllowedExtensions($fileName) {
		
		$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
		
		if (!in_array($fileExt,$this->allowedExtensions)) {
			return ($fileName.": Invalid file type \"".$fileExt."\"");
		}
		
		return TRUE;
		
	}
	
	private function getMimeType($file_path) {
		
		$mtype = '';
		
		if (function_exists('finfo_file')){
			$finfo = finfo_open(FILEINFO_MIME);
			$mtype = finfo_file($finfo, $file_path);
			finfo_close($finfo);  
		}
		else if (function_exists('mime_content_type')){
			$mtype = mime_content_type($file_path);
		}
		else {
	  		$mtype = $this->returnMIMEType($file_path);
		}
		
		if ($mtype == ''){
			$mtype = "application/force-download";
		}
		
		return $mtype;
		
	}
	
	private function returnMIMEType($filename) {
		
		preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
		
		if (!isset($fileSuffix[1])) {
			return "unknown";
		}

		switch (strtolower($fileSuffix[1])) {
			case "js":
				return "application/x-javascript";
				
			case "json":
				return "application/json";
				
			case "jpg":
			case "jpeg":
			case "jpe":
				return "image/jpg";
				
			case "png":
			case "gif":
			case "bmp":
			case "tiff":
				return "image/".strtolower($fileSuffix[1]);
				
			case "css":
				return "text/css";
				
			case "xml":
				return "application/xml";
				
			case "doc":
			case "docx":
				return "application/msword";
				
			case "xls":
			case "xlt":
			case "xlm":
			case "xld":
			case "xla":
			case "xlc":
			case "xlw":
			case "xll":
				return "application/vnd.ms-excel";
				
			case "ppt":
			case "pps":
				return "application/vnd.ms-powerpoint";
				
			case "rtf":
				return "application/rtf";
				
			case "pdf":
				return "application/pdf";
				
			case "html":
			case "htm":
			case "php":
				return "text/html";
				
			case "txt":
				return "text/plain";
				
			case "mpeg":
			case "mpg":
			case "mpe":
				return "video/mpeg";
				
			case "mp3":
				return "audio/mpeg3";
				
			case "wav":
				return "audio/wav";
				
			case "aiff":
			case "aif":
				return "audio/aiff";
				
			case "avi":
				return "video/msvideo";
				
			case "wmv":
				return "video/x-ms-wmv";
				
			case "mov":
				return "video/quicktime";
				
			case "zip":
				return "application/zip";
				
			case "tar":
				return "application/x-tar";
				
			case "swf":
				return "application/x-shockwave-flash";
				
			default:
				return "unknown/" . trim($fileSuffix[0], ".");
		}
	}
	
}

?>