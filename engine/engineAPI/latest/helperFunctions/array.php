<?php
/**
 * EngineAPI Helper Functions - array
 * @package Helper Functions\array
 */

/**
 * Recursive version of array_unique()
 * @see http://us2.php.net/manual/en/function.array-unique.php#97285
 * @param  $array
 * @return array
 */
function array_unique_recursive($array){
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
function array_diff_assoc_recursive($array1, $array2){
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

/**
 * Returns the index of the index immediately after $index.
 *
 * @param array $array
 *        The array to be searched
 * @param mixed $index
 *        The known, starting, index
 * @param bool $loop
 *        If TRUE, will loop when it gets to the end of the array and continue at beginning
 * @return mixed
 *         index of the next item in the array, NULL if $index is the last in the array, FALSE if $index is not found
 */
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


/**
 * Returns the index of the index immediately before $index.
 *
 * @param array $array
 *        The array to be searched
 * @param mixed $index
 *        The known, starting, index
 * @param bool $loop
 *        If TRUE, will loop when it gets to the beginning of the array and continue at end
 * @return mixed
 *         index of the next item in the array, NULL if $index is the first in the array, FALSE if $index is not found
 */
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

/**
 * Returns the first index of the array, otherwise false
 *
 * @param array $array
 *        The array to be searched
 * @return mixed
 *         Returns the index if found, otherwise FALSE
 */
function array_getFirstIndex($array) {

    if (is_array($array) && count($array) > 0) {
        return(array_shift(array_keys($array)));
    }
    
    return(FALSE); 
}

/**
 * Returns the last index of the array, otherwise false
 * @param array $array
 *        The array to be searched
 * @return mixed
 *         Returns the index if found, otherwise FALSE
 */
function array_getLastIndex($array) {

	
	if (is_array($array) && count($array) > 0) {
		return(array_pop(array_keys($array)));
	}
	
	return(FALSE);
}

/** 
 * Turns an array into a string, using a definable delimiter. 
 * if parameter is not an array, returns that parameter unmodified. 
 *
 * @deprecated This can be replaced with just using serialize_array() i think
 * @param array $array
 * @return array|mixed
 */
function buildECMSArray($array) {
	
    $enginevars = enginevars::getInstance();
	
	$output = "";
	if(is_array($array)) {
		$output = implode($enginevars->get("delim"),$array);
	}
	else {
		$output = $array;
	}
	return($output);
}

/**
 * Convert an XML document to an array
 * Converts a simpleXML element into an array. Preserves attributes.
 * You can choose to get your elements either flattened, or stored in a custom index that you define.
 *
 * @see http://us2.php.net/manual/en/book.simplexml.php#105697
 * @param SimpleXMLElement $xml
 *        The XML to convert
 * @param boolean|string $attributesKey
 *        If you pass TRUE, all values will be stored under an '@attributes' index.
 *        Note: You can also pass a string to change the default index. [defaults to null]
 * @param boolean|string $childrenKey
 *        If you pass TRUE, all values will be stored under an '@children' index.
 *        Note: You can also pass a string to change the default index. [defaults to null]
 * @param boolean|string $valueKey
 *        If you pass TRUE, all values will be stored under an '@values' index.
 *        Note: You can also pass a string to change the default index. [defaults to null]
 * @return array
 */
function simpleXMLToArray(SimpleXMLElement $xml,$attributesKey=null,$childrenKey=null,$valueKey=null){ 

    if ($childrenKey   && !is_string($childrenKey))   $childrenKey   = '@children';
    if ($attributesKey && !is_string($attributesKey)) $attributesKey = '@attributes';
    if ($valueKey      && !is_string($valueKey))      $valueKey      = '@values';
    
    $return = array(); 
    $name   = $xml->getName(); 
    $_value = trim((string)$xml); 

    if (!strlen($_value)) $_value = null; 

    if($_value !== null){ 
        if ($valueKey) {
            $return[$valueKey] = $_value;
        } 
        else {
            $return = $_value;
        } 
    } 

    $children = array(); 
    $first    = true; 
    foreach($xml->children() as $elementName => $child) { 

        $value = simpleXMLToArray($child,$attributesKey, $childrenKey,$valueKey); 
        
        if(isset($children[$elementName])) { 
            if(is_array($children[$elementName])) { 
               if($first) { 
                    $temp = $children[$elementName]; 
                    unset($children[$elementName]); 
                    $children[$elementName][] = $temp; 
                    $first=false; 
                } 
                $children[$elementName][] = $value; 
            }
            else{ 
                $children[$elementName] = array($children[$elementName],$value); 
            } 
        } 
        else{ 
            $children[$elementName] = $value; 
        } 

    } 
    if($children) { 

        if ($childrenKey) {
            if (is_array($return)) {
                $return = array($return, $childrenKey=>$children);
            }
            else {
                $return[$childrenKey] = $children;
            }
        }
        else {
            $return = array_merge((array)$return,$children);
        } 

    } 

    $attributes = array(); 
    
    foreach($xml->attributes() as $name=>$value) { 
        $attributes[$name] = trim($value); 
    } 

    if($attributes) { 
        if ($attributesKey) {
            if (!is_array($return)) {
                $return = array($return, $attributesKey=>$attributes);
            }
            else {
                $return[$attributesKey] = $attributes;
            }
        } 
        else {
            $return = array_merge((array)$return, $attributes);
        } 
    } 

    return $return; 
}

/**
 * Recursively merge a set of 2 or more arrays
 *
 * @see http://www.php.net/manual/en/function.array-merge-recursive.php#104145
 * @return array
 */
function array_merge_recursive_overwrite() {

    if (func_num_args() < 2) {
        trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
        return;
    }
    $arrays = func_get_args();
    $merged = array();
    while ($arrays) {
        $array = array_shift($arrays);
        if (!is_array($array)) {
            trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
            return;
        }
        if (!$array)
            continue;
        foreach ($array as $key => $value)
            if (is_string($key))
                if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
                    $merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
                else
                    $merged[$key] = $value;
            else
                $merged[] = $value;
    }
    return $merged;
}

/**
 * Peak into an array without modifying it (Warning: may not play well with objects)
 *
 * @todo Fix so that it is object-safe
 * @param array $arr
 *        The array to examine
 * @param string $side
 *        Which side of the array are we looking at? (left|front|top or right|end|bottom)
 *        Default: Left
 * @return mixed
 */
function array_peak($arr,$side='TOP'){
    // Make sure we're playing with an array
    if(!is_array($arr)) return NULL;

    // Make a copy of the array (makes sure we aren't working with a ref)
    $arr2 = $arr;

    switch(trim(strtoupper($side))){
        case 'START':
        case 'LEFT':
        case 'BOTTOM':
        default:
            return array_shift($arr2);
            break;

        case 'END':
        case 'RIGHT':
        case 'TOP':
            return array_pop($arr2);
    }
}
?>