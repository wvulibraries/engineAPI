<?php

class queryString {

	function __construct() {
		EngineAPI::defTempPatterns("/\{queryString\s+(.+?)\}/","queryString::templateMatches",$this);
	}

	/**
	 * Engine tag handler
	 * @param $matches
	 *        Matches passed by template handler
	 * @return string
	 */
	public static function templateMatches($matches) {
		$engine        = EngineAPI::singleton();
		$attPairs      = attPairs($matches[1]);

		if (isset($engine->cleanGet['HTML'][$attPairs['var']]) && !is_empty($engine->cleanGet['HTML'][$attPairs['var']])) {
			return($engine->cleanGet['HTML'][$attPairs['var']]);
		}

		return("");
		
	}

}

?>