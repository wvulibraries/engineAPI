<?php

class privatevars extends config {

	private static $classInstance;
	protected $variables = array();

	function __construct($engineDir,$site) {
		$defaults = parent::loadconfig($engineDir."/config/defaultPrivate.php");
		$siteVars = ($site != "default" && is_readable($engineDir."/config/".$site."Private.php"))?parent::loadconfig($engineDir."/config/".$site."Private.php"):array();

		$ev1 = $defaults['engineVarsPrivate'];
		$ev2 = isset($siteVars['engineVarsPrivate'])?$siteVars['engineVarsPrivate']:array();

		$this->variables = array_merge($ev1,$ev2);

		// $this->configObject = config::getInstance();
	}

	public static function getInstance($engineDir=NULL,$site="default") {
		if (!isset(self::$classInstance)) { 

			if (isnull($engineDir)) return FALSE;

			self::$classInstance = new self($engineDir,$site);
		}

		return self::$classInstance;
	}

	// const CONFIG_TYPE     = "private";

	// private $configObject = NULL;

	// function __construct() {
	// 	$this->configObject = config::getInstance();
	// }

	// public static function getInstance() {
	// 	return new self;
	// }

	// public function set($name,$value,$null=FALSE) {
	// 	return $this->configObject->set(self::CONFIG_TYPE,$name,$value,$null);
	// }

	// public function is_set($name) {
	// 	return $this->configObject->is_set(self::CONFIG_TYPE,$name);
	// }

	// public function get($name,$default="") {
	// 	return $this->configObject->get(self::CONFIG_TYPE,$name,$default);
	// }

	// public function remove($var) {
		
	// 	return $this->configObject->remove(self::CONFIG_TYPE,$var);
		
	// }

	// public function variable($var,$value=NULL,$null=FALSE) {
		
	// 	return $this->configObject->variable(self::CONFIG_TYPE,$var,$value,$null);
		
	// }

	// public function export() {
	// 	return $this->configObject->export(self::CONFIG_TYPE);
	// }

}

?>