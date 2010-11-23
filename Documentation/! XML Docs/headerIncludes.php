<?
function getDirectoryTree ($dir, $filters = array()) {
	
	$dirs   = array_diff(scandir($dir), array_merge(array(".",".."), $filters)); 
	$dirArr = array(); 
	
	foreach ($dirs as $d) {
		$dirArr[$d] = is_dir($dir."/".$d) ? getDirectoryTree($dir."/".$d, $filters) : $dir."/".$d;
	}
	
	return $dirArr;

}

function printDirectoryTree ($dirArr) {
	
	$output = "<ul>";
	
	foreach ($dirArr as $k => $v) {
		
		$output .= "<li>";
		if (is_array($v)) {
			$output .= $k;
			$output .= printDirectoryTree($v);
		}
		else {
			$output .= '<a href="'.$_SERVER['PHP_SELF'].'?f='.$k.'">'.$k.'</a>';
		}
		$output .= "</li>";
		
	}
	
	$output .= "</ul>";
	
	return $output;
	
}

function findDocPath ($dirArr, $name) {
	
	$return = FALSE;
	
	foreach ($dirArr as $k => $v) {
		
		if (is_array($v)) {
			$return = findDocPath($v,$name);
			if ($return !== FALSE) {
				return $return;
			}
		}
		else if ($k == $name) {
			return $v;
		}
		
	}
	
	return FALSE;
	
}

function leftIndentTrim($text) {
	
	$offset = array();
	$min    = NULL;
	$t      = NULL;
	
	foreach (preg_split("/(\r?\n)/", $text) as $i => $line) {

		if (is_empty($line)) {
			if ($i != 0) {
				$t .= $line."\n";
			}
			continue;
		}

		preg_match_all("/^\s+/",$line,$matches);
		$offset[] = $matches[0][0];

		$t .= $line."\n";
	}

	$min = min($offset);

	$t = preg_replace("/".$min."/","",$t);
	$t = rtrim($t);

	return $t;

}

?>
<link rel="stylesheet" href="documentation.css" type="text/css" media="screen" />
