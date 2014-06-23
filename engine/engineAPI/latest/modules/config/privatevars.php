<?php
/**
 * EngineAPI privatevars module
 * @package EngineAPI\modules\privatevars
 */
class privatevars extends config {

	/**
	 * @var self
	 */
	private static $classInstance;

	/**
	 * Class contructor
	 *
	 * @param $engineDir The directory path for EngineAPI
	 * @param $site      The site to use
	 */
	public function __construct($engineDir, $site) {
		// Load default variables
		$defaults = self::loadconfig($engineDir."/config/defaultPrivate.php");

		// Load site specific variables
		$sitePath = $engineDir."/config/".$site."Private.php";
		$siteVars = ($site != "default" && is_readable($sitePath))
			? self::loadconfig($sitePath)
			: array('engineVarsPrivate' => array());

		// Override defaults with site variables and save as instance variables
		$this->variables = array_merge($defaults['engineVarsPrivate'], $siteVars['engineVarsPrivate']);
	}

	/**
	 * Create or retrieve a privatevars object
	 *
	 * @param $engineDir The directory path for EngineAPI
	 * @param $site      The site to use (default: "default")
	 * @return bool|self
	 */
	public static function getInstance($engineDir=NULL, $site="default") {
		// Cache self if it's not already cached
		if (!isset(self::$classInstance)) {
			// Require Engine directory
			if (isnull($engineDir)) return FALSE;

			self::$classInstance = new self($engineDir, $site);
		}

		// Return cached instance
		return self::$classInstance;
	}

}
?>
