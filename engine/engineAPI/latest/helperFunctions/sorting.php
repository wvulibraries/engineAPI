<?php
/**
 * EngineAPI Helper Functions - sorting
 * @package Helper Functions\sorting
 */
/**
 * Perform a natural sort on a given array
 *
 * @todo Lookinto rewritting this. Seems very clunky and hacky
 * @uses  naturalSortCmp()
 * @param array $array
 *        Array to be sorted
 * @param int $col
 *        The column in a multi dimensional array.
 * @param bool $ignoreSymbols
 *        If True, ignores all symbols in the string while sorting [Default: True]
 * @param bool $ignoreCase
 *        Sorts case insensitively. [Default: True]
 * @return array
 */
function naturalSort($array,$col=NULL,$ignoreSymbols=TRUE,$ignoreCase=TRUE) {

	$enginevars = enginevars::getInstance();
	
	$enginevars->set("sortCol", $col);
	$enginevars->set("sortIgnoreSymbols", $ignoreSymbols);
	$enginevars->set("sortIgnoreCase", $ignoreCase);
	
	usort($array,"naturalSortCmp");

	$enginevars->set("sortCol", NULL);
	$enginevars->set("sortIgnoreSymbols", NULL);
	$enginevars->set("sortIgnoreCase", NULL);
	
	return($array);
}

/* Sort function called by natrualSort() */
/**
 * Sort function called by natrualSort()
 *
 * @todo Remove depreciated use of ereg_replace()
 * @internal
 * @used-by naturalSort()
 * @param string $a
 * @param string $b
 * @return int
 */
function naturalSortCmp($a, $b) {
	
	$enginevars = enginevars::getInstance();
	
	if (!isnull($enginevars->get("sortCol"))) {
		$aString = $a[$enginevars->get("sortCol")];
		$bString = $b[$enginevars->get("sortCol")];
	}
	else {
		$aString = $a;
		$bString = $b;
	}
	
	if($enginevars->get("sortIgnoreSymbols") === TRUE) {
		$aString = ereg_replace("[^A-Za-z0-9]", "", $aString);
		$bString = ereg_replace("[^A-Za-z0-9]", "", $bString);
	}
	
	if($enginevars->get("sortIgnoreCase") === TRUE) {
		return strnatcasecmp($aString,$bString);
	}
	
	return strnatcmp($aString, $bString);
}

?>