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
		
		$attPairs      = attPairs($matches[1]);

		if (isset($_GET['HTML'][$attPairs['var']]) && !is_empty($_GET['HTML'][$attPairs['var']])) {
			if (isset($attPairs['decode']) && $attPairs['decode'] == "true") {
				$_GET['HTML'][$attPairs['var']] = urldecode($_GET['HTML'][$attPairs['var']]);
			}
			return($_GET['HTML'][$attPairs['var']]);
		}

		return("");
		
	}

}

?>