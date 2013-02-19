<?php
/**
 * Legacy EngineAPI stuff
 * @deprecated
 */
class eapi_includes {

	//Template Stuff
	private $pattern = "/\{eapi_include\s+(.+?)\}/";
	private $function = "eapi_includes::templateMatches";
	
	function __construct() {		
		EngineAPI::defTempPatterns($this->pattern,$this->function,$this);
		EngineAPI::defTempPatterns("/\{engine name=\"include\"\s+(.+?)\}/",$this->function,$this);
	}

	/**
	 * Template handler
	 * @deprecated
	 * @param $matches
	 * @return bool
	 */
	public static function templateMatches($matches) {
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);
		
		if(!isset($attPairs['file']) && isempty($attPairs['type'])) return(FALSE);

		$regex           = NULL;
		$condition       = "REQUEST_URI";
		$caseInsensitive = TRUE;

		if(isset($attPairs['regex']))           $regex = $attPairs['regex'];
		if(isset($attPairs['condition']))       $regex = $attPairs['condition'];
		if(isset($attPairs['caseInsensitive'])) $regex = $attPairs['caseInsensitive'];

		$output = recurseInsert($attPairs['file'],$attPairs['type'],$regex,$condition,$caseInsensitive);
		
		return($output);
	}
}

?>