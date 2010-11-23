<?
// Disable php timeout settings
ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);

// Launch engine
$engineDir = "/Path/To/phpincludes/engineAPI/engine";
include($engineDir ."/engine.php");
$engine = new EngineCMS();


// Send emails, pausing 15 seconds between each one
// Uses command line arguments:
// -id=foo = "sendID" in the database
// -d=foo  = database name
// -t=foo  = table name
// -u=foo  = database username
// -p=foo  = database password
// -s=foo  = database server
// -P=foo  = database port
// -f=foo  = files table name


array_shift($argv);
foreach ($argv as $arg) {
	if (substr($arg,0,1) == '-') {
		
		$eqPos = strpos($arg,'=');
		$key   = substr($arg,1,$eqPos-1);
		$value = substr($arg,$eqPos+1);
		
		switch ($key) {
			case "id": $sendID    = $value; break;
			case "d":  $database  = $value; break;
			case "t":  $table     = $value; break;
			case "u":  $username  = $value; break;
			case "p":  $password  = $value; break;
			case "s":  $server    = $value; break;
			case "P":  $port      = $value; break;
			case "f":  $fileTable = $value; break;
		}
	}
}

$engine->dbConnect("username",$username,TRUE);
$engine->dbConnect("password",$password,TRUE);
$engine->dbConnect("server",$server,TRUE);
$engine->dbConnect("port",$port,TRUE);
$engine->dbConnect("database",$database,TRUE);  // needs to be last


$fileHandler           = new fileHandler($engine);
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


$sql = sprintf("SELECT * FROM %s WHERE sendID='%s'",
	$engine->openDB->escape($table),
	$engine->openDB->escape($sendID)
	);
$engine->openDB->sanitize = FALSE;
$sqlResult                = $engine->openDB->query($sql);

while ($row = @mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
	
	$mail = new mailSender($engine);
	
	$mail->addRecipient($row['recipient']);
	$mail->addSender($row['sender']);
	$mail->addSubject($row['subject']);
	$mail->addBody($row['body']);
	
	foreach ($files as $file) {
		$mail->addAttachment($dir."/".$file['name'], $file['name'], "base64", $file['type']);
	}
	
	$mail->sendEmail();
	sleep(15);
	
}


$sql = sprintf("DELETE FROM %s WHERE sendID='%s'",
	$engine->openDB->escape($table),
	$engine->openDB->escape($sendID)
	);
$engine->openDB->sanitize = FALSE;
$sqlResult                = $engine->openDB->query($sql);


foreach ($files as $file) {
	$output = $fileHandler->deleteFile($dir."/".$file['name']);

	if( $output !== TRUE) {
		fprintf(STDERR, "Error deleting file: ".$file['name']."\n");
	}
}
rmdir($dir) or fprintf(STDERR, "Failed to remove directory: ".$dir."\n");

?>