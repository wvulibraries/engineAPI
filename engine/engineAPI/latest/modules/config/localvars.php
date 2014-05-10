<?php
/**
 * EngineAPI localvars module
 * @package EngineAPI\modules\localvars
 */
class localvars extends config {

	private static $classInstance;

	/**
	 * Class constructor
	 */
	public function __construct() {
		templates::defTempPatterns("/\{local\s+(.+?)\}/", "localvars::templateMatches", $this);
	}

	public static function getInstance() {
		if (!isset(self::$classInstance)) {
			self::$classInstance = new self;
		}

		return self::$classInstance;
	}

	/**
	 * Engine tag handler
	 * @param $matches
	 *        Matches passed by template handler
	 * @return string
	 */
	public static function templateMatches($matches) {
		$attrPairs = attPairs($matches[1]);
		$variable  = self::get_static($attrPairs['var']);

		if (!is_empty($variable)) {
			return $variable;
		}

		return '';
	}

	public static function export_static() {
		return self::getInstance()->export();
	}

	public static function get_static($var) {
		return self::getInstance()->get($var);
	}

}
?>
