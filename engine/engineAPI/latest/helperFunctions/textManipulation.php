<?php
/**
 * EngineAPI Helper Functions - textManipulation
 * @package Helper Functions\textManipulation
 */

/**
 * Returns a string with, space delimited, with $wordCount number of words.
 * Can be used from {engine } function call, with attributes 'str' & 'wordCount'
 *
 * @param string|array $str
 *        If String; The String to evaluate
 *        If Array; keys 'str' and 'wordCount'
 * @param int $wordCount
 *        Pass-through for array_slice()'s $length
 * @return string
 */
function wordSubStr($str,$wordCount=NULL) {

    if (is_array($str)) {
        $wordCount = $str['wordCount'];
        $str = $str['str'];
    }

    $str = explode(" ",$str);
    $str = array_slice($str,0,$wordCount);
    $str = join(' ',$str);

    return($str);
}

/**
 * Key Words in Context (Highlights $str1 in $str2)
 * @param string $str1
 * @param string $str2
 * @return mixed
 */
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

/**
 * Attempts to properly capitalize post titles.
 *
 * @see http://nanovivid.com/stuff/wordpress/title-case/
 * @see http://daringfireball.net/2008/05/title_case/
 * @param $str 
 * @param  $preserve If TRUE, will preserve any capitalization already in the string (such as words in all caps or camel case)
 * @return mixed|string
 */

function str2TitleCase($str,$preserve=FALSE) {

    // Edit this list to change what words should be lowercase
    $small_words = "a an and as at but by en for if in of on or the to v[.]? via vs[.]?";
    $small_re = str_replace(" ", "|", $small_words);

    // Replace HTML entities for spaces and record their old positions
    $htmlspaces = "/&nbsp;|&#160;|&#32;/";
    $oldspaces = array();
    preg_match_all($htmlspaces, $str, $oldspaces, PREG_OFFSET_CAPTURE);

    // Remove HTML space entities
    $words = preg_replace($htmlspaces, " ", $str);

    // Split around sentance divider-ish stuff
    $words = preg_split('/( [:.;?!][ ] | (?:[ ]|^)["“])/x', $words, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 0; $i < count($words); $i++) {

        // Skip words with dots in them like del.icio.us
        $preserveRegex = '/\b([[:alpha:]][[:lower:].\'’(&\#8217;)]*)\b/x';
        $normalRegex   = '/\b([[:alpha:]][[:lower:][:upper:].\'’(&\#8217;)]*)\b/x';

        if ($preserve) {
            $regex = $preserveRegex;
        }
        else {
            $regex = $normalRegex;
        }

        $words[$i] = preg_replace_callback($regex, 'nv_title_skip_dotted', $words[$i]);

        // Lowercase our list of small words
        if (!$preserve) $words[$i] = preg_replace("/\b($small_re)\b/ei", "strtolower(\"$1\")", $words[$i]);

        // If the first word in the title is a small word, capitalize it
        $words[$i] = preg_replace("/\A([[:punct:]]*)($small_re)\b/ie", "\"$1\" . ucfirst(\"$2\")", $words[$i]);

        // If the last word in the title is a small word, capitalize it
        $words[$i] = preg_replace("/\b($small_re)([[:punct:]]*)\Z/e", "ucfirst(\"$1\") . \"$2\"", $words[$i]);
    }

    $words = join($words);

    // Oddities
    $words = preg_replace("/ V(s?)\. /i", " v$1. ", $words);                    // v, vs, v., and vs.
    $words = preg_replace("/(['’]|&#8217;|&#039;)S\b/i", "$1s", $words);               // 's
    $words = preg_replace("/\b(AT&T|Q&A)\b/ie", "strtoupper(\"$1\")", $words);  // AT&T and Q&A
    $words = preg_replace("/-ing\b/i", "-ing", $words);                         // -ing
    $words = preg_replace("/(&[[:alpha:]]+;)/Ue", "strtolower(\"$1\")", $words);          // html entities

    // Put HTML space entities back
    $offset = 0;
    for ($i = 0; $i < count($oldspaces[0]); $i++) {
        $offset = $oldspaces[0][$i][1];
        $words = substr($words, 0, $offset) . $oldspaces[0][$i][0] . substr($words, $offset + 1);
        $offset += strlen($oldspaces[0][$i][0]);
    }

    return $words;
}

/**
 * This is a callback function for str2title. This is the function that actually does the case changing
 * @param $matches
 * @return string
 */
function nv_title_skip_dotted($matches) {
    return preg_match('/[[:alpha:]] [.] [[:alpha:]]/x', $matches[0]) ? $matches[0] : ucfirst(strtolower($matches[0]));
}

/**
 * Obfuscate an email address
 *
 * @param string $input
 * @return string
 */
function obfuscateEmail($input){
	// Accept both array and srt input
	$email = is_array($input)
		? array_shift($input)
		: $input;

    $output = "";
    for ($i=0; $i<strlen($email); $i++){
        $output .= "&#" . ord($email[$i]) . ";";
    }

    return($output);
}

/**
 * Obfuscate full filepaths
 * This function will attempt to obfuscate sensitive parts of a file path.
 * Note: If we are in CLI mode, no obfuscation will be done
 *
 * @param string $filepath
 * @return string
 */
function secureFilepath($filepath){
    // If this is a cli session, there's no need to secure the filepath
    if(!class_exists('PHPUnit_Framework_TestCase', FALSE) and isCLI()) return $filepath;

    $filepath = preg_replace('|^'.$_SERVER["DOCUMENT_ROOT"].'|i', '[DOCUMENT_ROOT]', $filepath);
    $filepath = preg_replace('|^/home|i', '[HOME]', $filepath);

	return $filepath;
}

/**
 * Formats a given string into a US Phone format
 * Note: This function can take an already formatted number to then re-format it
 *
 * @param string $phoneIn
 * @param int $format - Format of the phone number to use
 *        [0] - 1234567890
 *        [1] - (123) 456-7890 (Default)
 *        [2] - 123.456.7890
 *        [3] - 123-456-7890
 * @param int $extFormat - Format of a phone extension to use (if an extension was given)
 *        [0] - Ignore extension
 *        [1] - x123 (Default)
 *        [2] - ext123
 * @return string
 */
function formatPhone($phoneIn,$format=1,$extFormat=1){
    if(strlen($phoneIn) < 10) return 'None Provided';
    $phoneIn = preg_replace('|\D|', '', $phoneIn);
    switch( (int)$format ){
        case 0:
            $phoneOut = substr($phoneIn,0,10);
            break;
        case 1:
            $phoneOut = sprintf('(%s) %s-%s', substr($phoneIn,0,3), substr($phoneIn,3,3), substr($phoneIn,6,4));
            break;
        case 2:
            $phoneOut = sprintf('%s.%s.%s', substr($phoneIn,0,3), substr($phoneIn,3,3), substr($phoneIn,6,4));
            break;
        case 3:
            $phoneOut = sprintf('%s-%s-%s', substr($phoneIn,0,3), substr($phoneIn,3,3), substr($phoneIn,6,4));
            break;
        default:
            return formatPhone($phoneIn, NULL, $extFormat);
            break;
    }

    if(strlen($phoneIn) > 10){
        switch( (int)$extFormat ){
            case 0:
                // Ignore extension
                break;
            case 1:
                $phoneOut .= ' x'.substr($phoneIn,10);
                break;
            case 2:
                $phoneOut .= ' ext'.substr($phoneIn,10);
                break;
            default:
                return formatPhone($phoneIn, $format);
                break;
        }
    }

    return $phoneOut;
}

/**
 * Convert a logical boolean into a string 'boolean'
 *
 * @param mixed $input
 * @param bool $returnBit
 *        If TRUE; return 1 or 0, otherwise return 'true' or 'false'
 * @return string
 */
function bool2str($input,$returnBit=FALSE){
    if($returnBit){
        return str2bool($input) ? '1' : '0';
    }else{
        return str2bool($input) ? 'true' : 'false';
    }
}

/**
 * Convert a string 'boolean' into a actual boolean
 *
 * @param Mixed $input
 * @return bool|null
 */
function str2bool($input){
    switch(TRUE){
        case is_bool($input):
            return $input ? TRUE : FALSE;
        case is_numeric($input):
            return $input > 0 ? TRUE : FALSE;
        case is_string($input):
            switch(strtolower(trim($input))){
                case 'true':
                case 'yes':
                    return TRUE;
                case 'false':
                case 'no':
                    return FALSE;
                default:
                    return NULL;
            }
        default:
            return NULL;
    }
}

/**
 * This function will take a mix of inputs, and return a normalized array
 * This is useful to take a CSV or JSON string and get an array back
 *
 * @param mixed $input
 * @param string $delimiter
 * @return array|mixed
 */
function normalizeArray($input,$delimiter=','){
    if(is_array($input)){
        return $input;
    }elseif(NULL !== ($json = json_decode($input,TRUE))){
        return $json;
    }else{
        return array_map('trim', explode($delimiter,$input));
    }
}

/**
 * This function will parse a date time string and convert it into a unix timestamp
 *
 * @deprecated
 * @param string $str
 * @return string
 **/
function dateToUnix($str) {
	deprecated();

    $dateDelims = "[- \/.]";
    $date       = "(0[1-9]|1[012])".$dateDelims."(0[1-9]|[12][0-9]|3[01])".$dateDelims."((?:19|20)\d\d)"; // mm/dd/yyyy
    $time12     = "(?:((?:[0]\d|[1][0-2])):([0-5]?[0-9])(?::([0-5]?[0-9]))?\s?([AaPp][Mm]{0,2}))"; // 08:12:34 pm
    $time24     = "(?:((?:[0-1]?[0-9]|[2][0-3])):([0-5]?[0-9])(?::([0-5]?[0-9]))?)"; // 20:12:34

    $return = preg_match("/^".$date."(?:\s(?:".$time12."|".$time24."))?$/",$str,$matches);

    if ($return === FALSE) {
        errorHandle::newError("Error in Regular Expression",errorHandle::DEBUG);
        return FALSE;
    }

    $month  = isset($matches[1]) ? (int)$matches[1] : 0;
    $day    = isset($matches[2]) ? (int)$matches[2] : 0;
    $year   = isset($matches[3]) ? (int)$matches[3] : 0;
    $hour   = isset($matches[4]) ? (int)$matches[4] : (isset($matches[8])  ? (int)$matches[8]  : 0);
    $minute = isset($matches[5]) ? (int)$matches[5] : (isset($matches[9])  ? (int)$matches[9]  : 0);
    $second = isset($matches[6]) ? (int)$matches[6] : (isset($matches[10]) ? (int)$matches[10] : 0);
    $ampm   = isset($matches[7]) ? strtolower($matches[7]) : NULL;

    if (!isnull($ampm)) {
        if ($ampm == "pm") {
            $hour += 12; // add 12 to pm hours to convert to 24hour clock
        }
        else if ($hour == 12) {
            $hour = 0; // if 12am, change to 0 to make range 0-23
        }
    }

    return mktime($hour,$minute,$second,$month,$day,$year);
}

/**
* This function will parse a unix timestamp and convert it into a readable date string
 *
 * @param string $time
 * @param string $format
 * @return void
 **/
function unixToDate($time,$format=NULL) {
    // If a format is set, use it strictly
    if (!isnull($format)) {
        return date($format,$time);
    }

    $date = getdate($time);

    $format = "m/d/Y"; // date
    if ($date['hours'] != 0 || $date['minutes'] != 0 || $date['seconds'] != 0) {
        $format .= ' h:i'; // hours and minutes
        if ($date['seconds'] != 0) {
            $format .= ':s'; // only display seconds if needed
        }
        $format .= ' a'; // am/pm
    }

    return date($format,$time);
}

/**
 * Alias for strtolower()
 *
 * @deprecated
 * @see strtolower()
 * @param string $string
 * @return string
 **/
function lc($string) {
	deprecated();
	return (strtolower($string));
}

/**
 * Alias for strtoupper()
 *
 * @deprecated
 * @see strtoupper()
 * @param string $string
 * @return string
 **/
function uc($string) {
	deprecated();
    return (strtoupper($string));
}

?>