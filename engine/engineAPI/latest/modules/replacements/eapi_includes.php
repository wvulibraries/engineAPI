<?php
/**
 * Legacy EngineAPI stuff
 * @deprecated
 * @package EngineAPI\modules\eapi_includes
 */
class eapi_includes {
	/**
	 * Template tag pattern
	 * @var string
	 */
	private $pattern = "/\{eapi_include\s+(.+?)\}/";
	/**
	 * Template tag callback
	 * @var string
	 */
	private $function = "eapi_includes::templateMatches";
	
	function __construct() {
		deprecated();
		templates::defTempPatterns($this->pattern,$this->function,$this);
		templates::defTempPatterns("/\{engine name=\"include\"\s+(.+?)\}/",$this->function,$this);
	}

	/**
	 * Template handler
	 * @deprecated
	 * @param $matches
	 * @return bool
	 */
	public static function templateMatches($matches) {
		deprecated();

		$attPairs      = attPairs($matches[1]);
		
		if(!isset($attPairs['file']) && is_empty($attPairs['type'])) return(FALSE);

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