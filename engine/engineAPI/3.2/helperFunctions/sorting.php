<?php
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

	global $engineVars;

	$engineVars['sortCol']           = $col;
	$engineVars['sortIgnoreSymbols'] = $ignoreSymbols;
	$engineVars['sortIgnoreCase']    = $ignoreCase;
	
	usort($array,"naturalSortCmp");

	$engineVars['sortCol']           = NULL;
	$engineVars['sortIgnoreSymbols'] = NULL;
	$engineVars['sortIgnoreCase']    = NULL;
	
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
	
	global $engineVars;
	
	if (!isnull($engineVars['sortCol'])) {
		$aString = $a[$engineVars['sortCol']];
		$bString = $b[$engineVars['sortCol']];
	}
	else {
		$aString = $a;
		$bString = $b;
	}
	
	if($engineVars['sortIgnoreSymbols'] === TRUE) {
		$aString = ereg_replace("[^A-Za-z0-9]", "", $aString);
		$bString = ereg_replace("[^A-Za-z0-9]", "", $bString);
	}
	
	if($engineVars['sortIgnoreCase'] === TRUE) {
		return strnatcasecmp($aString,$bString);
	}
	
	return strnatcmp($aString, $bString);
}

?>