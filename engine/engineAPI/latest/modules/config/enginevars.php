<?php
/**
 * EngineAPI enginevars module
 * @package EngineAPI\modules\enginevars
 */
class enginevars extends config {

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
		$defaults = self::loadconfig($engineDir."/config/default.php");

		$sitePath = $engineDir."/config/".$site.".php";
		$siteVars = ($site != "default" && is_readable($sitePath))
			? self::loadconfig($sitePath)
			: array('engineVars' => array());

		$this->variables = array_merge($defaults['engineVars'], $siteVars['engineVars']);
	}

	/**
	 * Create or retrieve an enginevars object
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
