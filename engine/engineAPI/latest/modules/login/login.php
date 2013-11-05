<?php

class login {

	private static $enginevars;
	private static $loginFunctions = array();
	
	function __construct() {}

	public static function init() {
		self::set_enginevars(enginevars::getInstance());
		$returnVars = loader(self::$enginevars->get('loginModules'));

		foreach ($returnVars['loginFunctions'] as $type => $function) {
			self::$loginFunctions[$type] = $function;
		}

	}

	public static function set_enginevars($enginevars) {
		self::$enginevars = $enginevars;
		return TRUE;
	}

	/**
	 * Process a login (Is this deprecated?)
	 *
	 * @deprecated
	 * @param $loginType
	 * @return bool
	 */
	public static function login($loginType) {
		if (isset($this->loginFunctions[$loginType])) {
			if($this->loginFunctions[$loginType](trim($_POST['RAW']['username']),$_POST['RAW']['password'])) {
				return TRUE;
			}
		}
		return FALSE;
	}

}

?>