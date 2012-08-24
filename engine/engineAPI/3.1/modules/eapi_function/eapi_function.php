<?php

class eapi_function {
	
	//Template Stuff
	private $pattern = "/\{eapi_function\s+(.+?)\}/";
	private $function = "eapi_function::templateMatches";
	
	function __construct() {
		// $engine = EngineAPI::singleton();
		
		// $engine->defTempPattern($this->pattern,$this->function,$this);
		// $engine->defTempPattern("/\{engine name=\"function\"\s+(.+?)\}/",$this->function,$this);
		
		EngineAPI::defTempPatterns($this->pattern,$this->function,$this);
		EngineAPI::defTempPatterns("/\{engine name=\"function\"\s+(.+?)\}/",$this->function,$this);
	}
	
	public static function templateMatches($matches) {
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