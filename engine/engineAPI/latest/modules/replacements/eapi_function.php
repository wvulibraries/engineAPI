<?php
/**
 * Legacy eapi_function()
 * @deprecated
 * @package EngineAPI\modules\eapi_function
 */
class eapi_function {
	/**
	 * Template tag pattern
	 * @var string
	 */
	private $pattern = "/\{eapi_function\s+(.+?)\}/";
	/**
	 * Template tag callback
	 * @var string
	 */
	private $function = "eapi_function::templateMatches";

	function __construct() {
		deprecated();
		templates::defTempPatterns($this->pattern,$this->function,$this);
		templates::defTempPatterns("/\{engine name=\"function\"\s+(.+?)\}/",$this->function,$this);
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
		
		if (!isset($attPairs['function']) && is_empty($attPairs['function'])) {
			return(FALSE);
		}
		
		$output = $attPairs['function']($attPairs);
		
		return($output);
	}
}

?>