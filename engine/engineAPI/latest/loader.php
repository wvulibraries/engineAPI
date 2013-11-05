<?php

function loader($directory) {

	$dirHandle = @opendir($directory) or die("Unable to open ".$directory);

	$varsBefore = array_keys(get_defined_vars());

	while (false !== ($file = readdir($dirHandle))) {
		// Check to make sure that it isn't a hidden file and that it is a PHP file
		if ($file != "." && $file != ".." && $file) {
			$fileChunks = array_reverse(explode(".", $file));
			
			if ($fileChunks[0] == "php") {
				require_once($directory."/".$file);
			}

		}
	}

	$varsAfter  = array_keys(get_defined_vars());

	return compact(array_diff($varsAfter, $varsBefore));;

}

?>