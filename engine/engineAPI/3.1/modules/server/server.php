<?php

class server {
	
	// For template matching
	public $pattern  = "/\{server\s+(.+?)\}/";
	public $function = "server::templateMatches";
	
	function __construct() {
		$engine   = EngineAPI::singleton();
		$engine->defTempPattern($this->pattern,$this->function,$this);
	}
	
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