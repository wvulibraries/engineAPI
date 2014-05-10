<?php

class privatevars extends config {

	private static $classInstance;
	protected $variables = array();

	function __construct($engineDir, $site) {
		$defaults = parent::loadconfig($engineDir."/config/defaultPrivate.php");
		$siteVars = ($site != "default" && is_readable($engineDir."/config/".$site."Private.php"))?parent::loadconfig($engineDir."/config/".$site."Private.php"):array();

		$ev1 = $defaults['engineVarsPrivate'];
		$ev2 = isset($siteVars['engineVarsPrivate'])?$siteVars['engineVarsPrivate']:array();

		$this->variables = array_merge($ev1, $ev2);

		// $this->configObject = config::getInstance();
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
