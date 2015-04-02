<?php

class queryString {

	function __construct() {
		templates::defTempPatterns("/\{queryString\s+(.+?)\}/","queryString::templateMatches",$this);
	}

	public static function getInstance() {
		return new self;
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

	/**
	 * Remove a variable from the query string
	 *
	 * @param $var
	 * @param $qs optional. if not provided will get query string from $_SERVER;
	 * @return string
	 */
	public function remove($var, $qs = NULL) {
		if (isnull($qs)) $qs = $_SERVER['QUERY_STRING'];

		if ($qs[0] == "?") {
			$qs     = substr($qs, 1);
			$qmTest = TRUE;
		}
		else {
			$qmTest = FALSE;
		}


		if ($qs[strlen($qs)-1] == "&") {
			$qs     = substr($qs, 0, -1);
			$ampTest = TRUE;
		}
		else {
			$ampTest = FALSE;
		}

		parse_str($qs, $output);
		if (array_key_exists($var, $output)) unset($output[$var]);

		return urldecode((($qmTest)?"?":"").http_build_query($output).(($ampTest)?"&":""));

	}

}

?>
