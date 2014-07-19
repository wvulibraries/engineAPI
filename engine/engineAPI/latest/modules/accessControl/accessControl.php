<?php

/**
 * Class accessControl
 * @todo Change existing engineAPI access drivers to use new registerAccessMethod() method
 */
class accessControl {

	/**
	 * Access Methods
	 * @var array
	 */
	private static $accessMethods = array();
	/**
	 * Unknown
	 * @var bool
	 */
	private static $accessExistsTest = TRUE;
	/**
	 * ACL items
	 * @var array
	 */
	private static $acl = array();
	/**
	 * ACL groups
	 * @var array
	 */
	private static $aclgroups = array();
	/**
	 * ACL count
	 * @var int
	 */
	private static $aclCount = 0;

	private static $enginevars;

	function __construct() {}

	public static function init() {

		self::set_enginevars(enginevars::getInstance());

		$returnVars = loader(self::$enginevars->get("accessModules"));

		foreach ($returnVars['accessControl'] as $method => $function) {
			self::$accessMethods[$method] = $function;
		}
		self::$accessMethods['denyAll']  = 'dummyFunction';
		self::$accessMethods['allowAll'] = 'dummyFunction';

		// @TODO unset $accessControl here?

		if (self::$enginevars->get("accessExistsTest") === TRUE || self::$enginevars->get("accessExistsTest") === FALSE) {
			self::$accessExistsTest = $enginevars->get("accessExistsTest");
		}
	}

	public static function set_enginevars($enginevars) {
		self::$enginevars = $enginevars;
		return TRUE;
	}

	/**
	 * Register a new access method
	 * @author David Gersting
	 * @param string $name
	 * @param string $function
	 * @return bool
	 */
	public static function registerAccessMethod($name, $function){
		if(in_array($name, array_keys(self::$accessMethods))){
			errorHandle::newError(__METHOD__."() Access method '$name' already defined!", errorHandle::DEBUG);
			return FALSE;
		}

		self::$accessMethods[$name] = $function;
		return TRUE;
	}

	/**
	 * Lists the ACLs that have been registered, printed to the screen
	 * @return BOOL TRUE
	 */
	public static function listACLs() {
		print "<pre>";
		var_dump(self::$acl);
		print "</pre>";

		return TRUE;
	}

	/**
	 * removes all the current ACLs from the list
	 * @return BOOL TRUE
	 */
	public static function clear() {
		unset(self::$acl);
		self::$acl = array();
		$aclCount  = 0;
		return TRUE;
	}

	/**
	 * builds the current ACL list, and applies it.
	 * @return BOOL / Redirect Returns TRUE on successful access, otherwise redirects to login page.
	 */				
	public static function build() {

			$auth  = NULL;
			$count = 0;

			foreach (self::$acl as $key => $value) {

				$action = $value['action'];

				if ($action == "denyAll") {
					// If this is the first item in the array, access is denied.
					// if it is NOT the first item, we assume it is the last intended to be
					// evaluated as a 'catch all'
					if ($count === 0) {
						self::accessControlDenied();
						exit;
					}
					else {
						break;
					}
				}

				$count++;

				if ($action == "allowAll") {
					return TRUE;
				}

				$foo = self::$accessMethods[$action];

				$returnValue = $foo($value['value'],$value['state']);

				// NULL value is error state. set auth to false to be safe
				if (isnull($returnValue)) {
					if ($value['hardBreak'] === TRUE) {
						self::$aclgroups[$action] = FALSE;
						self::accessControlDenied();
						exit;
					}
					self::$aclgroups[$action] = FALSE;
					continue;
				}
				else if ($returnValue === FALSE) {
					if ($value['hardBreak'] === TRUE) {
						self::$aclgroups[$action] = FALSE;
						self::accessControlDenied();
						exit;
					}
					if (self::$aclgroups[$action] === TRUE) {
						continue;
					}
					self::$aclgroups[$action] = FALSE;
				}
				else if ($returnValue === TRUE) {
					self::$aclgroups[$action] = TRUE;
				}
			}

			// foreach group ("action") check if it is true. If all actions are true, YAY!
			// Otherwise Ugh!
			$auth = NULL;
			foreach (self::$aclgroups as $key => $value) {

				// At this point, the only "FALSE" things should be those that did not have a hard break
				// so we should NOT exit if we see them, unless ALL things fail.

				if ($value === FALSE) {
					if (isnull($auth)) {
						$auth = FALSE;
					}
				}
				else if ($value === TRUE) {
					$auth = TRUE;
				}
				else {
					// Safety check in case of errors
					$auth = NULL;
				}
			}

			if ($auth === TRUE) {
				return TRUE;
			}


			self::accessControlDenied();
			exit;

			return $auth;

	}

	/**
	 * Sets the value of $accessExistsTest
	 *
	 * If $accessExistsTest is TRUE and the passed in ACL type is not defined in the system, the program
	 * will exit out. By default, we don't check that an ACL method exists. We leave it to the developer
	 * 
	 * @param  bool   $value 
	 * @return bool        TRUE
	 */
	public static function existsTest(bool $value) {
		self::$accessExistsTest = $value;
		return TRUE;
	}

	/**
	 * Register ACL rules
	 * hardbreak causes the function to exit immediately on a FALSE ACL return if set to TRUE
	 *
	 * @param $action the ACL to be added to the system.
	 *                    allowAll
	 *                    denyAll
	 *                    defined in accessControl Methods
	 * @param string|null $value
	 * @param string|bool $state
	 * @param bool $hardBreak
	 * @return bool
	 */
	public static function accessControl($action,$value=NULL,$state=FALSE,$hardBreak=TRUE) {


		if(!isset(self::$accessMethods[$action])) {
			if (self::$accessExistsTest === TRUE) {
				die("Access Control $action is undefined. Exiting.\n");
			}
			return FALSE;
		}

		self::$acl[self::$aclCount]['action']     = $action;
		self::$acl[self::$aclCount]['value']      = $value;
		self::$acl[self::$aclCount]['state']      = $state;
		self::$acl[self::$aclCount]['hardBreak']  = $hardBreak;


		if ($action != "denyAll") {
			self::$aclgroups[$action] = FALSE;
		}

		self::$aclCount++;

		return TRUE;
	}

	/**
	 * accessControlDenied()
	 */
	private static function accessControlDenied() {

		ob_end_clean();

		session::set("page",$_SERVER['PHP_SELF']);

		//@TODO : this query_String shouldn't be straight html sanitized, so that the &'s dont get screwed
		session::set("qs",preg_replace('/&#039;/',"'",urldecode(html_entity_decode($_SERVER['QUERY_STRING']))));


		header( 'Location: '.self::$enginevars->get("loginPage").'?page='.$_SERVER['PHP_SELF']."&qs=".(urlencode($_SERVER['QUERY_STRING'])) ) ;
		//die("No Access Here");
		//return FALSE;
	}

}

?>