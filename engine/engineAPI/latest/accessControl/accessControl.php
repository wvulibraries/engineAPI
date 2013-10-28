<?php 

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

	function __construct() {}

	public static function init() {
		$accessModDirHandle = @opendir(EngineAPI::$engineVars['accessModules']) or die("Unable to open ".EngineAPI::$engineVars['accessModules']);
		while (false !== ($file = readdir($accessModDirHandle))) {
			// Check to make sure that it isn't a hidden file and that it is a PHP file
			if ($file != "." && $file != ".." && $file) {
				$fileChunks = array_reverse(explode(".", $file));
				$ext= $fileChunks[0];
				if ($ext == "php") {
					include_once(EngineAPI::$engineVars['accessModules']."/".$file);
				}
			}
		}

		foreach ($accessControl as $method => $function) {
			self::$accessMethods[$method] = $function;
		}
		self::$accessMethods['denyAll']  = 'dummyFunction';
		self::$accessMethods['allowAll'] = 'dummyFunction';

		// @TODO unset $accessControl here?

		if (EngineAPI::$engineVars['accessExistsTest'] === TRUE || EngineAPI::$engineVars['accessExistsTest'] === FALSE) {
			self::$accessExistsTest = EngineAPI::$engineVars['accessExistsTest'];
		}
	}

	/**
	 * Register ACL rules
	 * hardbreak causes the function to exit immediately on a FALSE ACL return if set to TRUE
	 *
	 * @param $action
	 *        debugListAll - Prints debug info
	 *        existsTest - UNKNOWN
	 *        build - UNKNOWN
	 *        clear - Clears all acl rules
	 * @param string|null $value
	 * @param string|bool $state
	 * @param bool $hardBreak
	 * @return bool
	 */
	// @TODO this function needs to be broken up into additional methods. Handling too many things
	public static function accessControl($action,$value=NULL,$state=FALSE,$hardBreak=TRUE) {

		if ($action == "debugListAll") {
			print "<pre>";
			var_dump(self::$acl);
			print "</pre>";
			return TRUE;
		}

		if ($action == "existsTest") {
			if ($value === TRUE || $value === FALSE) {
				self::$accessExistsTest = $value;
				return TRUE;
			}
			return FALSE;
		}

		if ($action == "build") {

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

		if ($action == "clear") {
			unset(self::$acl);
			self::$acl = array();
			$aclCount  = 0;
			return TRUE;
		}

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


		header( 'Location: '.EngineAPI::$engineVars['loginPage'].'?page='.$_SERVER['PHP_SELF']."&qs=".(urlencode($_SERVER['QUERY_STRING'])) ) ;
		//die("No Access Here");
		//return FALSE;
	}

}

?>