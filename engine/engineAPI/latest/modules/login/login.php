<?php

class login {

	private static $enginevars;
	private static $loginFunctions = array();
	public static  $loginType = NULL;
	
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
	 * Process a login
	 * @param $loginType
	 * @return bool
	 */
	public static function login() {
		if (isnull(self::$loginType)) return FALSE;

		if (isset(self::$loginFunctions[self::$loginType])) {

			$loginFunction = self::$loginFunctions[self::$loginType];

			if($loginFunction(trim($_POST['RAW']['username']),$_POST['RAW']['password'])) {
				return TRUE;
			}
		}
		return FALSE;
	}

}

?>