<?php
/**
 * EngineAPI fileHandler Module
 * @package EngineAPI\modules\fileHandler
 */
class fileHandler {
	/**
	 * Instance of dbDriver
	 * @var dbDriver
	 */
	private $database;
	/**
	 * Array of allowed file extensions
	 * @var array
	 */
	private $allowedExtensions = array();
	/**
	 * Maximum file size (in bytes)
	 * @var int
	 */
	public $maxSize = 2000000;
	/**
	 * Base filepath
	 * @var null
	 */
	public $basePath;
	/**
	 * Boolean debug flag
	 * Must be FALSE in Production
	 * @var bool
	 */
	public $debug = FALSE;

	/**
	 * Class constructor
	 * @param engineDB $database
	 */
	function __construct($database=NULL) {
		$this->database = ($database instanceof dbDriver) ? $database : db::get('appDB');
	}

	/**
	 * Checks a file against size constraints, invalid extensions, and blank file names
	 *
	 * @param array $files  The file name, type, size, and data, as an array
	 * @param int   $i      The array index of the file, as an integer
	 * @return bool|string
	 **/
	public function validate($files,$i) {
		if(empty($files['name'][$i])){
			return errorHandle::errorMsg("File skipped: No File Name");
		}else{
			$fileSize = dbSanitize($files['size'][$i]);
			$fileName = dbSanitize($files['name'][$i]);

			// file not uploaded correctly, display PHP error
			if($files['error'][$i] == 1)                                      return errorHandle::errorMsg("File skipped: ".$fileName." exceeds the maximum file size set in PHP.");
			if($fileSize > $this->maxSize)                                    return errorHandle::errorMsg("File skipped: ".$fileName." (".displayFileSize($fileSize).") exceeds size limit of ".displayFileSize($this->maxSize).".");
			if(($output = $this->checkAllowedExtensions($fileName)) !== TRUE) return errorHandle::errorMsg("File skipped: ".$output);
		}

		return TRUE;
	}

	/**
	 * Wrapper for retrieval functions
	 *
	 * @param string $type      The retrieval method (database/folder), as a string
	 * @param string $name      The file name, as a string
	 * @param string $location  The path/table name where a file can be found, as a string
	 * @param array  $fields    The field names that the database table uses, as an array
	 * @return bool|array
	 **/
	public function retrieve($type,$name,$location,$fields=NULL) {
		switch(strtolower($type)) {
			case "database":
				return $this->retrieveFromDB($name,$location,$fields);
			case "folder":
				return $this->retrieveFromFolder($name,$location);
			default:
				return FALSE;
		}
	}

	/**
	 * Wrapper for storage functions
	 *
	 * @param string $type      The storage method (database/folder), as a string
	 * @param array $files      The file name, type, size, and data, as an array
	 * @param string $location  The path/table name where the file will be stored, as a string
	 * @param array  $fields    The field names that the database table uses, as an array
	 * @return bool|array
	 **/
	public function store($type,$files,$location,$fields=NULL) {
		$files = $this->normalizeArrayFormat($files);
		switch(strtolower($type)) {
			case "database":
				return $this->storeInDB($files,$location,$fields);
			case "folder":
				return $this->storeInFolder($files,$location);
			default:
				return FALSE;
		}
	}

	/**
	 * Changes array structure to match PHP upload
	 *
	 * @param array $files  The file name, type, size, and data, as an array
	 * @return array
	 **/
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

	/**
	 * Retrieve a file from a directory
	 *
	 * @param string $name      The file name, as a string
	 * @param string $location  The path where the file can be found, as a string
	 * @return bool|array
	 **/
	private function retrieveFromFolder($name,$location) {
		$filePath = $this->basePath."/".$location.'/'.$name;
		if(!file_exists($filePath)){
			if($this->debug === TRUE){
				errorHandle::newError("File does not exist: ".$filePath,errorHandle::DEBUG);
			}
			return FALSE;
		}

		$content        = file_get_contents($filePath);
		$type           = $this->getMimeType($filePath);
		$output['name'] = utf8_encode($name);
		$output['type'] = $type;
		$output['data'] = $content;
		return $output;
	}

	/**
	 * Retrieve a file from a database table
	 *
	 * @param $name   The file name, as a string
	 * @param $table  The table name where the file can be found, as a string
	 * @param $fields The field names that the database table uses, as an array
	 * @return bool
	 */
	private function retrieveFromDB($name,$table,$fields) {

		$select = "";
		foreach ($fields AS $val) {
			$select .= (empty($select)?"":", ").$val;
		}

		$sql = sprintf("SELECT `%s` FROM `%s` WHERE `%s`=? LIMIT 1",
			$select,
			$this->database->escape($table),
			$fields['name']
			);
		$sqlResult = $this->database->query($sql, array($name));

		if (!$sqlResult->rowCount()) {
			if ($this->debug === TRUE) {
				errorHandle::newError($name." not found in ".$table,errorHandle::DEBUG);
			}
			return FALSE;
		}

		$file = $sqlResult->fetch();

		$output['name'] = $file[$fields['name']];
		$output['type'] = $file[$fields['type']];
		$output['data'] = $file[$fields['data']];

		return $output;

	}

	/**
	 * Store files in a database table
	 *
	 * @param array  $files   The file names, types, and data, as an array
	 * @param string $table   The table name where the files will be stored, as a string
	 * @param array  $fields  The field names that the database table uses, as an array
	 * @return bool|string
	 **/
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

			$sql = sprintf("INSERT INTO `%s` SET `%s`=?, `%s`=?, `%s`=?",
				$this->database->escape($table),
				$this->database->escape($fields['name']),
				$this->database->escape($files['name'][$i]),
				$this->database->escape($fields['data'])
				);
			$sqlResult = $this->database->query($sql, array(file_get_contents($files['tmp_name'][$i]),$fields['type'],$fileType));

			if ($sqlResult->errorCode()) {
				if ($this->debug === TRUE) {
					errorHandle::newError("Failed to store ".$fileName." in ".$table,errorHandle::DEBUG);
				}
				$errorMsg .= errorHandle::errorMsg("Failed to store ".$fileName);
			}

		}

		if(!isnull($errorMsg)) return $errorMsg;
		return TRUE;
	}

	/**
	 * Store files in a directory
	 *
	 * @param array  $files   The file names, types, and data, as an array
	 * @param string $folder  The directory path where the files will be stored, as a string
	 * @return bool|string
	 **/
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
				$errorMsg .= errorHandle::errorMsg("Conflicting filename: ".$fileName);
			}
			else {
				if (is_uploaded_file($fileData)) {
					$output = move_uploaded_file($fileData,$location."/".$fileName);
				}
				else {
					$output = $this->copyFile($fileName,$location."/".$fileName);
				}

				if ($output === FALSE) {
					$errorMsg .= errorHandle::errorMsg("Error storing ".$fileName);
				}
			}

		}

		if(!isnull($errorMsg)) return $errorMsg;
		return TRUE;
	}

	/**
	 * Display an HTML form to allow users to upload files to the server
	 *
	 * @param string $name          The name used in the input field, as a string
	 * @param bool   $multiple      Whether or not to allow the form to upload multiple files at the same time, as a boolean
	 * @param bool   $hiddenFields  Names and values to be inserted into the form as hidden fields, as an array
	 * @return string
	 **/
	public function uploadForm($name,$multiple=FALSE,$hiddenFields=NULL) {

		$output = NULL;

		$output .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&uploadID='.(sessionGet("fileUploadID")).'" enctype="multipart/form-data">';
		$output .= '<input type="file" name="'.$name.'[]" id="'.$name.'_fileInsert" '.(($multiple)?'multiple':'').' />';

		if (!isnull($hiddenFields)) {
			foreach($hiddenFields as $I=>$V) {
				$output .= '<input type="hidden" name="'.$V['field'].'" value="'.$V['value'].'" />';
			}
		}

		$output .= sessionInsertCSRF();
		$output .= '<input type="submit" name="'.$name.'_submit" value="Upload" />';
		$output .= '</form>';

		return $output;
	}

	/**
	 * dbInsert() - Unknown
	 *
	 * @todo What is this even being used for?
	 * @deprecated
	 * @param $table
	 * @param $fields
	 * @return bool|string
	 */
	public function dbInsert($table,$fields) {
		deprecated();
		$sqlStr = NULL;

		foreach ($fields as $val) {
			if(!empty($val['field'])) {

				$sqlStr .= isnull($sqlStr) ? "" : ", ";
				$sqlStr .= '`'.dbSanitize($val['field'])."` = '".dbSanitize($val['value'])."'";

			}
		}

		$sql = sprintf("INSERT INTO `%s` SET %s",
			$this->database->escape($table),
			$sqlStr
			);
		$sqlResult = $this->database->query($sql);

		if ($sqlResult->errorCode()) {
			return errorHandle::errorMsg("Insert Error.");
		}

		return TRUE;

	}

	/**
	 * Copy a file from one database table to another
	 *
	 * @param string $oldTable       The source table name, as a string
	 * @param string $newTable       The destination table name, as a string
	 * @param array  $fields         Field names and values for the tables, as an array
	 * @param bool   $mysqlFileName  Whether or not to regenerate the file name stored in the new table, as a boolean
	 * @return bool|array
	 **/
	public function copyDbRecord($oldTable,$newTable,$fields,$mysqlFileName=TRUE) {

		// Get fields from Old Table
		$oldTableFields = array();

		$sql = sprintf("SHOW FIELDS FROM `$oldTable`");
		$sqlResult = $this->database->query($sql);
		while($row = $sqlResult->fetch()) {
			$oldTableFields[$row['Field']] = TRUE;
		}

		// Get fields from New Table
		$newTableFields = array();

		$sql = sprintf("SHOW FIELDS FROM `$newTable`");
		$sqlResult = $this->database->query($sql);
		while($row = $sqlResult->fetch()) {
			$newTableFields[$row['Field']] = TRUE;
		}


		// Grab data from oldTable from the database
		$sql = sprintf("SELECT * FROM `%s` WHERE `%s` = ?",
			$this->database->escape($oldTable),
			$this->database->escape($fields['id']['field'])
			);
		$sqlResult = $this->database->query($sql, array($fields['id']['value']));

		$row = $sqlResult->fetch();

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
		$insertFieldNames = "(`".(implode("`,`",(array_keys($oldTableFields))))."`)";

		// Grab the values from the oldTable that will be inserted in the newTable
		foreach (array_keys($oldTableFields) as $I) {
			$vals[] = "'".dbSanitize($row[$I])."'";
		}
		$insertFieldVals = implode(",",$vals);

		// insert record into new table
		$sql = sprintf("INSERT INTO `%s` %s VALUES(%s)",
			$this->database->escape($newTable),
			$insertFieldNames,
			$insertFieldVals
			);
		$sqlResult = $this->database->query($sql);

		if ($sqlResult->errorCode()) {
			return(FALSE);
		}

		$return       = array();
		$return['id'] = $sqlResult->insertId();

		// delete record from old table
		$sql = sprintf("DELETE FROM `%s` WHERE `%s` = ? LIMIT 1",
			$this->database->escape($oldTable),
			$this->database->escape($fields['id']['field'])
			);
		$sqlResult = $this->database->query($sql, array($oldID));

		// generate new filename based on $newID
		if ($mysqlFileName === TRUE) {

			$output = $this->genMysqlFileName($newTable,$fields,$return['id']);

			if ($output === FALSE) {
				return errorHandle::errorMsg("Update Error.");
			}

			$return['fileName'] = $output['fileName'];
			$return['paddedID'] = $output['paddedID'];
			$return['dir']      = $output['dir'];

			$return["oldFileName"] = $row['directory']."/".$row['fileName'];
			$return["newFileName"] = $output['dir']."/".$output['fileName'];

		}

		return $return;

	}

	/**
	 * Generate a new file name based on a given ID
	 *
	 * @param string $table  The source table name, as a string
	 * @param string $fields The destination table name, as a string
	 * @param string $ID     Field names and values for the tables, as an array
	 * @param bool $updateDB Whether or not to regenerate the file name stored in the new table, as a boolean
	 * @return array|bool
	 */
	public function genMysqlFileName($table,$fields,$ID,$updateDB=TRUE) {

		$paddedID = str_pad($ID, 3, "0", STR_PAD_LEFT);
		$ext = pathinfo($fields['name']['value'], PATHINFO_EXTENSION );
		$dir = $this->basePath."/".$paddedID[0]."/".$paddedID[1]."/".$paddedID[2];

		$newName = $paddedID.".".$ext;

		if ($updateDB === TRUE) {
			$sql = sprintf("UPDATE `%s` SET `%s` = ?, `%s` = ? WHERE `%s` = ?",
				$this->database->escape($table),
				$this->database->escape($fields['name']['field']),
				$this->database->escape($fields['dir']['field']),
				$this->database->escape($fields['id']['field'])
				);
			$sqlResult = $this->database->query($sql, array($newName,$dir,$ID));

			if ($sqlResult->errorCode()) {
				return FALSE;
			}
		}

		$return = array();
		$return['fileName'] = $newName;
		$return['paddedID'] = $paddedID;
		$return['dir']      = $dir;

		return $return;

	}

	/**
	 * Move a file
	 *
	 * @param string $sourceFile  The source file path, as a string
	 * @param string $destFile    The destination file path, as a string
	 * @return bool|string
	 **/
	public function moveFile($sourceFile,$destFile) {

		if (!file_exists($sourceFile)) {
			return errorHandle::errorMsg("Error moving file: $sourceFile source not found");
		}
		if (file_exists($destFile)) {
			return errorHandle::errorMsg("Error moving file: Conflicting filename in target directory");
		}

		return rename($sourceFile,$destFile);

	}

	/**
	 * Copy a file
	 *
	 * @param string $sourceFile  The source file path, as a string
	 * @param string $destFile    The destination file path, as a string
	 * @return bool|string
	 **/
	public function copyFile($sourceFile,$destFile) {
		if (!file_exists($sourceFile)) {
			return errorHandle::errorMsg("Error copying file: $sourceFile source not found");
		}
		if (file_exists($destFile)) {
			return errorHandle::errorMsg("Error copying file: Conflicting filename in target directory");
		}

		return copy($sourceFile,$destFile);
	}

	/**
	 * Delete a file
	 *
	 * @param string $filePath  The file path of the file to be deleted, as a string
	 * @return bool|string
	 **/
	public function deleteFile($filePath) {
		if (file_exists($filePath)) {
			return unlink($filePath);
		}
		return errorHandle::errorMsg("Error deleting file: $filePath");
	}

	/**
	 * Display a file -- sets session variables then calls displayFileInline() or redirects to the download page to ensure nothing is displayed before the headers
	 *
	 * @param array  $file     The file name, type, and data, as an array
	 * @param string $display  The method of display (inline|window|download), as a string
	 * @return bool|string
	 **/
	public function displayFile($file,$display=NULL) {

		$enginevars = enginevars::getInstance();

		if (isnull($display)) {
			$display = "window"; // set to default
		}

		$id = rand();

		$_SESSION['fileHandler_'.$id]['fileName'] = $file['name'];
		$_SESSION['fileHandler_'.$id]['fileType'] = $file['type'];
		$_SESSION['fileHandler_'.$id]['fileData'] = $file['data'];
		$_SESSION['fileHandler_'.$id]['display']  = $display;

		switch ($display) {
			case "inline":
				return self::displayFileInline($file,$id);
				break;
			case "download":
			case "window":
			default:
				header("Location: " . $enginevars->get("downloadPage").'?id='.$id);
		}

		return FALSE;

	}

	/**
	 * Display an inline file
	 *
	 * @param $file The file name, type, and data, as an array
	 * @param $id
	 * @return string
	 */
	private function displayFileInline($file,$id) {
		$enginevars = enginevars::getInstance();

		if(strpos($file['type'],'image') !== FALSE){
			$output = '<img src="'.$enginevars->get("downloadPage").'?id='.$id.'" />';
		}else{
			$output = $file['data'];
		}

		return $output;
	}

	/**
	 * Display a search form
	 *
	 * @param array $extensions A list of extensions to display in the form, as an array
	 * @return string
	 */
	public function displaySearchForm($extensions=array()) {

		if (is_empty($extensions)) {
			$extensions = $this->getExtensionsInFolder();
			natcasesort($extensions);
		}

		$limits = array(1=>"Bytes", 1000=>"KB", 1000000=>"MB", 1000000000=>"GB");

		$lowSizeLimit  = isset($_POST['HTML']['lowSizeLimit'])  ? $_POST['HTML']['lowSizeLimit']  : NULL;
		$lowSizeUnit   = isset($_POST['HTML']['lowSizeUnit'])   ? $_POST['HTML']['lowSizeUnit']   : 1000;
		$highSizeLimit = isset($_POST['HTML']['highSizeLimit']) ? $_POST['HTML']['highSizeLimit'] : NULL;
		$highSizeUnit  = isset($_POST['HTML']['highSizeUnit'])  ? $_POST['HTML']['highSizeUnit']  : 1000;

		$fileName = isset($_POST['HTML']['fileName']) ? $_POST['HTML']['fileName'] : NULL;
		$fileType = isset($_POST['HTML']['fileType']) ? $_POST['HTML']['fileType'] : NULL;

		$output  = '<form action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" method="post">';
		$output .= '<table>';
		$output .= '<tr>';
		$output .= '<td>File Name</td>';
		$output .= '<td><input type="text" name="fileName" value="'.$fileName.'" /></td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td>Type</td>';
		$output .= '<td>';
		$output .= '<select name="fileType">';
		$output .= '<option value="any">Any</option>';
		foreach ($extensions as $ext) {
			$ext = htmlSanitize($ext);
			$output .= '<option value="'.$ext.'"'.(($ext==$fileType)?' selected':'').'>'.$ext.'</option>';
		}
		$output .= '</select>';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td>Size Limit</td>';
		$output .= '<td>';
		$output .= '<input type="text" name="lowSizeLimit" value="'.$lowSizeLimit.'" size="10" />';
		$output .= '<select name="lowSizeUnit">';
		foreach ($limits as $k => $v) {
			$output .= '<option value="'.$k.'"'.(($k==$lowSizeUnit)?' selected':'').'>'.$v.'</option>';
		}
		$output .= '</select>';
		$output .= 'To';
		$output .= '<input type="text" name="highSizeLimit" value="'.$highSizeLimit.'" size="10" />';
		$output .= '<select name="highSizeUnit">';
		foreach ($limits as $k => $v) {
			$output .= '<option value="'.$k.'"'.(($k==$highSizeUnit)?' selected':'').'>'.$v.'</option>';
		}
		$output .= '</select>';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td colspan="2">';
		$output .= '{engine name="csrf"}';
		$output .= '<input type="submit" name="fileSubmit" value="Submit" />';
		$output .= '</td>';
		$output .= '</tr>';
		$output .= '</table>';
		$output .= '</form>';

		return $output;

	}

	/**
	 * Recursively search for a file
	 *
	 * @param array  $attPairs  The file name, type, size, etc. to searchf or, as an array
	 * @param string $folder    The base folder to look through past basePath, as a string
	 * @return array
	 **/
	public function search($attPairs, $folder=NULL) {

		$results = array();
		$dir     = $this->basePath."/".$folder;
		$files   = scandir($dir);

		foreach ($files as $file) {

			// ignore .files
			if ($file[0] == '.') {
				continue;
			}

			if (is_dir($dir."/".$file)) {
				// if it's a directory, recurse into it
				$results = array_merge($results, $this->search($attPairs,$folder."/".$file));
			}
			else if (!is_dir($dir."/".$file)) {

				// physical file properties
				$tmp         = array();
				$tmp['name'] = $file;
				$tmp['path'] = $dir."/".$file;
				$tmp['size'] = filesize($dir."/".$file);
				$tmp['type'] = pathinfo($file, PATHINFO_EXTENSION);

				// if lookup is defined, use the lookup value instead of the file value
				// used when a value is stored in the database instead
				if (isset($attPairs['lookup']) && !is_empty($attPairs['lookup'])) {
					foreach ($attPairs['lookup'] as $key => $value) {
						$sql = sprintf("SELECT `%s` FROM `%s`.`%s` WHERE `%s`=? LIMIT 1",
							$this->database->escape($value['field']),
							$this->database->escape($value['database']),
							$this->database->escape($value['table']),
							$this->database->escape($value['matchOn'])
							);
						$sqlResult = $this->database->query($sql, array($file));

						if ($sqlResult->rowCount() == 0) {
							continue(2);
						}

						$row = $sqlResult->fetch(PDO::FETCH_NUM);
						$tmp[$key] = $row[0];
					}

				}

				// skip file if searched string is not contained in file name
				if (isset($attPairs['name']) && !is_empty($attPairs['name'])) {
					if (strpos(strtolower($tmp['name']), strtolower($attPairs['name'])) === FALSE) {
						continue;
					}
				}

				// skip file if searched type is different
				if (isset($attPairs['type']) && !is_empty($attPairs['type'])) {
					if (strtolower($attPairs['type']) != strtolower($tmp['type'])) {
						continue;
					}
				}

				// skip file if file size is smaller than the searched low limit
				if (isset($attPairs['size']['low']) && !is_empty($attPairs['size']['low']) && $attPairs['size']['low'] != 0) {
					if ($tmp['size'] < $attPairs['size']['low']) {
						continue;
					}
				}

				// skip file if file size is larger than the searched high limit
				if (isset($attPairs['size']['high']) && !is_empty($attPairs['size']['high']) && $attPairs['size']['high'] != 0) {
					if ($tmp['size'] > $attPairs['size']['high']) {
						continue;
					}
				}

				$results[] = $tmp;

			}

		}

		return $results;

	}

	/**
	 * Recursively find all the file extentions within a folder and return as an array
	 *
	 * @param string $folder  The base folder to look through past basePath, as a string
	 * @return array
	 **/
	public function getExtensionsInFolder($folder=NULL) {

		$extArr = array();
		$dir    = $this->basePath."/".$folder;
		$files  = scandir($dir);

		foreach ($files as $file) {

			if ($file[0] == '.') {
				continue;
			}

			if (is_dir($dir."/".$file)) {
				$extArr = array_unique(array_merge($extArr, $this->getExtensionsInFolder($folder."/".$file)));
			}
			else if (!is_dir($dir."/".$file)) {
				$extArr[] = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			}

		}

		return $extArr;

	}

	/**
	 * Add a file extension to the allowed list, duplicates are simply ignored
	 *
	 * @param string $extension  The extension to be added, as a string
	 * @return bool
	 **/
	public function addAllowedExtension($extension) {

		if (!in_array($extension,$this->allowedExtensions)) {
			$this->allowedExtensions[] = $extension;
		}

		return TRUE;

	}

	/**
	 * Determine that a given file is allowed based on the extension
	 *
	 * @param string $fileName  The file name to be parsed, as a string
	 * @return bool|string
	 **/
	private function checkAllowedExtensions($fileName) {

		$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

		if (!in_array($fileExt,$this->allowedExtensions)) {
			return errorHandle::errorMsg($fileName.": Invalid file type \"".$fileExt."\"");
		}

		return TRUE;

	}

	/**
	 * Get the mime type for a given file -- tries to use finfo_open(), then mime_content_type(), then defaults to returnMIMEType()
	 *
	 * @param string $file_path
     * @param string $defaultMimeType
     * @param string $realFilename
	 * @return string
	 **/
	public function getMimeType($file_path, $defaultMimeType='application/force-download', $realFilename=NULL){
        
        $enginevars = enginevars::getInstance();

        $mimeType = $defaultMimeType;

        // if $realFilename is given, we need to create a shadow-copy of the file to examine the copy
        if(isset($realFilename)){
            $tmpFilename = sys_get_temp_dir() . "/" . md5(microtime(TRUE)) . "_$realFilename";
            copy($file_path, $tmpFilename);
            $file_path = $tmpFilename;
        }

        if(class_exists('finfo')){
            if(isPHP('5.3')){
                // $fInfo = new finfo(FILEINFO_MIME_TYPE);

                @$fInfo = new finfo(FILEINFO_MIME_TYPE, $enginevars->get("mimeFilename"));
                @$mimeType = $fInfo->file($file_path);
                print "<pre>";
                var_dump($mimeType);
                print "</pre>";
                if($mimeType === FALSE){
	                $fInfo = new finfo(FILEINFO_MIME_TYPE);
	                $mimeType = $fInfo->file($file_path);
                }
            }else{
                @$fInfo = new finfo(FILEINFO_MIME, $enginevars->get("mimeFilename"));
                @$mimeData = $fInfo->file($file_path);
                if($mimeData === FALSE){
	                $fInfo = new finfo(FILEINFO_MIME);
	                $mimeData = $fInfo->file($file_path);
                }
                $mimeParts = explode(';', $mimeData);
                $mimeType = trim($mimeParts[0]);
            }
        }elseif(function_exists('mime_content_type')){
            $mimeType = mime_content_type($file_path);
        }else{
            $mimeType = $this->returnMIMEType($file_path);
        }

        // Before we go, if $realFilename is given, we need to delete the shadow-copy of the file we've been working with
        if (isset($realFilename)) unlink($file_path);

        return $mimeType;
    }

	/**
	 * Get the mime type for a given file without using any php built-in functionality, as a failsafe
	 *
	 * @param string $filename  The file name to be parsed, as a string
	 * @return string
	 **/
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