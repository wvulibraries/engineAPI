<?php
/**
 * EngineAPI user authorization module
 * @package EngineAPI\modules\auth
 *
 * @todo [Added Functionality] blame() - Determine 'why' something happened. ("I'm denied because 'this' authorization on 'this' object)
 *
 * Terms:
 *   + Entity     - The user object, either a user (uid:1) or a group (gid:2)
 *   + Object     - The 'thing' being protected
 *   + Permission - An optional bitwise permission flag to check
 * ===================================================================================================================================
 *
 * First, we have to 'register' the object with the system. This method (auth::createObject) takes the following params:
 *   + id         - the uid for this object
 *   + [parent]   - The uid of the parent object
 *   + [inherits] - True/False flag showing whether this object will inherit permissions
 *   + [metaData] - Any addition meta data to be stored along with the object
 *
 * Now, we need to 'register' the permissions we'll be using. This method (auth:createPermission) takes the following params:
 *   + object - The object this permission is 'visible' to, or NULL to make it visible globally (and make it globally inheritable)
 *   + name   - The name of the permission (name must be unique within both the object and the globals)
 *   + [desc] - An optional description for the permission
 *
 * Okay, now we can assign a permission for an entity to an object. This is done with the auth::grant() method which takes the following params:
 *   + authObject    - The object this authorization applies to
 *   + authEntity    - The entity that this authorization applies to (user / group)
 *   + permission    - The permission to grant
 *   + [inheritable] - True/False flag showing if this permission should inherit down (note: global permissions will always inherit)
 *   + [policy]      - 'allow' or 'deny' (A deny will cause the authCheck to fail unless the USER is granted an explicit allow)
 *
 * Finally, we can check an authorization to see if a given entity has the given permission for the given object with the method auth::checkAuthorization() which takes the following params:
 *   + object     - The object to check
 *   + entity     - The user entity (user / group)
 *   + permission - The named permission to check
 *
 */
class auth extends authCommon{
	const REGEX_ENTITY       = '/([g|u])(?:id)?:(\d+)/i';
	const REGEX_ENTITY_GROUP = '/g(?:id)?:(\d+)/i';
	const REGEX_ENTITY_USER  = '/u(?:id)?:(\d+)/i';
	const REGEX_PERMISSION   = '/^(?:(.+)-)?(.+)$/';
	const GLOBAL_PERMISSION = '__GLOBAL__';
	/**
	 * @var int[]
	 */
	private static $permissionIdRegistry = array();
	/**
	 * @var authObject[]
	 */
	public static $objectRegistry = array();
	/**
	 * @var authEntity[]
	 */
	public static $entityRegistry = array();
	/**
	 * @var authEntity[]
	 */
	public static $tempRegistry = array();


	## General use methods
	##################################################################################################################################
	/**
	 * This method simply returns a non-static instance of this otherwise static class
	 * @static
	 * @return auth
	 */
	public static function getInstance(){
		return new self();
	}

	/**
	 * This method both formats and validates a given auth 'name' (object/entity/permission)
	 * These names cannot be numerical (confusion with db id #'s)
	 * And all spaces will be converted to _'s
	 *
	 * @static
	 * @param string $name
	 * @return bool|string
	 */
	public static function formatName($name)
	{
		$name = str_replace(' ','_',trim($name));
		if(is_numeric($name)){
			errorHandle::newError(__METHOD__."() - Malformed auth name '$name'! (From ".callingLine().":".callingFile().")", errorHandle::DEBUG);
			return FALSE;
		}
		return $name;
	}

	/**
	 * Returns the requested entity (user or group)
	 * Note: This method uses an internal registry that return any existing entity (unless $forceNew is true)
	 * @static
	 * @param $input
	 * @param bool $autoExpand
	 * @param bool $forceNew
	 * @return authUser|authGroup|bool
	 */
	public static function getEntity($input,$autoExpand=false,$forceNew=false)
	{
		if($input instanceof authEntity) return $input;
		$input = self::formatName($input);

		if(is_string($input) and preg_match(self::REGEX_ENTITY, $input, $m)){
			if(!isset(self::$entityRegistry[md5($input)]) or $forceNew){
				$className = ($m[1] == 'u') ? 'authUser' : 'authGroup';
				$obj = new $className($input);
				return ($obj) ? self::$entityRegistry[md5($input)] = $obj : $obj;
			}else{
				if($autoExpand) self::$entityRegistry[md5($input)]->expandTree();
				return self::$entityRegistry[md5($input)];
			}
		}else{
			errorHandle::newError(__METHOD__."() - Malformed entity identifier! ($input)", errorHandle::DEBUG);
			return FALSE;
		}
	}

	## Object methods
	##################################################################################################################################

	/**
	 * Returns the requested object
	 * Note: This method uses an internal registry that return any existing entity (unless $forceNew is true)
	 * @static
	 * @param $input
	 * @param bool $forceNew
	 * @return authObject
	 */
	public static function getObject($input,$forceNew=FALSE)
	{
		if($input instanceof authObject) return $input;
		$input = self::formatName($input);

		if(!isset(self::$objectRegistry[md5($input)]) or $forceNew){
			$obj = new authObject($input);
			return ($obj) ? self::$objectRegistry[md5($input)] = $obj : $obj;
		}else{
			return self::$objectRegistry[md5($input)];
		}
	}

	/**
	 * Creates an object in the system (Note: an 'object' represents the 'thing' we are protecting)
	 * @static
	 * @param string $id
	 * @param string $parent
	 * @param bool $inherits
	 * @param array $metaData
	 * @param bool $ignoreMissingParents
	 * @return authObject|bool
	 */
	public static function createObject($id,$parent=NULL,$inherits=TRUE,$metaData=NULL,$ignoreMissingParents=FALSE)
	{
		$authCommon = new parent();

		// Validate input
		if(isset($metaData) and !is_array($metaData)){
			errorHandle::newError(__METHOD__."() - metaData must be an array of key/value pairs!", errorHandle::DEBUG);
			return FALSE;
		}

		// Check for an existing object
		$dbObjCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `ID`='%s'",
			$authCommon->db->escape($authCommon->tblObjects),
			$authCommon->db->escape($id)));
		if(mysql_result($dbObjCheck['result'],0,'i')){
			errorHandle::newError(__METHOD__."() - Object already exists!", errorHandle::DEBUG);
			return FALSE;
		}else{
			// If a parent was passed that dosen't exist we need to fail
			if(isset($parent) and !$ignoreMissingParents){
				$dbObjCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `ID`='%s'",
					$authCommon->db->escape($authCommon->tblObjects),
					$authCommon->db->escape($parent)));
				if(!mysql_result($dbObjCheck['result'],0,'i')){
					errorHandle::newError(__METHOD__."() - Parent object dosen't exists!", errorHandle::DEBUG);
					return FALSE;
				}
			}

			// Start building the SQL statement
			$dbFields = array('`ID`');
			$dbValues = array("'".$authCommon->db->escape($id)."'");
			if(isset($parent)){
				$dbFields[] = '`parent`';
				$dbValues[] = "'".$authCommon->db->escape($parent)."'";
			}
			if(isset($inherits)){
				$dbFields[] = '`inherits`';
				$dbValues[] = "'".$authCommon->db->escape((int)$inherits)."'";
			}
			if(isset($metaData)){
				$dbFields[] = '`metaData`';
				$dbValues[] = "'".serialize($metaData)."'";
			}

			$dbObjRegister = $authCommon->db->query(sprintf("INSERT INTO `%s` (%s) VALUES(%s)",
				$authCommon->db->escape($authCommon->tblObjects),
				implode(',', $dbFields),
				implode(',', $dbValues)));
			if($dbObjRegister['errorNumber']){
				errorHandle::newError(__METHOD__."() - SQL Error! (".$dbObjRegister['error'].")", errorHandle::DEBUG);
				return FALSE;
			}else{
				// The last thing we need to do is get to this object's parents (if there is one) and trigger authorization propagation
				if(isset($parent) and !$ignoreMissingParents) auth::getObject($parent)->propagateInheritance($id);
				// Okay, we can not return the new object
				return self::getObject($id);
			}
		}
	}

	/**
	 * Removes an object from the system (Note: an 'object' represents the 'thing' we are protecting)
	 * @static
	 * @param string $id
	 * @return bool
	 */
	public static function removeObject($id)
	{
		$authCommon = new parent();

		// Start the transaction
		$authCommon->db->transBegin($authCommon->tblObjects);

		// Delete all the authorizations this object has
		$dbAuthDelete = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `authObjectID`='%s'",
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape($id)));

		// Delete the actual auth object
		$dbObjDelete = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `ID`='%s' LIMIT 1",
			$authCommon->db->escape($authCommon->tblObjects),
			$authCommon->db->escape($id)));

		if(!$dbAuthDelete['errorNumber'] and !$dbObjDelete['errorNumber']){
			// Get all the children, and kill them too :) (while listening for an error)
			$children = $authCommon->getChildren($id,FALSE);
			foreach($children as $child){
				$fn = __FUNCTION__;
				if(!self::$fn($child['ID'])){
					// An error occurred in a child!
					errorHandle::newError(__METHOD__."() - Error encountered with child '".$child['ID']."'", errorHandle::DEBUG);
					$authCommon->db->transRollback();
					$authCommon->db->transEnd();
				}
			}

			// If we're here, we're done
			$authCommon->db->transCommit();
			$authCommon->db->transEnd();
			return TRUE;
		}else{
			if($dbAuthDelete['errorNumber']) errorHandle::newError(__METHOD__."() - Failed to remove authorizations for object '$id'. (SQL Error: ".$dbAuthDelete['error'].")", errorHandle::DEBUG);
			if($dbObjDelete['errorNumber'])  errorHandle::newError(__METHOD__."() - Failed to remove object '$id'. (SQL Error: ".$dbObjDelete['error'].")", errorHandle::DEBUG);
			$authCommon->db->transRollback();
			$authCommon->db->transEnd();
			return FALSE;
		}
	}

	/**
	 * Updates an object using the provided payload
	 * @static
	 * @param string|authObject $object
	 * @param array $payload
	 * @return bool
	 */
	public static function updateObject($object,$payload)
	{
		$authCommon = new parent();
		$objParent  = auth::getObject($object)->getMetaData('parent');

		$dbFields = array();
		foreach($payload as $k => $v){
			if(is_array($v)) $v = serialize($v);
			$dbFields[] = sprintf("`%s`='%s'", $authCommon->db->escape($k), $authCommon->db->escape($v));
		}
		$dbObjUpdate = $authCommon->db->query(sprintf("UPDATE `%s` SET %s WHERE `ID`='%s' LIMIT 1",
			$authCommon->db->escape($authCommon->tblObjects),
			implode(',', $dbFields),
			$authCommon->db->escape($object)));

		if(!$dbObjUpdate['errorNumber']){
			/*
			 * The move worked!
			 * Now, we need to know if the object moved. If so, we need to trigger propagation on it's NEW parent in order to apply it's new location's permissions
			 */
			if($objParent != auth::getObject($object,TRUE)->getMetaData('parent')){
				// Propagation needed!
				if(self::getObject($objParent)->propagateInheritance($object)){
					return TRUE;
				}else{
					errorHandle::newError(__METHOD__."() - An error has occurred with the propagation!", errorHandle::CRITICAL);
					return FALSE;
				}
			}else{
				return TRUE;
			}
		}else{
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbObjUpdate['error'].")", errorHandle::DEBUG);
			return FALSE;
		}
	}

	## User groups methods
	##################################################################################################################################
	/**
	 * Returns an array of all the groups in the system
	 * @static
	 * @var string $orderBy
	 * @var boolean $returnObject
	 * @return array
	 */
	public static function getGroups($orderBy=null,$returnObject=FALSE)
	{
		$authCommon = new parent();
		$groups = array();

		$sql = sprintf("SELECT * FROM `%s`", $authCommon->db->escape($authCommon->tblGroups));
		if(isset($orderBy)) $sql .= " ORDER BY $orderBy";
		$dbGroups = $authCommon->db->query($sql);
		while($row = mysql_fetch_assoc($dbGroups['result'])){
			$groups[] = $returnObject ? self::getGroup($row['ID'], TRUE) : $row;
		}
		return $groups;
	}

	/**
	 * Returns an array of the 'root' groups (groups which do not have a parent)
	 * Note: This will also grab any orphaned groups, but this is desired
	 * @static
	 * @param null $orderBy
	 * @param boolean $returnObject
	 * @return array
	 */
	public static function getRootGroups($orderBy=null,$returnObject=FALSE)
	{
		$authCommon = new parent();
		$groups = array();
		$sql = sprintf("SELECT * FROM `%s` WHERE ID NOT IN (SELECT `childGroup` FROM `%s`)", $authCommon->db->escape($authCommon->tblGroups), $authCommon->db->escape($authCommon->tblGroups2Groups));
		if(isset($orderBy)) $sql .= " ORDER BY $orderBy";
		$dbGroups = $authCommon->db->query($sql);
		while($row = mysql_fetch_assoc($dbGroups['result'])){
			$groups[] = $returnObject ? self::getGroup($row['ID'], TRUE) : $row;
		}
		return $groups;
	}

	/**
	 * This method will lookup the groupID of a group associated with the provided LDAP DN
	 * @static
	 * @param $dn
	 * @param string $fields
	 * @return int|null
	 */
	public static function ldapDN2group($dn,$fields='ID')
	{
		$authCommon = new parent();

		if(!is_array($fields)) $fields = explode(',', $fields);
		if(sizeof($fields) == 1 AND $fields[0] == '*'){
			// Return ALL fields
			$fieldsMySQL = '*';
		}else{
			$fieldsMySQL = array();
			foreach($fields as $key => $value){
				$fields[$key] = trim($authCommon->db->escape($value));
				$fieldsMySQL[$key] = sprintf("`%s`", $fields[$key]);
			}
			$fieldsMySQL = implode(',', $fieldsMySQL);
		}

		$dbGroup = $authCommon->db->query(sprintf("SELECT %s FROM `%s` WHERE `ldapDN`='%s' LIMIT 1", $fieldsMySQL, $authCommon->db->escape($authCommon->tblGroups), $authCommon->db->escape($dn)));
		if(!$dbGroup['numRows']){
			return NULL;
		}else{
			if(sizeof($fields) > 1 or $fields[0] == '*'){
				return mysql_fetch_assoc($dbGroup['result']);
			}else{
				return mysql_result($dbGroup['result'], 0, $fields[0]);
			}
		}
	}

	/**
	 * Returns an array of the requested fields for the requested group
	 * @static
	 * @param int|string $groupKey
	 *        Either the ID or ldapDN for the group
	 * @param mixed $return
	 *        This param controls what is returned from this method
	 *        String|Array|NULL - An array of the requested fields will be returned.
	 *                            Input: array or CSV of fields to return. (NULL or '*' will return ALL fields)
	 *        Boolean - A full group object will be returned
	 *                  Input: TRUE and FALSE will have the same effect
	 * @param bool $forceNew
	 *        Set this to TRUE to force a new groupEntity to be returned (ignoring the intercal cache)
	 *        *This param has no effect if $return is not bool*
	 * @return array|authEntity
	 */
	public static function getGroup($groupKey,$return=NULL,$forceNew=FALSE)
	{
		$authCommon  = new parent();

		// The rest of this method will depend on what we're returning
		if(is_bool($return)){
			// Return a full object

			// If we DON'T have the ID, we need it (as that's how the groups are ID'd)
			if(!is_numeric($groupKey)){
				// Get the fields from the database
				$dbGroupID = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s` WHERE `ldapDN`='%s' LIMIT 1",
					$authCommon->db->escape($authCommon->tblGroups),
					$authCommon->db->escape($groupKey)));
				if(!$dbGroupID['numRows']){
					// errorHandle::newError(__METHOD__."() - Cannot find a group for ldapDN '$groupKey'!", errorHandle::DEBUG);
					return NULL;
				}else{
					$groupKey = mysql_result($dbGroupID['result'], 0, 'ID');
				}
			}
			return self::getEntity('gid:'.$groupKey, FALSE, (bool)$forceNew);
		}else{
			// return an array of fields

			// Process the fields param
			$sqlFields = array();
			if(isnull($return) or $return == '*'){
				$sqlFields = array('*');
			}else{
				if(is_string($return)) $return = explode(',', $return);
				foreach($return as $field){
					$sqlFields[] = sprintf('`%s`', $authCommon->db->escape($field));
				}
			}

			// Get the fields from the database
			$dbGroup = $authCommon->db->query(sprintf("SELECT %s FROM `%s` WHERE `ID`='%s' OR `ldapDN`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->tblGroups),
				$authCommon->db->escape($groupKey),
				$authCommon->db->escape($groupKey)));

			// Return the final array of fields
			return $dbGroup['numRows'] ? mysql_fetch_assoc($dbGroup['result']) : array();
		}
	}

	/**
	 * This method will create a new group for users and groups to be assigned to
	 * @static
	 * @param $name
	 * @param string $desc
	 * @param string $ldapDN
	 * @return bool|int
	 */
	public static function createGroup($name,$desc=NULL,$ldapDN=NULL)
	{
		$authCommon = new parent();
		$dbNewGroup = $authCommon->db->query(sprintf("INSERT INTO `%s` (`name`,`description`,`ldapDN`) VALUES('%s','%s',%s)",
			$authCommon->db->escape($authCommon->tblGroups),
			$authCommon->db->escape($name),
			isset($desc)   ? $authCommon->db->escape($desc) : '',
			isset($ldapDN) ? sprintf("'%s'", $authCommon->db->escape($ldapDN)) : "NULL"));

		if(!$dbNewGroup['errorNumber']){
			return self::getEntity("gid:".(int)$dbNewGroup['id']);
		}else{
			errorHandle::newError(__METHOD__.sprintf("() - SQL Error! (%s:%s)", $dbNewGroup['errorNumber'], $dbNewGroup['error']), errorHandle::MEDIUM);
			return FALSE;
		}
	}

	/**
	 * This method will remove a group from the system
	 * @static
	 * @param $groupKey
	 * @return bool
	 */
	public static function removeGroup($groupKey)
	{
		$authCommon = new parent();

		// Get the group's ID
		$groupID = (is_numeric($groupKey)) ? "gid:$groupKey" : $groupKey;
		$groupEntity = self::getEntity($groupID);

		// Begin the transaction
		$authCommon->db->transBegin($authCommon->tblGroups);

		// Delete all authorizations for this group
		$dbDelete1 = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `authEntity`='%s'",
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape("$groupEntity")));

		// Delete all group->group memberships
		$dbDelete2 = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `childGroup`='%s' OR `parentGroup`='%s'",
			$authCommon->db->escape($authCommon->tblGroups2Groups),
			$authCommon->db->escape($groupEntity->getMetaData('ID')),
			$authCommon->db->escape($groupEntity->getMetaData('ID'))));

		// Delete all user->group memberships
		$dbDelete3 = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `group`='%s'",
			$authCommon->db->escape($authCommon->tblUsers2Groups),
			$authCommon->db->escape($groupEntity->getMetaData('ID'))));

		// Now we can delete the actual group
		$dbDelete4 = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `ID`='%s' LIMIT 1",
			$authCommon->db->escape($authCommon->tblGroups),
			$authCommon->db->escape($groupEntity->getMetaData('ID'))));

		if(!$dbDelete1['errorNumber'] and !$dbDelete2['errorNumber'] and !$dbDelete3['errorNumber'] and !$dbDelete4['errorNumber']){
			$authCommon->db->transCommit();
			$authCommon->db->transEnd();
			return TRUE;
		}else{
			$authCommon->db->transRollback();
			$authCommon->db->transEnd();
			if($dbDelete1['errorNumber']) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete1['errorNumber'],$dbDelete1['error']), errorHandle::HIGH);
			if($dbDelete2['errorNumber']) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete2['errorNumber'],$dbDelete2['error']), errorHandle::HIGH);
			if($dbDelete3['errorNumber']) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete3['errorNumber'],$dbDelete3['error']), errorHandle::HIGH);
			if($dbDelete4['errorNumber']) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete4['errorNumber'],$dbDelete4['error']), errorHandle::HIGH);
			return FALSE;
		}
	}

	/**
	 * [List Object Callback] Create a User Group
	 * @static
	 * @return bool|int
	 */
	public static function callback_createGroup()
	{
		return self::createGroup($_POST['HTML']['name_insert'], $_POST['HTML']['description_insert'], $_POST['HTML']['ldapDN_insert']);
	}

	/**
	 * [List Object Callback] Remove a User Group
	 * @static
	 * @param $id
	 * @return bool
	 */
	public static function callback_deleteGroup($id)
	{
		return self::removeGroup($id);
	}

	## User methods
	##################################################################################################################################
	/**
	 * Returns an array of all the users in the system
	 * @static
	 * @var array $orderBy
	 * @var boolean $returnObject
	 * @return array
	 */
	public static function getUsers($orderBy=null,$returnObject=FALSE)
	{
		$authCommon  = new parent();
		$users = array();

		$sql = sprintf("SELECT * FROM `%s`", $authCommon->db->escape($authCommon->tblUsers));
		if(isset($orderBy)) $sql .= " ORDER BY $orderBy";

		$dbUsers = $authCommon->db->query($sql);
		while($row = mysql_fetch_assoc($dbUsers['result'])){
			$users[] = ($returnObject) ? self::getUser($row['ID'], TRUE) : $row;
		}
		return $users;
	}

	/**
	 * Returns an array of the requested fields for the requested user or a full userEntity
	 * @static
	 * @param int|array $userKey
	 *        array(Field, Value) or will assume 'ID' field
	 * @param mixed $return
	 *        This param controls what is returned from this method
	 *        String|Array|NULL - An array of the requested fields will be returned.
	 *                            Input: array or CSV of fields to return. (NULL or '*' will return ALL fields)
	 *        Boolean - A full group object will be returned
	 *                  Input: TRUE and FALSE will have the same effect
	 * @param bool $forceNew
	 *        Set this to TRUE to force a new groupEntity to be returned (ignoring the intercal cache)
	 *        *This param has no effect if $return is not bool*
	 * @return array|authEntity
	 */
	public static function getUser($userKey,$return=NULL,$forceNew=FALSE)
	{
		$authCommon  = new parent();

		// The rest of this method will depend on what we're returning
		if(is_bool($return)){
			// Return a full object

			// If we DON'T have the ID, we need it (as that's how the groups are ID'd)
			if(!is_numeric($userKey)){
				// Get the fields from the database
				$dbGroupID = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s` WHERE `username`='%s' LIMIT 1",
					$authCommon->db->escape($authCommon->tblGroups),
					$authCommon->db->escape($userKey)));
				if(!$dbGroupID['numRows']){
					errorHandle::newError(__METHOD__."() - Cannot find user for username '$userKey'!", errorHandle::DEBUG);
					return NULL;
				}else{
					$userKey = mysql_result($dbGroupID['result'], 0, 'ID');
				}
			}
			return self::getEntity('uid:'.$userKey, FALSE, (bool)$forceNew);
		}else{
			// return an array of fields

			// Process the fields param
			$sqlFields = array();
			if(isnull($return) or $return == '*'){
				$sqlFields = array('*');
			}else{
				if(is_string($return)) $return = explode(',', $return);
				foreach($return as $field){
					$sqlFields[] = sprintf('`%s`', $authCommon->db->escape($field));
				}
			}

			// Get the fields from the database
			$dbGroup = $authCommon->db->query(sprintf("SELECT %s FROM `%s` WHERE `ID`='%s' OR `username`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->tblGroups),
				$authCommon->db->escape($userKey),
				$authCommon->db->escape($userKey)));

			// Return the final array of fields
			return $dbGroup['numRows'] ? mysql_fetch_assoc($dbGroup['result']) : array();
		}
	}


	public static function removeUser($userKey)
	{
		$authCommon  = new parent();

		// Get the fields from the database
		$dbObjects = $authCommon->db->query(sprintf("SELECT DISTINCT authObjectID FROM %s WHERE authEntity='%s'",
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape($userKey)));
		if(!$dbObjects['error']){
			while($row = mysql_fetch_assoc($dbObjects['result'])){
				auth::revoke($row['authObjectID'], "uid:$userKey", '*');
			}
			return TRUE;
		}else{
			errorHandle::newError(__METHOD__."() - Failed to find user's authorizations (SQL Error: ".$dbObjects['error'].")!", errorHandle::DEBUG);
			return FALSE;
		}
	}



	## Permission methods
	##################################################################################################################################
	/**
	 * This method will register a new permission in the system for assignment to objects
	 * @static
	 * @param string $object
	 * @param string $name
	 * @param string $desc
	 * @param bool $systemPerm
	 * @return bool
	 */
	public static function createPermission($object, $name, $desc=NULL, $systemPerm=FALSE)
	{
		$authCommon = new parent();

		// Catch default values
		if(is_null($desc)) $desc='';

		// validate the inputs
		if(is_null($object)) $object = self::GLOBAL_PERMISSION;
		if(!$object = self::formatName($object)) return FALSE;
		if(!$name = self::formatName($name)) return FALSE;

		// Check permission name's uniqueness
		if($object == self::GLOBAL_PERMISSION){
			// Name must be globally unique
			$dbNameCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `name`='%s'",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($name)));
		}else{
			// Name must be unique across the object and global spaces
			$dbNameCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE (`object`='%s' OR `object`='%s') AND `name`='%s'",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape(self::GLOBAL_PERMISSION),
				$authCommon->db->escape($object),
				$authCommon->db->escape($name)));
		}
		if(mysql_result($dbNameCheck['result'], 0, 'i')){
			// We found a name-collision
			errorHandle::newError(__METHOD__."() - A permission already exists with the name '$name'!", errorHandle::DEBUG);
			return FALSE;
		}

		// If we get here then there's no hole available. We need to insert a new permission row (either starting a new permissions line, or adding to the end of one)
		$dbCreatePermission = $authCommon->db->query(sprintf("INSERT INTO `%s` (`object`,`name`,`description`,`system`) VALUES('%s','%s','%s','%s')",
			$authCommon->db->escape($authCommon->tblPermissions),
			$authCommon->db->escape(trim($object)),
			$authCommon->db->escape(trim($name)),
			$authCommon->db->escape(trim($desc)),
			$authCommon->db->escape(bool2str($systemPerm, TRUE))));

		if($dbCreatePermission['errorNumber']){
			errorHandle::newError(__METHOD__.sprintf("() - SQL Error! (%s:%s)",$dbCreatePermission['errorNumber'],$dbCreatePermission['error']), errorHandle::MEDIUM);
			return FALSE;
		}else{
			return $dbCreatePermission['id'];
		}
	}

	/**
	 * This method will remove a permission from the system
	 * @static
	 * @param string|int $object
	 * 		  This is either the objectID, or the permissionID
	 * @param string $name
	 *        Set to null to treat object as the permissionID
	 *        Set to '*' to delete all the permissions of the given objectID
	 *        Set to the name of the permission you want to remove
	 * @return bool
	 */
	public static function removePermission($object, $name=NULL)
	{
		$authCommon = new parent();
		$permissionIDs = (isset($name)) ? array() : array($object);

		if(isset($name) and $name='*'){
			// Delete ALL permissions of this object
			$fn = __FUNCTION__;
			$dbPermissions = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s` WHERE `object`='%s'",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($object)));
			while($permission = mysql_fetch_assoc($dbPermissions['result'])){
				$permissionIDs[] = $permission['ID'];
			}
		}

		// Okay, we now have a list of the permission IDs we need to delete
		$authCommon->db->transBegin($authCommon->tblPermissions);
		foreach($permissionIDs as $permissionID){
			// Remove all the permission's authorizations
			$dbDelete1 = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `permissionID`='%s'", $authCommon->db->escape($authCommon->tblAuthorizations), $authCommon->db->escape($permissionID)));
			// Remove the permission from the registry
			$dbDelete2 = $authCommon->db->query(sprintf("DELETE FROM `%s` WHERE `ID`='%s' LIMIT 1", $authCommon->db->escape($authCommon->tblPermissions), $authCommon->db->escape($permissionID)));

			// And check for errors
			if($dbDelete1['errorNumber'] or $dbDelete2['errorNumber']){
				$authCommon->db->transRollback();
				$authCommon->db->transEnd();
				if($dbDelete1['errorNumber']) errorHandle::newError(__METHOD__."() - [Delete1] SQL Error: ".$dbDelete1['error'], errorHandle::DEBUG);
				if($dbDelete2['errorNumber']) errorHandle::newError(__METHOD__."() - [Delete2] SQL Error: ".$dbDelete2['error'], errorHandle::DEBUG);
				return FALSE;
			}
		}

		// No errors? Good, then we can commit the transaction!
		$authCommon->db->transCommit();
		$authCommon->db->transEnd();
		return TRUE;
	}

	/**
	 * @static
	 * @param string|int $name
	 * @param string $originObject
	 * @param string $fields
	 * @return array|bool|string
	 */
	public static function lookupPermission($name,$originObject=self::GLOBAL_PERMISSION,$fields='ID')
	{
		$authCommon = new parent();

		// Process the fields param
		$sqlFields = array();
		if(!isset($fields) or $fields == '*' or (is_array($fields) and $fields[0] == '*')){
			$sqlFields = array('*');
		}else{
			if(!is_array($fields)) $fields = explode(',',$fields);
			foreach($fields as $field){
				$sqlFields[] = sprintf('`%s`', $authCommon->db->escape($field));
			}
		}

		if(is_numeric($name)){
			// Lookup by permission ID
			$dbPermission = $authCommon->db->query(sprintf("SELECT %s FROM `%s` WHERE `ID`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape((int)$name)));

		}else{
			// Lookup by permission name and object
			$dbPermission = $authCommon->db->query(sprintf("SELECT %s FROM `%s` WHERE `object`='%s' AND `name`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape(self::formatName($originObject)),
				$authCommon->db->escape(self::formatName($name))));
		}

		if($dbPermission['error']){
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermission['errorNumber'].":".$dbPermission['error'].")", errorHandle::DEBUG);
			return NULL;
		}elseif($dbPermission['numRows']){
			$result = mysql_fetch_assoc($dbPermission['result']);
			return (sizeof($sqlFields) > 1) ? $result : array_shift($result);
		}else{
			errorHandle::newError(__METHOD__."() - No permission found for the key '$originObject'-'$name'!", errorHandle::DEBUG);
			return NULL;
		}
	}

	/**
	 * Looks to see if the given permission exists in the database
	 * @static
	 * @param string|int $name
	 * @param string $object
	 * @return bool
	 */
	public static function permissionExists($name, $object=auth::GLOBAL_PERMISSION){
		$authCommon = new parent();

		if(is_numeric($name)){
			// Lookup by permission ID
			$dbPermissionExists = $authCommon->db->query(sprintf("SELECT ID FROM `%s` WHERE ID='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape((int)$name)));

		}else{
			// Lookup by permission name and object
			$dbPermissionExists = $authCommon->db->query(sprintf("SELECT ID FROM `%s` WHERE `object`='%s' AND `name`='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape(self::formatName($object)),
				$authCommon->db->escape(self::formatName($name))));
		}


		if($dbPermissionExists['error']){
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermissionExists['error'].")", errorHandle::DEBUG);
			return FALSE;
		}else{
			return $dbPermissionExists['numRows'] != 0;
		}
	}


	/**
	 * Returns an array of permissions for the given object
	 * @static
	 * @param string $object
	 * @param bool $inclGlobal
	 *        Set to true to return the global permissions as well
	 * @return array
	 */
	public static function listPermissions($object,$inclGlobal=FALSE)
	{
		$authCommon  = new parent();
		$permissions = array();

		if($inclGlobal){
			$dbPermissions = $authCommon->db->query(sprintf("SELECT * FROM `%s` WHERE `object` = '%s' OR `object` = '%s'",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($object),
				$authCommon->db->escape(self::GLOBAL_PERMISSION)));
		}else{
			$dbPermissions = $authCommon->db->query(sprintf("SELECT * FROM `%s` WHERE `object` = '%s'",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($object)));
		}

		while($row = mysql_fetch_assoc($dbPermissions['result'])){
			$row['isGlobal'] = ($row['object'] == self::GLOBAL_PERMISSION) ? TRUE : FALSE;
			$permissions[]   = $row;
		}

		return $permissions;
	}


	public static function getPermissionID($name,$object=self::GLOBAL_PERMISSION){
		$authCommon = new parent();
		$key = md5($object."|".$name);
		if(!isset(self::$permissionIdRegistry[$key])){
			$dbPermission = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s` WHERE `name`='%s' AND `object`='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($name),
				$authCommon->db->escape($object)));
			if($dbPermission['error']){
				errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermission['errorNumber'].":".$dbPermission['error'].")", errorHandle::DEBUG);
				return NULL;
			}elseif($dbPermission['numRows']){
				$result = mysql_fetch_assoc($dbPermission['result']);
				self::$permissionIdRegistry[$key] = $result['ID'];
			}else{
				errorHandle::newError(__METHOD__."() - No permission found for name:".$name." object:".$object."!", errorHandle::DEBUG);
				return NULL;
			}
		}

		return self::$permissionIdRegistry[$key];
	}

	/**
	 * Get a specific permission by ID
	 * @static
	 * @param $id
	 * @param string $fields
	 * @return array|null
	 */
	public static function getPermission($id,$fields='ID')
	{
		$authCommon = new parent();
		// Process the fields param
		$sqlFields = array();
		if(!isset($fields) or $fields == '*' or (is_array($fields) and $fields[0] == '*')){
			$sqlFields = array('*');
		}else{
			if(!is_array($fields)) $fields = explode(',',$fields);
			foreach($fields as $field){
				$sqlFields[] = sprintf('`%s`', $authCommon->db->escape($field));
			}
		}
		$dbPermission = $authCommon->db->query(sprintf("SELECT %s FROM `%s` WHERE `ID`='%s' LIMIT 1",
			implode(',',$sqlFields),
			$authCommon->db->escape($authCommon->tblPermissions),
			$authCommon->db->escape($id)));
		if($dbPermission['error']){
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermission['errorNumber'].":".$dbPermission['error'].")", errorHandle::DEBUG);
			return NULL;
		}elseif($dbPermission['numRows']){
			$result = mysql_fetch_assoc($dbPermission['result']);
			return (sizeof($sqlFields) > 1) ? $result : array_shift($result);
		}else{
			errorHandle::newError(__METHOD__."() - No permission found with id '$id'!", errorHandle::DEBUG);
			return NULL;
		}
	}

	/**
	 * [List Object Callback] Create a permission
	 * @static
	 * @return bool
	 */
	public static function callback_createPermission()
	{
		return self::createPermission($_POST['HTML']['object_insert'],$_POST['HTML']['name_insert'],$_POST['HTML']['description_insert']);
	}

	/**
	 * [List Object Callback] Remove a permissions
	 * @static
	 * @param $id
	 * @return bool
	 */
	public static function callback_deletePermission($id)
	{
		return self::removePermission($id);
	}

	## Authorization methods
	##################################################################################################################################
	/**
	 * Retrieves all the authorizations for a given object
	 * @static
	 * @param string|authObject $object
	 * @return array
	 */
	public static function listAuthorizations($object)
	{
		return self::getObject($object)->listAuthorizations();
	}

	/**
	 * @static
	 * @param $authID
	 * @return array
	 */
	public static function lookupAuthorization($authID)
	{
		$authCommon = new parent();
		$dbAuth = $authCommon->db->query(sprintf("SELECT `a`.*, `p`.`name` AS `permissionName`, `p`.`description` AS `permissionDesc`, `p`.`object` AS `permissionObject`, `p`.`ID` AS `permissionID` FROM `%s` AS `a` LEFT JOIN `%s` AS `p` ON `a`.`permissionID`=`p`.`ID` WHERE a.`ID`='%s'",
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape($authCommon->tblPermissions),
			$authCommon->db->escape($authID)));
		if($dbAuth['numRows']){
			return mysql_fetch_assoc($dbAuth['result']);
		}else{
			return array();
		}
	}

	## Assignment and Authorization Management methods
	##################################################################################################################################
	/**
	 * This method will assign the childEntity to the parentEntity
	 * @static
	 * @param string|authUser|authGroup $childEntity
	 * @param string|authUser|authGroup $parentEntity
	 * @return bool
	 */
	public static function assignTo($childEntity,$parentEntity)
	{
		return self::getEntity($childEntity)->assignTo(self::getEntity($parentEntity));
	}

	/**
	 * This method will remove the childEntity from the parentEntity
	 * @static
	 * @param string|authUser|authGroup $childEntity
	 * @param string|authUser|authGroup $parentEntity
	 * @return bool
	 */
	public static function removeFrom($childEntity,$parentEntity)
	{
		return self::getEntity($childEntity)->removeFrom(self::getEntity($parentEntity));
	}

	/**
	 * Grant a permission (allow or deny) for a given authEntity to a given authObject
	 * @static
	 * @param $object
	 * @param $authEntity
	 * @param string|array $permissionName
	 * @param string $permissionOrigin
	 * @param bool $inheritable
	 * @param string $policy
	 * @return bool
	 */
	public static function grant($object,$authEntity,$permissionName,$permissionOrigin=NULL,$inheritable=TRUE,$policy='allow')
	{
		if(is_array($permissionName)){
			$authCommon = new parent();
			$authCommon->db->transBegin($authCommon->tblAuthorizations);
			foreach($permissionName as $permission){
				$permName = $permission[0];
				$permObj  = isset($permission[1]) ? $permission[1] : auth::GLOBAL_PERMISSION;
				if(!self::getObject($object)->grant($authEntity,$permName,$permObj,$inheritable,$policy)){
					$authCommon->db->transRollback();
					$authCommon->db->transEnd();
					return FALSE;
				}
			}
			$authCommon->db->transCommit();
			$authCommon->db->transEnd();
			return TRUE;
		}else{
			if(!isset($permissionOrigin)) $permissionOrigin = auth::GLOBAL_PERMISSION;
			return self::getObject($object)->grant($authEntity,$permissionName,$permissionOrigin,$inheritable,$policy);
		}
	}

	/**
	 * Revoke a permission (allow or deny) for a given authEntity from a given authObject
	 * @static
	 * @param $object
	 * @param $authEntity
	 * @param string|array $permissionName
	 * @param string $permissionOrigin
	 * @param string $policy
	 * @return bool
	 */
	public static function revoke($object,$authEntity,$permissionName,$permissionOrigin=NULL,$policy='allow')
	{
		if(is_array($permissionName)){
			$authCommon = new parent();
			$authCommon->db->transBegin($authCommon->tblAuthorizations);
			foreach($permissionName as $permission){
				$permName = $permission[0];
				$permObj  = isset($permission[1]) ? $permission[1] : auth::GLOBAL_PERMISSION;
				if(!self::getObject($object)->revoke($authEntity,$permName,$permObj,$policy)){
					$authCommon->db->transRollback();
					$authCommon->db->transEnd();
					return FALSE;
				}
			}
			$authCommon->db->transCommit();
			$authCommon->db->transEnd();
			return TRUE;
		}else{
			if(!isset($permissionOrigin)) $permissionOrigin = auth::GLOBAL_PERMISSION;
			return self::getObject($object)->revoke($authEntity,$permissionName,$permissionOrigin,$policy);
		}
	}

	/**
	 * Checks if the given entity has authorization to the given object and permission
	 * @static
	 * @param $entity
	 * @param $object
	 * @param $permissionName
	 * @param $permissionObject
	 * @return bool
	 */
	public static function isAllowed($entity,$object,$permissionName=NULL,$permissionObject=NULL)
	{
		$entity = self::getEntity($entity);
		return $entity->isAllowed($object,$permissionName,$permissionObject);
	}
}
class authCommon{
	public $dbName='Engine_CMS';
	public $tblUsers='auth_users';
	public $tblGroups='auth_groups';
	public $tblPermissions='auth_permissions';
	public $tblAuthorizations='auth_authorizations';
	public $tblObjects='auth_objects';
	public $tblUsers2Groups='auth_users_groups';
	public $tblGroups2Groups='auth_groups_groups';
	/**
	 * @var engineDB
	 */
	protected $db;

	protected function __construct()
	{
		
		$engine = EngineAPI::singleton();

		if(!$this->db = $engine->openDB){; //getPrivateVar('engineDB')){
			errorHandle::newError(__METHOD__ . '() - Cannot get to the engineDB!', errorHandle::CRITICAL);
		}

		if(array_key_exists('userAuth',$engineVars)){
			if(array_key_exists('dbName',enginevars::get("userAuth")))            $this->dbName            = enginevars::get("userAuth")['dbName'];
			if(array_key_exists('tblUsers',enginevars::get("userAuth")))          $this->tblUsers          = enginevars::get("userAuth")['tblUsers'];
			if(array_key_exists('tblGroups',enginevars::get("userAuth")))         $this->tblGroups         = enginevars::get("userAuth")['tblGroups'];
			if(array_key_exists('tblPermissions',enginevars::get("userAuth")))    $this->tblPermissions    = enginevars::get("userAuth")['tblPermissions'];
			if(array_key_exists('tblAuthorizations',enginevars::get("userAuth"))) $this->tblAuthorizations = enginevars::get("userAuth")['tblAuthorizations'];
			if(array_key_exists('tblUsers2Groups',enginevars::get("userAuth")))   $this->tblUsers2Groups   = enginevars::get("userAuth")['tblUsers2Groups'];
			if(array_key_exists('tblGroups2Groups',enginevars::get("userAuth")))  $this->tblGroups2Groups  = enginevars::get("userAuth")['tblGroups2Groups'];
		}
	}

	/**
	 * Gets all the children of a given object
	 * @param $id
	 * @param bool $returnObject
	 *             True to return a fully instantiated authObject
	 *             Else return a arrow with all the object row's fields
	 * @return authObject[]
	 */
	protected function getChildren($id, $returnObject=TRUE)
	{
		$result = array();
		$dbChildren = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `parent`='%s'",
			$this->db->escape($this->tblObjects),
			$this->db->escape($id)));
		while($row = mysql_fetch_assoc($dbChildren['result'])){
			$result[] = ($returnObject) ? auth::getObject($row['ID']) : $row;
		}
		return $result;
	}

	/**
	 * Returns the parent object of the given object id
	 * @param $id
	 * @param bool $returnObject
	 * @return authObject
	 */
	protected function getParent($id, $returnObject=TRUE)
	{
		$dbParent = $this->db->query(sprintf("SELECT `A`.* FROM `%s` AS `A` LEFT JOIN `%s` AS `B` ON `B`.`parent`=`A`.`ID` WHERE `B`.`ID`='%s'",
			$this->db->escape($this->tblObjects),
			$this->db->escape($this->tblObjects),
			$this->db->escape($id)));
		if($dbParent['numRows']){
			$parent = mysql_fetch_assoc($dbParent['result']);
			return ($returnObject) ? auth::getObject($parent['ID']) : $parent;
		}else{
			return NULL;
		}
	}

	protected function authUUID(){
		return md5(uniqid('', TRUE));
	}
}
class authObject extends authCommon{
	const ALLOW='allow';
	const DENY='deny';
	private $objectID;
	private $metaData=array();
	public $autoPropagate=TRUE;

	public function __construct($objectID)
	{
		parent::__construct();
		$this->objectID = auth::formatName($objectID);

		// Get all my meta Data
		$dbObject = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `ID`='%s' LIMIT 1",
			$this->db->escape($this->tblObjects),
			$this->db->escape($this->objectID)));
		if($dbObject['numRows']){
			$row = mysql_fetch_assoc($dbObject['result']);
			foreach($row as $field => $value){
				$this->metaData[$field] = $value;
			}
			if($this->metaData['metaData']) $this->metaData['metaData'] = unserialize($this->metaData['metaData']);
		}else{
			errorHandle::newError(__METHOD__."() - Object '{$this->objectID}' hasn't been registered yet!", errorHandle::CRITICAL);
		}
	}

	/**
	 * [Magic Method] Returns the object ID when this object is used in a string context
	 * @return string
	 */
	public function __toString()
	{
		return $this->objectID;
	}

	/**
	 * Retrieves the requested MetaData
	 * @param string $name
	 * @return string|array
	 */
	public function getMetaData($name=NULL)
	{
		if(is_null($name)){
			return $this->metaData;
		}elseif(isset($this->metaData[$name])){
			return $this->metaData[$name];
		}elseif(isset($this->metaData['metaData'][$name])){
			return $this->metaData['metaData'][$name];
		}else{
			return NULL;
		}
	}

	/**
	 * Returns the parent object to this object
	 * @return authObject|null
	 */
	public function getParent()
	{
		if($this->getMetaData('parent')){
			return auth::getObject($this->getMetaData('parent'));
		}else{
			return NULL;
		}
	}

	/**
	 * Returns the children of this object
	 * @return authObject[]
	 */
	public function getChildren()
	{
		$children = array();
		$dbObjects = $this->db->query(sprintf("SELECT `ID` FROM `%s` WHERE `parent`='%s'", $this->db->escape($this->tblObjects), $this->db->escape($this->getMetaData('ID'))));
		while($row = mysql_fetch_assoc($dbObjects['result'])){
			$children[] = auth::getObject($row['ID']);
		}
		return $children;
	}

	/**
	 * Retrieves a list of all the authorizations for this object
	 * @var string $type
	 *      This controls what kind of authorizations are returned. Valid values include:
	 *       + all - will return ALL authorizations [default]
	 *       + local - will return ONLY the locally defined authorizations
	 *       + inherited - will return ONLY the inherited authorizations
	 * @return array
	 */
	public function listAuthorizations($type='all')
	{
		$authorizations = array();
		$sql = sprintf("SELECT `a`.*, `p`.`name` AS `permissionName`, `p`.`description` AS `permissionDesc`, `p`.`ID` AS `permissionID` FROM `%s` AS `a` LEFT JOIN `%s` AS `p` ON `a`.`permissionID`=`p`.`ID` WHERE `authObjectID`='%s'",
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape($this->tblPermissions),
			$this->db->escape("$this"));

		if(strtolower($type) == 'local'){
			$sql .= " AND `inheritedFrom` = ''";
		}elseif(strtolower($type) == 'inherited'){
			$sql .= " AND `inheritedFrom` <> ''";
		}elseif(strtolower($type) == 'all'){
			$sql .= "";
		}else{
			errorHandle::newError(__METHOD__."() - Invalid return type! ('$type')", errorHandle::DEBUG);
			return array();
		}

		$dbAuthorizations = $this->db->query($sql);
		while($row = mysql_fetch_assoc($dbAuthorizations['result'])){
			$authorizations[] = $row;
		}
		return $authorizations;
	}

	/**
	 * This method will clear (wipe) all the inherited authorizations on this object
	 * @return bool
	 */
	private function clearInheritance()
	{
		$dbDelete = $this->db->query(sprintf("DELETE FROM `%s` WHERE `authObjectID`='%s' AND `inheritedFrom` <> ''",
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape($this->getMetaData('ID'))));
		return $dbDelete['errorNumber']==0;
	}

	/**
	 * Updates the state of inheritance this object's authorizations
	 * @param bool $newState
	 * @param bool $applyLocal
	 *        Set to true to apply the inherited auth's locally
	 * @return bool
	 */
	public function setInheritance($newState, $applyLocal=false)
	{
		// Only do something if we're actually changing!
		if($newState == $this->getMetaData('inherits')) return FALSE;

		// Start the database transaction
		$this->db->transBegin();

		// Save the new value to the object's metaData (testing for it to be successful)
		if(!auth::updateObject($this->objectID, array('inherits' => $newState))){
			errorHandle::newError(__METHOD__."() - Failed to save the new inheritance state", errorHandle::DEBUG);
			$this->db->transRollback();
			$this->db->transEnd();
			return FALSE;
		}else{
			if($newState){
				/*
				 * We're turning inheritance ON!
				 * We need to get my parent, and propagate it's permissions down to me
				 */
				if($this->getParent()->propagateInheritance($this->objectID)){
					$this->db->transCommit();
					$this->db->transEnd();
					return TRUE;
				}else{
					errorHandle::newError(__METHOD__."() - Propagate changes failed!", errorHandle::HIGH);
					$this->db->transRollback();
					$this->db->transEnd();
					return FALSE;
				}
			}else{
				/*
				 * We're turning inheritance OFF!
				 * We need to clear all the inherited authorizations from this object
				 * However, if $applyLocal is true, we'll instead move them to local authorizations (instead of just removing them)
				 * Then we'll re-propagate my authorizations down to my children
				 */
				if($applyLocal){
					// Move all my inherited authorizations to local authorizations
					$dbAction = $this->db->query(sprintf("UPDATE `%s` SET `inheritedFrom`='' WHERE `authObjectID`='%s' AND `inheritedFrom`<>''",
						$this->db->escape($this->tblAuthorizations),
						$this->db->escape($this->objectID)));
				}else{
					// Remove all my inherited authorizations
					$dbAction = $this->db->query(sprintf("DELETE FROM `%s` WHERE `authObjectID`='%s' AND `inheritedFrom`<>''",
						$this->db->escape($this->tblAuthorizations),
						$this->db->escape($this->objectID)));
				}

				if($dbAction['errorNumber']){
					errorHandle::newError(__METHOD__."() - Failed to update authorizations in the database! (SQL Error: ".$dbAction['error'].")", errorHandle::CRITICAL);
					$this->db->transRollback();
					$this->db->transEnd();
					return FALSE;
				}else{
					// Now propagate these changes down to my children
					if($this->propagateInheritance()){
						$this->db->transCommit();
						$this->db->transEnd();
						return TRUE;
					}else{
						errorHandle::newError(__METHOD__."() - Propagate changes failed!", errorHandle::HIGH);
						$this->db->transRollback();
						$this->db->transEnd();
						return FALSE;
					}
				}
			}
		}
	}

	/**
	 * @param string $applyTo
	 *        If given, only propagate to the given objects, otherwise ALL children will be targeted
	 *        This is very useful when you have a known child you want to send the inheritance to.
	 * @return bool
	 */
	public function propagateInheritance($applyTo=NULL) {
		// First, we need to start a transaction for safety
		$this->db->transBegin($this->tblAuthorizations);

		// We need to get all this object's authorizations (both inherited and local which are set to inherit)
		$authorizations = array();
		$dbAuthorizations = $this->db->query(sprintf("SELECT a.*, p.name AS `permissionName`, p.object AS `permissionObject` FROM `%s` a LEFT JOIN `%s` p ON a.permissionID=p.ID WHERE `inheritable`='1' AND `authObjectID`='%s'",
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape($this->tblPermissions),
			$this->db->escape($this->getMetaData('ID'))));
		while($row = mysql_fetch_assoc($dbAuthorizations['result'])){
			$authorizations[] = $row;
		}

		// Now loop on all my children
		$children = is_null($applyTo)
			? $this->getChildren()
			: array_map('auth::getObject', explode(',', $applyTo));

		foreach($children as $child){
			// Skip children who won't inherit
			if(!$child->getMetaData('inherits')) continue;
			// Turn off autoPropagation (we'll trigger it manually at the end)
			$child->autoPropagate = FALSE;
			// Clear the child's inherited authorizations
			if(!$child->clearInheritance()){
				errorHandle::newError(__METHOD__."() - An error occurred while clearing an object's inheritance!", errorHandle::DEBUG);
				$this->db->transRollback();
				$this->db->transEnd();
				return FALSE;
			}
			// Now, we loop on each of MY authorizations, and grant them down to the child
			foreach($authorizations as $authorization){
				$inheritedFrom = $authorization['inheritedFrom'] ? $authorization['inheritedFrom'] : $this->getMetaData('ID');
				if(!$child->grant($authorization['authEntity'],$authorization['permissionName'],$authorization['permissionObject'],(bool)$authorization['inheritable'],$authorization['policy'],$inheritedFrom)){
					errorHandle::newError(__METHOD__."() - An error occurred with a grant!", errorHandle::DEBUG);
					$this->db->transRollback();
					$this->db->transEnd();
					return FALSE;
				}
			}
			// Lastly, we manually trigger the propagation for the child
			$child->propagateInheritance();
		}

		// Lastly, we need to commit everything we've done
		$this->db->transCommit();
		$this->db->transEnd();
		return TRUE;
	}

	/**
	 * @param string|authUser|authGroup $entity
	 * @param string|int $permissionName
	 * @param string $permissionOrigin
	 * @param bool $inheritable
	 * @param string $policy
	 * @param string $inheritedFrom
	 * @return bool
	 */
	public function grant($entity,$permissionName,$permissionOrigin=auth::GLOBAL_PERMISSION,$inheritable=TRUE,$policy='allow',$inheritedFrom='')
	{
		// Get to the authEntity and the permissionID
		$permissionID = is_numeric($permissionName) ? $permissionName : auth::lookupPermission($permissionName,$permissionOrigin);
		$entity       = auth::getEntity($entity);

		// Check for an existing authorization
		$dbAuthorizationCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `authEntity`='%s' AND `permissionID`='%s' AND `policy`='%s' AND `authObjectID`='%s'",
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape("$entity"),
			$this->db->escape($permissionID),
			$this->db->escape($policy),
			$this->db->escape($this->objectID)));
		if(mysql_result($dbAuthorizationCheck['result'], 0, 'i')){
			errorHandle::newError(__METHOD__."() - Authorization already exists - move along now. (Entity: $entity PermissionID: $permissionID Policy: $policy ObjectID: {$this->objectID})", errorHandle::DEBUG);
			return TRUE;
		}else{
			// Add the authorization
			$dbAddAuthorization = $this->db->query(sprintf("INSERT INTO `%s` (`ID`,`authEntity`,`permissionID`,`policy`,`authObjectID`,`inheritable`,`inheritedFrom`) VALUES('%s','%s','%s','%s','%s','%s','%s')",
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape($this->authUUID()),
				$this->db->escape("$entity"),
				$this->db->escape($permissionID),
				$this->db->escape($policy),
				$this->db->escape($this->objectID),
				$this->db->escape((int)$inheritable),
				$this->db->escape($inheritedFrom)));
			if($dbAddAuthorization['errorNumber']){
				errorHandle::newError(__METHOD__.sprintf("() - SQL Error! (%s:%s)",$dbAddAuthorization['errorNumber'],$dbAddAuthorization['error']), errorHandle::MEDIUM);
				return FALSE;
			}
		}

		// Lastly, we need to propagate these changes down the object's tree
		if($this->autoPropagate && $inheritable === TRUE){
			if($this->propagateInheritance()){
				$this->db->transCommit();
				$this->db->transEnd();
				return TRUE;
			}else{
				$this->db->transRollback();
				$this->db->transEnd();
				return FALSE;
			}
		}else{
			return TRUE;
		}
	}

	/**
	 * @param $entity
	 * @param $permissionName
	 * @param string $permissionOrigin
	 * @param string $policy
	 * @return bool
	 */
	public function revoke($entity,$permissionName,$permissionOrigin=auth::GLOBAL_PERMISSION,$policy='allow')
	{
		// Get to the authEntity
		$entity = auth::getEntity($entity);

		if($permissionName == '*'){
			// Remove ALL the entities' (local) authorizations
			$dbAllAuths = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `authEntity`='%s' AND `authObjectID`='%s' AND `inheritedFrom` = ''",
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape("$entity"),
				$this->db->escape($this->objectID)));

			// Since we'll doing alot of looping, start a transaction
			$this->db->transBegin($this->tblAuthorizations);
			// Disable autoPropaget (we'll manualy trigger it at the end)
			$this->autoPropagate = FALSE;
			// For each authorization, remove it
			while($row = mysql_fetch_assoc($dbAllAuths['result'])){
				$this->revoke($entity,$row['permissionID']);
			}
			// Manually trigger propagation
			$this->propagateInheritance();
			$this->autoPropagate = TRUE;
			// Lastly, commit and end the transaction
			$this->db->transCommit();
			$this->db->transEnd();
		}else{
			// Get to the permissionID
			$permissionID = is_numeric($permissionName) ? $permissionName : auth::lookupPermission($permissionName,$permissionOrigin);
			// Lookup the existing authorization
			$dbAuthorizationLookup = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `authEntity`='%s' AND `permissionID`='%s' AND `authObjectID`='%s' LIMIT 1",
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape("$entity"),
				$this->db->escape($permissionID),
				$this->db->escape($this->objectID)));

			if(!$dbAuthorizationLookup['numRows']){
				errorHandle::newError(__METHOD__."() - Can't locate authorization!", errorHandle::MEDIUM);
				return FALSE;
			}

			// Is this a local permission?
			$authRow = mysql_fetch_assoc($dbAuthorizationLookup['result']);
			if(!$authRow['inheritedFrom']){
				// Yes - We just need to remove this authorization
				$dbRevoke = $this->db->query(sprintf("DELETE FROM `%s` WHERE `ID`='%s' LIMIT 1",
					$this->db->escape($this->tblAuthorizations),
					$this->db->escape($authRow['ID'])));
			}else{
				/*
				 * No - This will get pretty complicated. We need to;
				 *       1) Change the inheritance state of this object to NOT inherit (applying everything locally)
				 *       2) Revoke the authorization from this object
				 *       3) Propagate the changes down the tree
				 */
				if($this->setInheritance(FALSE,TRUE)){
					return $this->revoke($entity,$permissionID,$policy);
				}else{
					errorHandle::newError(__METHOD__."() - Revoke failed!", errorHandle::HIGH);
					return FALSE;
				}
			}

			// Lastly, we need to propagate these changes down the object's tree
			if($this->autoPropagate){
				return $this->propagateInheritance();
			}else{
				return TRUE;
			}
		}
	}
}
class authEntity extends authCommon implements ArrayAccess{
	const TYPE_USER = 'User';
	const TYPE_GROUP = 'Group';

	protected $entityType;
	protected $authSearchTrees = array();
	protected $metaData = array();
	protected $expanded = false;
	/**
	 * @var authEntity[]
	 */
	protected $members = array();
	/**
	 * @var authEntity[]
	 */
	protected $membersExtended = array();
	/**
	 * @var authEntity[]
	 */
	protected $memberOf = array();
	/**
	 * @var authEntity[]
	 */
	protected $memberOfExtended = array();

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns the value of the entityType property (This serves to ID what kind of entity this is)
	 * @return string
	 */
	public function getType()
	{
		return $this->entityType;
	}

	/**
	 * Returns true if this is a User
	 * @return bool
	 */
	public function isUser()
	{
		return $this->getType() == self::TYPE_USER;
	}

	/**
	 * Returns true if this is a User Group
	 * @return bool
	 */
	public function isGroup()
	{
		return $this->getType() == self::TYPE_GROUP;
	}

	/**
	 * Retrieves the requested MetaData
	 * @param string $name
	 * @return string
	 */
	public function getMetaData($name)
	{
		return (isset($this->metaData[$name])) ?$this->metaData[$name] : NULL;
	}

	/**
	 * Assign this entity to the passed entity
	 * @param string|authGroup|authUser $targetEntity
	 * @param bool $ldapAssignment
	 * @return bool
	 */
	public function assignTo($targetEntity,$ldapAssignment=NULL)
	{
		$targetEntity = auth::getEntity($targetEntity);

		// Check for a user target (which is an illegal action)
		if($targetEntity->getType() == authEntity::TYPE_USER){
			errorHandle::newError(__METHOD__."() - Illegal action! (You can't assign an entity to a user!)", errorHandle::HIGH);
			return FALSE;
		}
		// Check for a circular assignment (assigning a group to itself)
		if("$this" == "$targetEntity"){
			errorHandle::newError(__METHOD__."() - Illegal action! (You can't assign a group to itself!)", errorHandle::HIGH);
			return FALSE;
		}

		// Okay, we now have the type and the id of the target, now we just need to assign THIS entity to the target
		switch($this->getType()){
			case authEntity::TYPE_USER:
				$dbAssignmentCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `user`='%s' AND `group`='%s'",
					$this->db->escape($this->tblUsers2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				if(mysql_result($dbAssignmentCheck['result'], 0, 'i')){
					errorHandle::newError(__METHOD__."() - Assignment already exists.", errorHandle::DEBUG);
					return TRUE;
				}else{
					$sql = (is_bool($ldapAssignment))
						? sprintf("INSERT INTO `%s` (`user`,`group`,`ldapAssignment`) VALUES('%s','%s','%s')",
							$this->db->escape($this->tblUsers2Groups),
							$this->db->escape($this->getMetaData('ID')),
							$this->db->escape($targetEntity->getMetaData('ID')),
							$this->db->escape( (int)$ldapAssignment ))
						: sprintf("INSERT INTO `%s` (`user`,`group`) VALUES('%s','%s')",
							$this->db->escape($this->tblUsers2Groups),
							$this->db->escape($this->getMetaData('ID')),
							$this->db->escape($targetEntity->getMetaData('ID')));
					$dbAssignment = $this->db->query($sql);
				}
				break;

			case authEntity::TYPE_GROUP:
				$dbAssignmentCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `childGroup`='%s' AND `parentGroup`='%s'",
					$this->db->escape($this->tblGroups2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				if(mysql_result($dbAssignmentCheck['result'], 0, 'i')){
					errorHandle::newError(__METHOD__."() - Assignment already exists.", errorHandle::DEBUG);
					return TRUE;
				}else{
					$dbAssignment = $this->db->query(sprintf("INSERT INTO `%s` (`childGroup`,`parentGroup`) VALUES('%s','%s')",
						$this->db->escape($this->tblGroups2Groups),
						$this->db->escape($this->getMetaData('ID')),
						$this->db->escape($targetEntity->getMetaData('ID'))));
				}
				break;

			default:
				errorHandle::newError(__METHOD__."() - Fetal Error - Unknown entity type for 'this'!", errorHandle::HIGH);
				return FALSE;
		}

		// Okay, if we've gotten here, then we did try to assign THIS entity to the target entity. We need to check the result and return back to the user.
		if(isset($dbAssignment) and !$dbAssignment['errorNumber']){
			return TRUE;
		}else{
			if(!isset($dbAssignment)){
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (There's no sql result!)", errorHandle::HIGH);
			}else{
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (SQL Error: ".$dbAssignment['error'].")", errorHandle::HIGH);
			}
			return FALSE;
		}
	}

	/**
	 * Remove this entity from the passed entity
	 * @param string|authUser|authGroup $targetEntity
	 * @return bool
	 */
	public function removeFrom($targetEntity)
	{
		$targetEntity = auth::getEntity($targetEntity);

		// Check for a user target (which is an illegal action)
		if($targetEntity->getType() == authEntity::TYPE_USER){
			errorHandle::newError(__METHOD__."() - Illegal action! (You can't remove an entity from a user!)", errorHandle::HIGH);
			return FALSE;
		}
		// Check for a circular removal (doubt this would ever happen)
		if("$this" == "$targetEntity"){
			errorHandle::newError(__METHOD__."() - Illegal action! (You can't remove a group from itself!)", errorHandle::HIGH);
			return FALSE;
		}

		// Okay, we now have the type and the id of the target, now we just need to assign THIS entity to the target
		switch($this->getType()){
			case authEntity::TYPE_USER:
				$dbAssignmentDelete = $this->db->query(sprintf("DELETE FROM `%s` WHERE `user`='%s' AND `group`='%s'",
					$this->db->escape($this->tblUsers2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				break;

			case authEntity::TYPE_GROUP:
				$dbAssignmentDelete = $this->db->query(sprintf("DELETE FROM `%s` WHERE `childGroup`='%s' AND `parentGroup`='%s'",
					$this->db->escape($this->tblGroups2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				break;

			default:
				errorHandle::newError(__METHOD__."() - Fetal Error - Unknown entity type for 'this'!", errorHandle::HIGH);
				return FALSE;
		}

		// Okay, if we've gotten here, then we did try to assign THIS entity to the target entity. We need to check the result and return back to the user.
		if(isset($dbAssignmentDelete) and !$dbAssignmentDelete['errorNumber']){
			return TRUE;
		}else{
			if(!isset($dbAssignmentDelete)){
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (There's no sql result!)", errorHandle::HIGH);
			}else{
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (SQL Error: ".$dbAssignmentDelete['error'].")", errorHandle::HIGH);
			}
			return FALSE;
		}
	}

	/**
	 * Retrieves an array of entities which are members of this entity
	 * @param bool $recursive
	 * @return authEntity[]
	 */
	public function getMembers($recursive=FALSE)
	{
		$this->expandTree();
		if($recursive) $this->__getMembers();
		return array_unique(array_merge($this->members, $this->membersExtended));
	}

	/**
	 * Recursive helper for getMembers()
	 * @return authEntity[]
	 */
	private function __getMembers()
	{
		if(!sizeof($this->membersExtended)){
			foreach($this->members as $member){
				$this->membersExtended = $member->getMembers(TRUE);
			}
		}
		return $this->membersExtended;
	}

	/**
	 * Retrieves a CSV list of entity IDs for entities which are members of this entity
	 * @param bool $recursive
	 * @param bool $forSQL
	 * @return string
	 */
	public function getMembersList($recursive=FALSE,$forSQL=FALSE)
	{
		$result = array();
		$members = $this->getMembers($recursive);
		foreach($members as $member){
			$result[] = $forSQL ? sprintf("'%s'", $this->db->escape("$member")) : "$member";
		}
		return implode(',',$result);
	}

	/**
	 * Retrieves an array of entities which this entity is a member of
	 * @param bool $recursive
	 * @return array
	 */
	public function getMemberOf($recursive=FALSE)
	{
		$this->expandTree();
		if($recursive) $this->__getMemberOf();
		return array_unique(array_merge($this->memberOf, $this->memberOfExtended));
	}

	/**
	 * Recursive helper for getMemberOf()
	 * @return authEntity[]
	 */
	private function __getMemberOf()
	{
		if(!sizeof($this->memberOfExtended)){
			foreach($this->memberOf as $memberOf){
				$this->memberOfExtended = $memberOf->getMemberOf(TRUE);
			}
		}
		return $this->memberOfExtended;
	}

	/**
	 * Retrieves a CSV list of entity IDs for entities which this entity is a member of
	 * @param bool $recursive
	 * @param bool $forSQL
	 * @return string
	 */
	public function getMemberOfList($recursive=FALSE,$forSQL=FALSE)
	{
		$result = array();
		$memberOf = $this->getMemberOf($recursive);
		foreach($memberOf as $memberOfMember){
			$result[] = $forSQL ? sprintf("'%s'", $this->db->escape("$memberOfMember")) : "$memberOfMember";
		}
		return implode(',',$result);
	}

	/**
	 * This is the meat of the whole auth library.
	 * This method will do all the work to check weather or not a user is allowed to access a given object
	 *
	 * @todo Algorithm may need work
	 * I feel the algorithm may need some work with how it handles User Groups, namely;
	 *  + User Group auth conflicts - What should happen if 'Group A' is ALLOWED, and 'Group B' is DENIED
	 *
	 * @param $object
	 * @param $permissionName
	 * @param $permissionObject
	 * @return bool
	 */
	public function isAllowed($object,$permissionName=NULL,$permissionObject=NULL)
	{
		// Set the default response to FALSE (deny)
		$isAllowed = FALSE;
		// Get the object we're checking
		$object = auth::getObject($object);

		// Get a list of all the groups which may have permissions (all groups of is entity)
		$memberOf = $this->getMemberOfList(TRUE, TRUE);
		if(!$memberOf) $memberOf = "''";

		// Get the authSearchTree for this auth check
		if(!isset($this->authSearchTrees["$object"])){
			$this->authSearchTrees["$object"] = array();
			$sql = sprintf("SELECT a.*, p.ID AS permissionID, p.object AS permissionObject, p.name AS permissionName FROM %s AS a LEFT JOIN %s AS p ON a.permissionID=p.ID WHERE authObjectID='%s' AND (authEntity IN (%s) OR authEntity='%s')",
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape($this->tblPermissions),
				$this->db->escape("$object"),
				$memberOf, "$this");
			$dbAuthorizations = $this->db->query($sql);
			if(!$dbAuthorizations['error']){
				while($row = mysql_fetch_assoc($dbAuthorizations['result'])){
					$this->authSearchTrees["$object"][ $row['authEntity'] ][] = $row;
				}
			}else{
				errorHandle::newError(__METHOD__."() - Failed to get auth search tree! (SQL Error: ".$dbAuthorizations['error']." | SQL: $sql)", errorHandle::DEBUG);
			}
		}
		$searchTree = $this->authSearchTrees["$object"];

		/*
		 * !! THIS SORT OPERATION IS CRITICAL !!
		 * This will put the user's authorizations at the END of the searchTree
		 * (This is due to the face that the groups are under keys like 'gid:#' and will sort before the user under 'uid:#')
		 */
		ksort($searchTree);

		// Are we looking for a specific permissions, or a general "something" permission
		if(isset($permissionName)){
			// Yes - Okay, this will be a detailed permission check
			if(is_null($permissionObject)) $permissionObject = auth::GLOBAL_PERMISSION;
			foreach($searchTree as $entityID => $authorizations){
				foreach($authorizations as $authorization){
					if($authorization['permissionName'] == auth::formatName($permissionName) and $authorization['permissionObject'] == auth::formatName($permissionObject)){
						$isAllowed = $authorization['policy'] == 'allow' ? TRUE : FALSE;
					}
				}
			}
		}else{
			// No - Okay, we only care if the user has "something" for this object
			$isAllowed = sizeof($searchTree) > 0;
		}

		// Log this check to the debug log
		$isAllowedTxt = ($isAllowed) ? 'Granted' : 'Denied';
		errorHandle::newError(__METHOD__."() - Access $isAllowedTxt to user for $permissionObject:$permissionName on $object", errorHandle::DEBUG);

		// And return the final response
		return $isAllowed;
	}

	/**
	 * [ArrayAccess interface] Whether a offset exists
	 * @param $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return !($this->getMetaData($offset) === NULL);
	}
	/**
	 * [ArrayAccess interface] Offset to retrieve
	 * @param $offset
	 * @return string
	 */
	public function offsetGet($offset)
	{
		return $this->getMetaData($offset);
	}
	/**
	 * [ArrayAccess interface] Offset to set
	 * @param $offset
	 * @param $value
	 */
	public function offsetSet($offset, $value)
	{
		errorHandle::newError(__METHOD__."() - Illegal action! (Setting of metadata not allowed!)", errorHandle::MEDIUM);
	}
	/**
	 * [ArrayAccess interface] Offset to unset
	 * @param $offset
	 */
	public function offsetUnset($offset)
	{
		errorHandle::newError(__METHOD__."() - Illegal action! (Unsetting of metadata not allowed!)", errorHandle::MEDIUM);
	}
}
class authUser extends authEntity{
	private $userKey;

	public function __construct($userKey,$autoExpandTree=FALSE)
	{
		parent::__construct();
		$this->entityType = parent::TYPE_USER;
		$this->userKey = $userKey;
		$this->init($autoExpandTree);
	}

	private function init($autoExpandTree){
		// Get the user's key
		$userKey = preg_match(auth::REGEX_ENTITY_USER, $this->userKey, $m) ? $m[1] : $this->userKey;
		$dbUser = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `ID`='%s' OR `username`='%s' LIMIT 1",
			$this->db->escape($this->tblUsers),
			$this->db->escape($userKey),
			$this->db->escape($userKey)));

		if(!$dbUser['numRows']){
			errorHandle::newError(__METHOD__."() - No user found with userKey '$userKey'!", errorHandle::DEBUG);
		}else{
			// Save the meta data
			$this->metaData = mysql_fetch_assoc($dbUser['result']);
		}

		// Do I auto-expand the tree?
		if($autoExpandTree) $this->expandTree();
	}

	public function refresh($autoExpandTree=FALSE){
		$this->metaData = array();
		$this->authSearchTrees = array();
		$this->init($this->expanded || $autoExpandTree);
	}

	/**
	 * [Magic Method] Returns the entityID when this object is used in a string context
	 * @return string
	 */
	public function __toString()
	{
		return 'uid:'.$this->getMetaData('ID');
	}

	/**
	 * Expands the memberOf and members lists
	 * @return void
	 */
	public function expandTree()
	{
		if(!$this->expanded){
			$this->expanded = TRUE;

			// Get the member entities
			// -- (a user can't have any) --

			// Get the memberOf entities
			$dbMemberOf = $this->db->query(sprintf("SELECT `group` FROM `%s` WHERE `user`='%s'",
				$this->db->escape($this->tblUsers2Groups),
				$this->db->escape($this->getMetaData('ID'))));
			if($dbMemberOf['numRows']){
				while($row = mysql_fetch_assoc($dbMemberOf['result'])){
					$this->memberOf[] = auth::getEntity("gid:".$row['group'], TRUE);
				}
			}
		}
	}
}
class authGroup extends authEntity{
	private $groupKey;

	public function __construct($groupKey,$autoExpandTree=FALSE)
	{
		parent::__construct();
		$this->entityType = parent::TYPE_GROUP;
		$this->groupKey = $groupKey;
		$this->init($autoExpandTree);
	}

	private function init($autoExpandTree){
		// Get the group's key
		$groupKey = preg_match(auth::REGEX_ENTITY_GROUP, $this->groupKey, $m) ? $m[1] : $this->groupKey;
		$dbGroup = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `ID`='%s' OR `ldapDN`='%s' LIMIT 1",
			$this->db->escape($this->tblGroups),
			$this->db->escape($groupKey),
			$this->db->escape($groupKey)));
		if(!$dbGroup['numRows']){
			errorHandle::newError(__METHOD__."() - No group found with groupKey '$groupKey'!", errorHandle::DEBUG);
		}else{
			// Save the metadata
			$this->metaData = mysql_fetch_assoc($dbGroup['result']);
		}

		// Do I auto-expand the tree?
		if($autoExpandTree) $this->expandTree();
	}

	public function refresh($autoExpandTree=FALSE){
		$this->metaData = array();
		$this->authSearchTrees = array();
		$this->init($this->expanded || $autoExpandTree);
	}

	/**
	 * [Magic Method] Returns the entityID when this object is used in a string context
	 * @return string
	 */
	public function __toString()
	{
		return 'gid:'.$this->getMetaData('ID');
	}

	/**
	 * Allows updating this group's metadata (name, ldapDN, etc)
	 * @param array $newMetadata
	 *        Key -> Value pairs for the new data
	 */
	public function edit($newMetadata){
		$protectedFields = array('ID');
		$changedFields = array();
		foreach($newMetadata as $field => $value){
			if(in_array($field, $protectedFields)){
				errorHandle::newError(__METHOD__."() - Illegal action! (Attempt to change protected field!)", errorHandle::HIGH);
			}else{
				if($this->getMetaData($field) != $value){
					$changedFields[$field] = sprintf("`%s`='%s'", $this->db->escape($field), $this->db->escape($value));
				}
			}
		}
		if(sizeof($changedFields)){
			$sql = sprintf("UPDATE `%s` SET %s WHERE `ID`='%s' LIMIT 1",
				$this->db->escape($this->tblGroups),
				implode(',', $changedFields),
				$this->db->escape($this->getMetaData('ID')));
			$dbUpdate = $this->db->query($sql);
			if(!$dbUpdate['numRows']){
				errorHandle::newError(__METHOD__."() - Failed to update group! (SQL Error: ".$dbUpdate['error'].")", errorHandle::DEBUG);
			}else{
				// Save the new metadata
				foreach(array_keys($changedFields) as $field){
					$this->metaData[$field] = $newMetadata[$field];
				}
			}
		}
	}

	/**
	 * Expands the memberOf and members lists
	 * @return void
	 */
	public function expandTree()
	{
		if(!$this->expanded){
			$this->expanded = TRUE;

			// Get the member entities
			$dbMembers = $this->db->query(sprintf("(SELECT 'group' AS `entityType`,`childGroup` AS `ID` FROM `%s` WHERE `parentGroup`='%s') UNION (SELECT 'user' AS `entityType`,`user` AS `ID` FROM `%s` WHERE `group`='%s')",
				$this->db->escape($this->tblGroups2Groups),
				$this->db->escape($this->getMetaData('ID')),
				$this->db->escape($this->tblUsers2Groups),
				$this->db->escape($this->getMetaData('ID'))));
			if($dbMembers['numRows']){
				while($row = mysql_fetch_assoc($dbMembers['result'])){
					$objID = ($row['entityType'] == 'group') ? 'gid:'.$row['ID'] : 'uid:'.$row['ID'];
					$this->members[] = auth::getEntity($objID, TRUE);
				}
			}
			// Get the memberOf entities
			$dbMemberOf = $this->db->query(sprintf("SELECT `parentGroup` FROM `%s` WHERE `childGroup`='%s'",
				$this->db->escape($this->tblGroups2Groups),
				$this->db->escape($this->getMetaData('ID'))));
			if($dbMemberOf['numRows']){
				while($row = mysql_fetch_assoc($dbMemberOf['result'])){
					$this->memberOf[] = auth::getEntity("gid:".$row['parentGroup'], TRUE);
				}
			}
		}
	}
}