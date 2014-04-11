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
		$defaults = self::loadconfig($engineDir."/config/defaultPrivate.php");

		$sitePath = $engineDir."/config/".$site."Private.php";
		$siteVars = ($site != "default" && is_readable($sitePath))
			? self::loadconfig($sitePath)
			: array('engineVarsPrivate' => array());

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
		if (!isset(self::$classInstance)) {
			if (isnull($engineDir)) return FALSE;

			self::$classInstance = new self($engineDir, $site);
		}

		return self::$classInstance;
	}

}
?>
