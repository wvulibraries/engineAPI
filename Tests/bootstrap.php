<?php

// Start output-buffering for any header for cookie functions ('headers already sent' error)
ob_start();

$modulesDirectory  = "engine/engineAPI/latest/modules";
$helperFunctions = "engine/engineAPI/latest/helperFunctions";

$availableModules  = array();




//Load helper function Modules
$hfDirHandle = @opendir($helperFunctions) or die("Unable to open: ".$helperFunctions);
while (false !== ($file = readdir($hfDirHandle))) {
	// Check to make sure that it isn't a hidden file and that it is a PHP file
	if ($file != "." && $file != ".." && $file) {
		$fileChunks = array_reverse(explode(".", $file));
		$ext= $fileChunks[0];
		if ($ext == "php") {
			require_once $helperFunctions."/".$file;
		}
	}
}

$modules_dirHandle = @opendir($modulesDirectory) or die("Unable to open (Modules): ".$modulesDirectory);
while (false !== ($dir = readdir($modules_dirHandle))) {

	$moduleDirectory = $modulesDirectory."/".$dir;

	// Check to make sure that it isn't a hidden file and that the file is a directory
	if ($dir != "." && $dir != ".." && is_dir($moduleDirectory) === TRUE) {

		$singleMod_dirHandle = @opendir($moduleDirectory) or die("Unable to open (Single Module): ".$moduleDirectory);
		
		while (false !== ($file = readdir($singleMod_dirHandle))) {
		
			if ($file != "." && $file != ".." && $file) {

				// if ($file == "onLoad.php") {
				// 	include_once($moduleDirectory."/".$file);
				// }
				// else {
					$fileChunks = array_reverse(explode(".", $file));
					$ext= $fileChunks[0];
					if ($ext == "php") {
						$availableModules[$fileChunks[1]] = $moduleDirectory."/".$file;
					}
				// }

			}
		}
	}
}

function autoloader($className) {

	if (!class_exists($className, FALSE)) {

		global $availableModules;

		if (isset($availableModules[$className]) && file_exists($availableModules[$className])) {
			require_once $availableModules[$className];
			return TRUE;
		}
	}

	return;
}

spl_autoload_register("autoloader",TRUE);

?>