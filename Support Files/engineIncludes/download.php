<?
$engineDir = "/Path/To/phpincludes/engineAPI/engine";
include($engineDir ."/engine.php");
$engine = new EngineCMS();

$fileName = sessionGet("FMfileName");
$fileType = sessionGet("FMfileType");
$fileData = sessionGet("FMfileData");
$display = sessionGet("FMdisplay");

unset($_SESSION['FMfileName']);
unset($_SESSION['FMfileType']);
unset($_SESSION['FMfileData']);
unset($_SESSION['FMdisplay']);

ob_end_clean();

if (empty($fileName) || empty($fileType) || empty($fileData)) {
	exit;
}

if ($display == "window") {
	header("Expires: 0");
	header("Cache-Control: private");
	header("Pragma: cache");
	header("Content-Length: ".strlen($fileData));
	header("Content-Type: ".$fileType);
	header("Content-Transfer-Encoding: binary");
	header('Content-Disposition: filename="'.$fileName.'"');
}
else if ($display = "download") {
	header("Expires: 0");
	header("Cache-Control: private");
	header("Pragma: cache");
	header("Content-Length: ".strlen($fileData));
	header("Content-type: application/force-download");
	header("Content-Transfer-Encoding: binary");
	header('Content-Disposition: attachment; filename="'.$fileName.'"');
}

print $fileData;
?>