<?

/* Log database structure

ID
date
ip
referrer
resource
useragent
function
type
message

*/

function engineLog($type="access",$function=NULL,$message=NULL) {
	
	global $engineVars;
	
	if (!$engineVars['log']) {
		return(FALSE);
	}
	
	global $engineDB;
	
	// setup the variables
	$date      = time();
	$ip        = $_SERVER['REMOTE_ADDR'];
	$referrer  = (isset($_SERVER['HTTP_REFERER']))?$_SERVER['HTTP_REFERER']:NULL;
	$resource  = $_SERVER['REQUEST_URI'];
	$queryStr  = $_SERVER['QUERY_STRING'];
	$useragent = $_SERVER['HTTP_USER_AGENT'];
	
	$query = sprintf(
		"INSERT INTO log (date,ip,referrer,resource,useragent,function,type,message,querystring) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s')",
		$engineDB->escape($date),
		$engineDB->escape($ip),
		$engineDB->escape($referrer),
		$engineDB->escape($resource),
		$engineDB->escape($useragent),
		$engineDB->escape($function),
		$engineDB->escape($type),
		$engineDB->escape($message),
		$engineDB->escape($queryStr)
		);

	$engineDB->sanitize = FALSE;			
	$results = $engineDB->query($query);
	
	if (debugNeeded("log")) {
		debugDisplay("log","\$results",1,"Contents of the \$results array.",$results);
	}
	
	return(TRUE);
}

?>