<?php
/**
 * EngineAPI localvars module
 * @package EngineAPI\modules\localvars
 */
class localvars extends config {

	/**
	 * @var self
	 */
	private static $classInstance;

	/**
	 * Class constructor
	 */
	public function __construct() {
        // Register {local ...} engine tags
		templates::defTempPatterns("/\{local\s+(.+?)\}/", "localvars::templateMatches", $this);
	}

	/**
	 * Create or retrieve a localvars object
	 * @return self
	 */
	public static function getInstance() {
		// Cache self if it's not already cached
		if (!isset(self::$classInstance)) {
			self::$classInstance = new self;
		}

		// Return cached instance
		return self::$classInstance;
	}

	/**
	 * Engine tag handler
	 * @param $matches Matches passed by template handler
	 * @return string
	 */
	public static function templateMatches($matches) {
		$attrPairs = attPairs($matches[1]);
		$variable  = self::get_static($attrPairs['var']);

        // Return the variable if it exists
		if (!is_empty($variable)) {
			return $variable;
		}

        // Doesn't exist or is empty, return an empty string
		return '';
	}

	/**
	 * Static wrapper for export()
	 * @see config::export()
	 * @return array
	 */
	public static function export_static() {
		return self::getInstance()->export();
	}

	/**
	 * Static wrapper for get()
	 * @see config::get()
	 * @param string $var
	 * @return string
	 */
	public static function get_static($var) {
		return self::getInstance()->get($var);
	}

}
?>
