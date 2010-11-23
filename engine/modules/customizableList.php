<?php
/*
$file = "templates/listTemplate.html";
$dataArray = array();
$iVal = 0;
for ($i = 0; $i < 9; $i++) {
	$dataArray[0][$i] = $iVal;
	
	if ($i % 3 == 0) {
		$i++;
		$dataArray[0][$i] = array();
		$jVal = 0;
		for ($j = 0; $j < 4; $j++) {
			$dataArray[0][$i][$j] = $iVal.".".$jVal;
			if ($j == 1) {
				$j++;
				$dataArray[0][$i][$j] = array();
				for ($k = 0; $k < 2; $k++) {
					$dataArray[0][$i][$j][$k] = $iVal.".".$jVal.".".$k;
				}
			}
			$jVal++;
		}
	}
	$iVal++;
}

echo webHelper_createList($file, $dataArray);
*/


function webHelper_createList($filename, $data) {
	$template = file($filename);
	
	$array = webHelper_buildArray($template);
	$dataArray = webHelper_formatData($data);
	$output = webHelper_insertData($array, $dataArray);
	
	return $output;
}


function webHelper_formatData ($data) {
	
	$out = array();
	$count = 0;
	
	for ($i = 0; $i < count($data); $i++) {
		if (is_array($data[$i])) {
			$out[$count]['sub'] = webHelper_formatData($data[$i]);
		}
		else {
			$count++;
			$out[$count]['display'] = $data[$i];
			$out[$count]['sub'] = NULL;
		}
	}
	
	return $out;
	
}


function webHelper_buildArray($template) {
	
	$string = "";
	for($I=0;$I<count($template);$I++) {
		$string .= $template[$I] . "\n";
	}
	
	$string = preg_replace('/{list\s*repeat="start"\s*level="(\d+?)"}/',"\n$0\n",$string);
	$string = preg_replace('/{list\s*repeat="end"\s*level="(\d+?)"}/',"\n$0\n",$string);
	
	$template = explode("\n", $string);
		
	for($I=0;$I<count($template);$I++) {
		if (!empty($template[$I])) {
			$template[$I] .= "\n";
		}
		
		if(preg_match('/{list\s*repeat="start"\s*level="(\d+?)"}/',$template[$I],$matches)) {
			
			$rt = array();
			
			for($K=$I++;$K<count($template);$K++) { 
			
				if(preg_match('/{list\s*repeat="end"\s*level="'.$matches[1].'"}/',$template[$K])) {
					
					$I = $K;
					$template[$I] = webHelper_buildArray($rt);
					
					break 1;
					
				}
				$rt[] = $template[$K];
			}
		}
		
		$returnArray[] = $template[$I];
		
	}

	return($returnArray);
	
}


function webHelper_insertData($template, $vars) {
	$output = "";
	
	foreach ($vars as $item) {
		foreach ($template as $line) {
			if (is_array($line)) {
				if (is_array($item['sub'])) {
					$ln = webHelper_insertData($line,$item['sub']);
					$output .= $ln;
				}
			}
			elseif (preg_match('/{list\s*var="listItem"}/',$line)) {
				$ln = preg_replace('/{list\s*var="listItem"}/',$item['display'],$line);
				$output .= $ln;
			}
			else {
				$output .= $line;
			}
		}
	}
	
	return $output;
}

?>
