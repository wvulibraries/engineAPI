<?php
/**
 * EngineAPI server module
 * Engine template tag handler for {server var=""} tags
 *
 * @package EngineAPI\modules\server
 */
class server {
	/**
	 * Template tag pattern
	 * @var string
	 */
	public $pattern  = "/\{server\s+(.+?)\}/";
	/**
	 * Template tag callback
	 * @var string
	 */
	public $function = "server::templateMatches";

	/**
	 * Class constructor
	 */
	function __construct() {
		templates::defTempPatterns($this->pattern,$this->function,$this);
	}

	/**
	 * Engine template tag handler
	 * @param $matches
	 *        regex matches from the engine template handler
	 *          - var: the variable to return from $_SERVER
	 * @return string
	 */
	public static function templateMatches($matches) {
		
		$attPairs = attPairs($matches[1]);
		if (!isset($attPairs['var'])) {
			return("No variable specified");
		}
		
		if(isset($_SERVER[$attPairs['var']])) {
			return ($_SERVER[$attPairs['var']]);
		}
		
		return("Variable not found in \$_SERVER");
	}

	private static function cleanServerVars($var) {
		if (isset($_SERVER[$var])) {
			$_SERVER[$var] = htmlSanitize($_SERVER[$var]);
		}

		return TRUE;
	}

	public static function cleanHTTPReferer() {
		return self::cleanServerVars('HTTP_REFERER');
	}
	
	public static function cleanQueryStringReferer() {
		return self::cleanServerVars('QUERY_STRING');
	}
		
}

?>