<?php
// Disable php timeout settings
ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);

// Launch engine
include("/home/library/phpincludes/engine/engineAPI/3.0/engine.php");
$engine = EngineAPI::singleton();


// Send emails, pausing 15 seconds between each one
// Uses command line arguments:
// -id=foo = "sendID" in the database
// -d=foo  = database name
// -t=foo  = table name
// -u=foo  = database username
// -p=foo  = database password
// -s=foo  = database server
// -P=foo  = database port
// -S=foo  = sleep duration


// Set any defaults
$sleep = 15;


array_shift($argv); // Remove filename so all remaining in stack are true arguments
foreach ($argv as $arg) {
	if (substr($arg,0,1) == '-') {
		
		$eqPos = strpos($arg,'=');
		$key   = substr($arg,1,$eqPos-1);
		$value = substr($arg,$eqPos+1);
		
		switch ($key) {
			case "id": $sendID   = $value; break;
			case "d":  $database = $value; break;
			case "t":  $table    = $value; break;
			case "u":  $engine->dbConnect("username",$value,TRUE); break;
			case "p":  $engine->dbConnect("password",$value,TRUE); break;
			case "s":  $engine->dbConnect("server",$value,TRUE); break;
			case "P":  $engine->dbConnect("port",$value,TRUE); break;
			case "S":  $sleep    = $value; break;
		}
	}
}

$engine->dbConnect("database",$database,TRUE);  // needs to be last


$fileHandler           = new fileHandler();
$fileHandler->basePath = "/tmp/engineBulkEmail";
$folder                = $sendID;
$dir                   = $fileHandler->basePath."/".$folder;

$files = array();

// if no directory, no files were attached
if (is_dir($dir)) {
	$fileNames = scandir($dir);
	if (is_array($fileNames)) {
		foreach ($fileNames as $val) {
			if ($val != '.' && $val != '..') {
				$files[] = $fileHandler->retrieve("folder",$val,$folder);
			}
		}
	}
}


$sql = sprintf("SELECT * FROM `%s` WHERE sendID='%s'",
	$engine->openDB->escape($table),
	$engine->openDB->escape($sendID)
	);
$sqlResult = $engine->openDB->query($sql);

if ($sqlResult['result']) {
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		
		$mail = new mailSender();
		
		$mail->addRecipient($row['recipient']);
		$mail->addSender($row['sender']);
		$mail->addSubject($row['subject']);
		$mail->addBody($row['body']);
		
		foreach ($files as $file) {
			$mail->addAttachment($dir."/".$file['name'], $file['name'], "base64", $file['type']);
		}
		
		$mail->sendEmail();
		sleep($sleep);
		
	}
}


$sql = sprintf("DELETE FROM `%s` WHERE sendID='%s'",
	$engine->openDB->escape($table),
	$engine->openDB->escape($sendID)
	);
$sqlResult = $engine->openDB->query($sql);


foreach ($files as $file) {
	$output = $fileHandler->deleteFile($dir."/".$file['name']);

	if( $output !== TRUE) {
		errorHandle::newError("Error deleting file: ".$file['name'],errorHandle::HIGH);
	}
}

if (is_dir($dir)) {
	rmdir($dir) or errorHandle::newError("Failed to remove directory: ".$dir,errorHandle::HIGH);
}
?>
