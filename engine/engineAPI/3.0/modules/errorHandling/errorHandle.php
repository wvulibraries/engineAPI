<?php

class errorHandle {

	const ERROR   = "error";
	const SUCCESS = "success";
	const WARNING = "warning";

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

	public static function prettyPrint($type="all") {
		
		if (!(isset($this) && get_class($this) == __CLASS__)) {
			$engine = EngineAPI::singleton();
		}
		else {
			$engine = $this->engine;
		}
		
		$output = '<ul class="errorPrettyPrint">';
		
		if ($type == "all") {
			
			if (!isset($engine->errorStack['all']) || !is_array($engine->errorStack['all'])) {
				return(FALSE);
			}
			
			foreach ($engine->errorStack['all'] as $V) {
				
				switch ($V['type']) {
					case errorHandle::ERROR:
						$class = "errorMessage";
						break;
					case errorHandle::SUCCESS:
						$class = "successMessage";
						break;
					case errorHandle::WARNING:
						$class = "warningMessage";
						break;
					default:
						break;
				}
				
				$output .= "<li>";
				$output .= '<span class="'.$class.'">';
				$output .= $V['message'];
				$output .= "</span>";
				$output .= "</li>";
			}
		}
		else {
			
			if (!isset($engine->errorStack[$type]) || !is_array($engine->errorStack[$type])) {
				return(FALSE);
			}
			
			switch ($type) {
				case errorHandle::ERROR:
					$class = "errorMessage";
					break;
				case errorHandle::SUCCESS:
					$class = "successMessage";
					break;
				case errorHandle::WARNING:
					$class = "warningMessage";
					break;
				default:
					break;
			}
			
			foreach ($engine->errorStack[$type] as $V) {
				
				$output .= "<li>";
				$output .= '<span class="'.$class.'">';
				$output .= $V;
				$output .= "</span>";
				$output .= "</li>";
			}
		}
		
		$output .= '</ul>';
		
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
		
		if (!isset($engine->errorStack[$type])) {
			$engine->errorStack[$type] = array();
		}
		if (!isset($engine->errorStack["all"])) {
			$engine->errorStack["all"] = array();
		}
		
		$engine->errorStack[$type][] = $message;
		
		$temp = array();
		$temp['message'] = $message;
		$temp['type']    = $type;
		$engine->errorStack["all"][] = $temp;
		
		return;
	}

}

?>