<?php


/**
 * @see http://us2.php.net/manual/en/function.array-unique.php#97285
 * @param  $array
 * @return array
 */
function array_unique_recursive($array)
{
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));
    foreach($result as $key => $value)
    {
        if(is_array($value)){
            $result[$key] = array_unique_recursive($value);
        }
    }
    return $result;
}