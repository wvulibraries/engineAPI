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
		$engine   = EngineAPI::singleton();
		$engine->defTempPattern($this->pattern,$this->function,$this);
	}

	/**
	 * Engine template tag handler
	 * @param $matches
	 *        regex matches from the engine template handler
	 *          - var: the variable to return from $_SERVER
	 * @return string
	 */
	public static function templateMatches($matches) {
		$engine   = EngineAPI::singleton();
		$obj      = $engine->retTempObj("server");
		
		$attPairs = attPairs($matches[1]);
		if (!isset($attPairs['var'])) {
			return("No variable specified");
		}
		
		if(isset($_SERVER[$attPairs['var']])) {
			return ($_SERVER[$attPairs['var']]);
		}
		
		return("Variable not found in \$_SERVER");
	}
	
}

?>