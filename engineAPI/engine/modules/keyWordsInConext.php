<?php
//Key Words in Context
// Highlights $str1 in $str2
//
// $str1 = search term
// $str2 = compair with string
function kwic($str1,$str2) {
	
	$kwicLen = strlen($str1);

	$kwicArray = array();
	$pos       = 0;
	$count     = 0;

	while($pos !== FALSE) {
		$pos = stripos($str2,$str1,$pos);
		if($pos !== FALSE) {
			$kwicArray[$count]['kwic'] = substr($str2,$pos,$kwicLen);
			$kwicArray[$count++]['pos']  = $pos;
			$pos++;
		}
	}

	for($I=count($kwicArray)-1;$I>=0;$I--) {
		$kwic = '<span class="kwic">'.$kwicArray[$I]['kwic'].'</span>';
		$str2 = substr_replace($str2,$kwic,$kwicArray[$I]['pos'],$kwicLen);
	}
		
	return($str2);
}
?>