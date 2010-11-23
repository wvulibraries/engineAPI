<?
/* $array         = Array to be sorted
 * $col           = The column in a multi dimensional array.
 * $ignoreSymbols = BOOL, default to TRUE. If True, ignores all symbols in the string while sorting
 * $ignoreCase    = BOOL, default to TRUE. Sorts case insensitively.
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