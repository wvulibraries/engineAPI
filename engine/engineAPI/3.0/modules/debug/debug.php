<?php

class debug {
	
	private static $instance; // Hold an instance of this object, for use as Singleton
	
	private $debug   = array();
	
	// $password : Password, set by application author, for displaying debug infromation
	// $getPassword : password passed to application via query string (debugPassword),
	//     compared to $password
	public $password     = NULL;
	private $getPassword = NULL;
	
	private function __construct() {
		$this->engine = EngineAPI::singleton();
		if(isset($this->engine->cleanGet['HTML']['debug'])) {
			$this->debug[$this->engine->cleanGet['HTML']['debug']] = TRUE;
			if(isset($this->engine->cleanGet['HTML']['debugPassword']) && !is_empty($this->engine->cleanGet['HTML']['debugPassword'])) {
				$this->getPassword = $this->engine->cleanGet['HTML']['debugPassword'];
			}
		}
	}
	
	function __destruct() {
	}

	public static function create() {
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c();
		}

		return self::$instance;
	}
	
	public function needed($type) {

		if (isnull($this->password) || isnull($this->getPassword)) {
			return(FALSE);
		}
		
		if ($this->password != $this->getPassword) {
			return(FALSE);
		}

		if (isset($this->debug[$type]) || isset($this->debug["all"])) {
			return(TRUE);
		}

		return(FALSE);
		
	}
	
	public static function printENV() {
		
		$engine = EngineAPI::singleton();

		if(isnull($engine)) {
			return(FALSE);
		}

		global $engineVars;

		print "<p><strong>Engine Variables:</strong>:<br />";
		foreach ($engineVars as $key => $value) {
			print "$key : <em>$value</em> <br />";
		}
		print "</p>";

		$localVars = $engine->localVarsExport();

		print "<p><strong>Local Variables:</strong>:<br />";
		foreach ($localVars as $key => $value) {
			print "$key : <em>$value</em> <br />";
		}
		print "</p>";

		return;
		
	}
	
	/* Stolen from: http://de.php.net/manual/en/function.print-r.php#75872 */
	/* Modified to suite our needs */
	/* This function still needs a lot of work */
	public static function obsafe_print_r($var, $return = TRUE, $level = 0) {
		$html = false;
		$spaces = "";
		$space = $html ? "&nbsp;" : " ";
		$newline = $html ? "<br />" : "\n";
		for ($i = 1; $i <= 6; $i++) {
			$spaces .= $space;
		}
		$tabs = $spaces;
		for ($i = 1; $i <= $level; $i++) {
			$tabs .= $spaces;
		}
		if (is_array($var)) {
			$title = "Array";
		} elseif (is_object($var)) {
			$title = get_class($var)." Object";
		}
		$output = $title . $newline . $newline;
		foreach($var as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$level++;
				$value = obsafe_print_r($value, true, $html, $level);
				$level--;
			}
			$output .= $tabs . "[" . $key . "] => " . $value . $newline;
		}
		if ($return) return $output;
		else echo $output;
		return;
	}
	
}

?>