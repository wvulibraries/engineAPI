<?php

$modulesDirectory = "/Users/mrbond/Documents/Dropbox/GIT/engineAPI/engine/engineAPI/latest/modules";
$modulesDirectory = "engine/engineAPI/latest/modules";

$availableModules = array();

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