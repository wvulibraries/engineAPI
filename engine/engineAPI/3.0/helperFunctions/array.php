<?php
/**
 * @see http://us2.php.net/manual/en/function.array-unique.php#97285
 * @param  $array
 * @return array
 */
function array_unique_recursive($array)
{
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));
    foreach ($result as $key => $value)
    {
        if (is_array($value)) {
            $result[$key] = array_unique_recursive($value);
        }
    }
    return $result;
}

/**
 * Recursive version of array_diff_assoc()
 * @see http://us2.php.net/manual/en/function.array-diff-assoc.php#73972
 * @param $array1
 * @param $array2
 * @return array
 */
function array_diff_assoc_recursive($array1, $array2)
{
    $difference = array();
    foreach($array1 as $key => $value){
        if(is_array($value)){
            if(!isset($array2[$key])){
                $difference[$key] = $value;
            }elseif(!is_array($array2[$key])){
                $difference[$key] = $value;
            }else{
                $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
                if($new_diff != FALSE){
                    $difference[$key] = $new_diff;
                }
            }
        }elseif(!isset($array2[$key]) || $array2[$key] != $value){
            $difference[$key] = $value;
        }
    }
    return $difference;
}

// $array is array to be searched
// $index is the known index
//
// Returns the index of the index immediately after $index. 
// If $index is the last index in $array, returns NULL
// if $index is not found in array, returns FALSE
function array_nextIndex($array,$index,$loop=FALSE) {
	
	if (!is_array($array)) {
		return(FALSE);
	}
	
	$next  = FALSE;
	$first = NULL;
	
	reset($array);
	
	foreach ($array as $I=>$V) {
		if ($loop === TRUE && is_null($first)) {
			$first = $I;
		}
		if (is_null($next)) {
			return($I);
		}
		if ($I == $index) {
			$next = NULL;
		}
	}
	
	if (is_null($next) && $loop === TRUE) {
		return($first);
	}
	
	return($next);
}

// $array is array to be searched
// $index is the known index
//
// Returns the index of the index immediately before $index. 
// If $index is the first index in $array, returns NULL
// if $index is not found in array, returns FALSE
function array_prevIndex($array,$index,$loop=FALSE) {
	
	if (!is_array($array)) {
		return(FALSE);
	}
	
	$first = FALSE;
	$last  = NULL;
	
	reset($array);
	
	foreach ($array as $I=>$V) {
		if ($I == $index && !is_null($last)) {
			return($last);
		}
		else if ($I == $index) {
			$first = TRUE;
		} 
		$last = $I;
	}
	
	if ($loop === TRUE && $first === TRUE) {
		return($last);
	}
	else if ($first === TRUE) {
		return(NULL);
	}
	
	return(FALSE);
}

// returns the first index of the array, otherwise false
function array_getFirstIndex($array) {
	if (is_array($array) && count($array) > 0) {
		reset($array);
		foreach ($array as $I=>$V) {
			return($I);
		}
	}
	return(FALSE);
}

// returns the last index of the array, otherwise false
function array_getLastIndex($array) {

	
	if (is_array($array) && count($array) > 0) {
		return(array_pop(array_keys($array)));
	}
	
	return(FALSE);
}

?>