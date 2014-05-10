<?php

class enginevars extends config {

	private static $classInstance;

	public function __construct($engineDir, $site) {
		$defaults = self::loadconfig($engineDir."/config/default.php");

		$sitePath = $engineDir."/config/".$site.".php";
		$siteVars = ($site != "default" && is_readable($sitePath))
			? self::loadconfig($sitePath)
			: array('engineVars' => array());

		$this->variables = array_merge($defaults['engineVars'], $siteVars['engineVars']);
	}

	public static function getInstance($engineDir=NULL, $site="default") {
		if (!isset(self::$classInstance)) {
			if (isnull($engineDir)) return FALSE;

			self::$classInstance = new self($engineDir, $site);
		}

		return self::$classInstance;
	}

}
?>
