<?php

class errorHandle {

	private $engine     = null;
	
	// Defines the element that gets used when a message output is returned. 
	// defaults to "<p>" and "</p>" ... should be the element without the 
	// great/less thans. 
	public static $spanElement = "p";

	function __construct() {
		$this->engine = EngineAPI::singleton();
	}

	public static function errorMsg($message) {
		
		$engine  = EngineAPI::singleton();
		
		self::errorStack("error",$message);
		
		$output  = "<".self::$spanElement;
		$output .= ' class="errorMessage">';
		$output .= $message;
		$output .= "</".self::$spanElement.">";
		
		return($output);
	}

	public static function successMsg($message) {
		
		$engine = EngineAPI::singleton();
		
		self::errorStack("success",$message);
		
		$output  = "<".self::$spanElement;
		$output .= ' class="successMessage">';
		$output .= $message;
		$output .= "</".self::$spanElement.">";
		
		return($output);
		
	}

	public static function warningMsg($message) {
		
		$engine = EngineAPI::singleton();
		
		self::errorStack("warning",$message);
		
		$output  = "<".self::$spanElement;
		$output .= ' class="warningMessage">';
		$output .= $message;
		$output .= "</".self::$spanElement.">";
		
		return($output);
	}

	private function errorStack($type,$message) {
		
		$engine = NULL;
		
		if (!(isset($this) && get_class($this) == __CLASS__)) {
			$engine = EngineAPI::singleton();
		}
		else {
			$engine = $this->engine;
		}
		$engine->errorStack[$type][] = $message;
		$engine->errorStack["all"][] = $message;
		
		return;
	}

}

?>