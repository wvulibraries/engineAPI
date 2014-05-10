<?php

class enginevars extends config {

	private static $classInstance;

	function __construct($engineDir, $site) {
		$defaults = parent::loadconfig($engineDir."/config/default.php");
		$siteVars = ($site != "default" && is_readable($engineDir."/config/".$site.".php"))?parent::loadconfig($engineDir."/config/".$site.".php"):array();

		$ev1 = $defaults['engineVars'];
		$ev2 = isset($siteVars['engineVars'])?$siteVars['engineVars']:array();

		$this->variables = array_merge($ev1, $ev2);
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
