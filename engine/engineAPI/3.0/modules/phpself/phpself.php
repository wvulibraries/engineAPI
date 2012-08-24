<?php

class phpself {
	
	private $engine = NULL;
	public $pattern  = "/\{phpself\s+(.+?)\}/";
	public $function = "phpself::templateMatches";
	
	function __construct() {
		$engine = EngineAPI::singleton();
		$engine->defTempPattern($this->pattern,$this->function,$this);
	}
	
	public static function templateMatches($matches) {
		
		$engine   = EngineAPI::singleton();

		$obj      = $engine->retTempObj("phpself");

		$attPairs = attPairs($matches[1]);

		$phpself = $_SERVER['PHP_SELF'];

		if (strtolower($attPairs['query']) == "true") {
			$qs = preg_replace('/&amp;/','&',$_SERVER['QUERY_STRING']);

			$phpself .= "?".$qs;
		}

		return($phpself);
		
	}
	
}

?>