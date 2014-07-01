<?php
/**
 * Legacy EngineAPI session stuff
 * @deprecated
 */
class eapi_session {
	function __construct() {
		EngineAPI::defTempPatterns("/\{csrf\s+(.+?)\}/","eapi_session::csrf",$this);
		EngineAPI::defTempPatterns("/\{engine name=\"csrf\"\s*(.*?)\}/","eapi_session::csrf",$this);
		EngineAPI::defTempPatterns("/\{engine name=\"insertCSRF\"\s*(.*?)\}/","eapi_session::csrf",$this);
		EngineAPI::defTempPatterns("/\{engine name=\"csrfGet\"\s*(.*?)\}/","eapi_session::csrfGet",$this);
		EngineAPI::defTempPatterns("/\{engine name=\"session\"\s+(.+?)\}/","eapi_session::sessionGet",$this);
	}

	/**
	 * Get something from the session
	 *
	 * @deprecated
	 * @param $matches
	 * @return mixed|null
	 */
	public static function sessionGet($matches) {
		deprecated();
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);

		$output = sessionGet($attPairs['var']);
		return($output);
	}

	/**
	 * Insert the csrf
	 *
	 * @deprecated
	 * @param $matches
	 * @return mixed|null|string
	 */
	public static function csrf($matches) {
		deprecated();
		$engine        = EngineAPI::singleton();
		$eapi_session  = $engine->retTempObj("eapi_session");
		$attPairs      = attPairs($matches[1]);

		if(isset($attPairs['insert']) && strtolower($attPairs['insert']) != "post"){
			$output = sessionInsertCSRF(FALSE);
		}else{
			$output = sessionInsertCSRF();
		}

		return($output);
	}

	/**
	 * Get the csrf
	 *
	 * @deprecated
	 * @param $matches
	 * @return mixed|null|string
	 */
	public static function csrfGet($matches) {
		deprecated();
		$output = sessionInsertCSRF(FALSE);
		return($output);
	}

	/**
	 * Template handler
	 *
	 * @deprecated
	 * @param $matches
	 * @return bool|string
	 */
	public static function templateMatches($matches) {
		deprecated();
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);

		if (!isset($attPairs['files']) && isempty($attPairs['type'])) {
			return(FALSE);
		}

		$output = recurseInsert($attPairs['file'],$attPairs['type']);

		return($output);
	}
}

?>
