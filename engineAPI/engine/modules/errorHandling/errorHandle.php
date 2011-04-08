<?php

class errorHandle {

	private $engine = null;

	function __construct() {
		$this->engine = EngineAPI::singleton();
	}

	public static function errorMsg($message, $p=TRUE) {
		
		$engine  = EngineAPI::singleton();
		
		self::errorStack("error",$message);
		
		$output  = ($p === TRUE)?"<p ":"<span ";
		$output .= 'class="errorMessage">';
		$output .= $message;
		$output .= ($p === TRUE)?"</p>":"</span>";
		
		return($output);
	}

	public static function successMsg($message, $p=TRUE) {
		
		$engine = EngineAPI::singleton();
		
		self::errorStack("success",$message);
		
		$output  = ($p === TRUE)?"<p ":"<span ";
		$output .= 'class="successMessage">';
		$output .= $message;
		$output .= ($p === TRUE)?"</p>":"</span>";
		
		return($output);
		
	}

	public static function warningMsg($message, $p=TRUE) {
		
		$engine = EngineAPI::singleton();
		
		self::errorStack("warning",$message);
		
		$output  = ($p === TRUE)?"<p ":"<span ";
		$output .= 'class="warningMessage">';
		$output .= $message;
		$output .= ($p === TRUE)?"</p>":"</span>";
		
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