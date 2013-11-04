<?php
/**
 * EngineAPI phpself module
 * @package EngineAPI\modules\phpself
 */
class phpself {
	/**
	 * Template tag pattern
	 * @var string
	 */
	public $pattern  = "/\{phpself\s+(.+?)\}/";
	/**
	 * Template tag callback
	 * @var string
	 */
	public $function = "phpself::templateMatches";

	/**
	 * Class constructor
	 */
	function __construct() {
		templates::defTempPatterns($this->pattern,$this->function,$this);
	}

	/**
	 * Engine tag handler
	 *
	 * @param $matches
	 * @return string
	 */
	public static function templateMatches($matches) {

		$obj      = templates::retTempObj("phpself");
		$attPairs = attPairs($matches[1]);
		$phpself  = $_SERVER['PHP_SELF'];

		if (strtolower($attPairs['query']) == "true") {
			$qs = preg_replace('/&amp;/','&',$_SERVER['QUERY_STRING']);
			$phpself .= "?".$qs;
		}

		return($phpself);
	}

	public static function clean() {
		$phpself             = basename($_SERVER['SCRIPT_FILENAME']);
		$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'],$phpself)).$phpself;
		return TRUE;
	}

}

?>