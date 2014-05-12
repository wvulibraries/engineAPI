<?php
/**
 * Legacy eapi_function()
 * @deprecated
 */
class eapi_function {
	
	//Template Stuff
	private $pattern = "/\{eapi_function\s+(.+?)\}/";
	private $function = "eapi_function::templateMatches";
	
	function __construct() {
		deprecated();
		EngineAPI::defTempPatterns($this->pattern,$this->function,$this);
		EngineAPI::defTempPatterns("/\{engine name=\"function\"\s+(.+?)\}/",$this->function,$this);
	}

	/**
	 * Template handler
	 * @deprecated
	 * @param $matches
	 * @return bool
	 */
	public static function templateMatches($matches) {
		deprecated();
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);
		
		if (!isset($attPairs['function']) && isempty($attPairs['function'])) {
			return(FALSE);
		}
		
		$output = $attPairs['function']($attPairs);
		
		return($output);
	}
}

?>