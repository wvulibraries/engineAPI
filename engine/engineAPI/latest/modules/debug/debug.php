<?php
/**
 * EngineAPI debug module
 * @todo Is this still used?
 * @package EngineAPI\modules\debug
 */
class debug {
	/**
	 * @var self
	 */
	private static $instance;
	
	private $debug = array();

	/**
	 * Password, set by application author, for displaying debug infromation
	 *
	 * @todo Should this be public?
	 * @var string
	 */
	public $password;

	/**
	 * password passed to application via query string (debugPassword) compared to $password
	 *
	 * @var string
	 */
	private $getPassword;
	
	private function __construct() {
		if(isset($_GET['HTML']['debug'])) {
			$this->debug[$_GET['HTML']['debug']] = TRUE;
			if(isset($_GET['HTML']['debugPassword']) && !is_empty($_GET['HTML']['debugPassword'])) {
				$this->getPassword = $_GET['HTML']['debugPassword'];
			}
		}
	}
	
	public static function create() {
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c();
		}

		return self::$instance;
	}

	public function needed($type){
		if(isnull($this->password) || isnull($this->getPassword)) return (FALSE);
		if($this->password != $this->getPassword) return (FALSE);
		if(isset($this->debug[$type]) || isset($this->debug["all"])) return (TRUE);
		return (FALSE);
	}

	/**
	 * Print the EngineAPI environment
	 * Prints te EngineAPI EngineVars and LocalVars
	 *
	 * @todo Remove usage of global $engineVars
	 * @todo Look at cleanup / rewrite
	 * @return bool
	 */
	public static function printENV() {
		

		print "<p><strong>Engine Variables:</strong>:<br />";
		foreach ($engineVars as $key => $value) {
			print "$key : <em>$value</em> <br />";
		}
		print "</p>";

		$localvars = localvars::getInstance();
		$localVars = $localvars->export();

		print "<p><strong>Local Variables:</strong>:<br />";
		foreach ($localVars as $key => $value) {
			print "$key : <em>$value</em> <br />";
		}
		print "</p>";

		return TRUE;
	}
	
	/**
	 * Output Butter save version of print_r()
	 *
	 * @todo This function still needs a lot of work
	 * @see http://de.php.net/manual/en/function.print-r.php#75872
	 * @param $var
	 * @param bool $return
	 * @param int $level
	 * @return string
	 */
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
		else {
			$title = "Error!";
		}
		$output = $title . $newline . $newline;
		foreach($var as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$level++;
				$value = obsafe_print_r($value, true, $level);
				$level--;
			}
			$output .= $tabs . "[" . $key . "] => " . ((isnull($value))?"NULL":$value) . $newline;
		}
		if ($return) return $output;
		else echo $output;
		return;
	}
}

?>