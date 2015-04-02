<?php

// Bring in the other parts of auth
require_once __DIR__.'/authCommon.php';
require_once __DIR__.'/authUser.php';
require_once __DIR__.'/authGroup.php';
require_once __DIR__.'/authEntity.php';
require_once __DIR__.'/authObject.php';

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
		$dbObjCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE `ID`='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblObjects),
			$authCommon->db->escape($id)));
		if($dbObjCheck->fetchField()){
			errorHandle::newError(__METHOD__."() - Object already exists!", errorHandle::DEBUG);
			return FALSE;
		}else{
			// If a parent was passed that dosen't exist we need to fail
			if(isset($parent) and !$ignoreMissingParents){
				$dbObjCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE `ID`='%s'",
					$authCommon->db->escape($authCommon->dbName),
					$authCommon->db->escape($authCommon->tblObjects),
					$authCommon->db->escape($parent)));
				if(!$dbObjCheck->fetchField()){
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

			$dbObjRegister = $authCommon->db->query(sprintf("INSERT INTO `%s`.`%s` (%s) VALUES(%s)",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblObjects),
				implode(',', $dbFields),
				implode(',', $dbValues)));
			if($dbObjRegister->error()){
				errorHandle::newError(__METHOD__."() - SQL Error! (".$dbObjRegister->errorMsg().")", errorHandle::DEBUG);
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
		$authCommon->db->beginTransaction();

		// Delete all the authorizations this object has
		$dbAuthDelete = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `authObjectID`='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape($id)));

		// Delete the actual auth object
		$dbObjDelete = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblObjects),
			$authCommon->db->escape($id)));

		if(!$dbAuthDelete->error() and !$dbObjDelete->error()){
			// Get all the children, and kill them too :) (while listening for an error)
			$children = $authCommon->getChildren($id,FALSE);
			foreach($children as $child){
				$fn = __FUNCTION__;
				if(!self::$fn($child['ID'])){
					// An error occurred in a child!
					errorHandle::newError(__METHOD__."() - Error encountered with child '".$child['ID']."'", errorHandle::DEBUG);
					$authCommon->db->rollback();
				}
			}

			// If we're here, we're done
			$authCommon->db->commit();
			return TRUE;
		}else{
			if($dbAuthDelete->error()) errorHandle::newError(__METHOD__."() - Failed to remove authorizations for object '$id'. (SQL Error: ".$dbAuthDelete->errorMsg().")", errorHandle::DEBUG);
			if($dbObjDelete->error())  errorHandle::newError(__METHOD__."() - Failed to remove object '$id'. (SQL Error: ".$dbObjDelete->errorMsg().")", errorHandle::DEBUG);
			$authCommon->db->rollback();
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
		$dbObjUpdate = $authCommon->db->query(sprintf("UPDATE `%s`.`%s` SET %s WHERE `ID`='%s' LIMIT 1",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblObjects),
			implode(',', $dbFields),
			$authCommon->db->escape($object)));

		if(!$dbObjUpdate->error()){
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
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbObjUpdate->errorMsg().")", errorHandle::DEBUG);
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

		$sql = sprintf("SELECT * FROM `%s`.`%s`",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblGroups)
		);
		if(isset($orderBy)) $sql .= " ORDER BY $orderBy";
		$dbGroups = $authCommon->db->query($sql);
		while($row = $dbGroups->fetch()){
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
		$sql = sprintf("SELECT * FROM `%s`.`%s` WHERE ID NOT IN (SELECT `childGroup` FROM `%s`)",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblGroups2Groups)
		);
		if(isset($orderBy)) $sql .= " ORDER BY $orderBy";
		$dbGroups = $authCommon->db->query($sql);
		while($row = $dbGroups->fetch()){
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

		$sql = sprintf("SELECT %s FROM `%s`.`%s` WHERE `ldapDN`='%s' LIMIT 1",
			$fieldsMySQL,
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblGroups),
			$authCommon->db->escape($dn)
		);
		$dbGroup = $authCommon->db->query($sql);
		if(!$dbGroup->rowCount()){
			return NULL;
		}else{
			if(sizeof($fields) > 1 or $fields[0] == '*'){
				return $dbGroup->fetch();
			}else{
				return $dbGroup->fetchField($fields[0]);
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
				$dbGroupID = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s`.`%s` WHERE `ldapDN`='%s' LIMIT 1",
					$authCommon->db->escape($authCommon->dbName),
					$authCommon->db->escape($authCommon->tblGroups),
					$authCommon->db->escape($groupKey)));
				if(!$dbGroupID->rowCount()){
					// errorHandle::newError(__METHOD__."() - Cannot find a group for ldapDN '$groupKey'!", errorHandle::DEBUG);
					return NULL;
				}else{
					$groupKey = $dbGroupID->fetchField();
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
			$dbGroup = $authCommon->db->query(sprintf("SELECT %s FROM `%s`.`%s` WHERE `ID`='%s' OR `ldapDN`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblGroups),
				$authCommon->db->escape($groupKey),
				$authCommon->db->escape($groupKey)));

			// Return the final array of fields
			return $dbGroup->rowCount() ? $dbGroup->fetch() : array();
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
		$dbNewGroup = $authCommon->db->query(sprintf("INSERT INTO `%s`.`%s` (`name`,`description`,`ldapDN`) VALUES('%s','%s',%s)",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblGroups),
			$authCommon->db->escape($name),
			isset($desc)   ? $authCommon->db->escape($desc) : '',
			isset($ldapDN) ? sprintf("'%s'", $authCommon->db->escape($ldapDN)) : "NULL"));

		if(!$dbNewGroup->error()){
			return self::getEntity("gid:".(int)$dbNewGroup['id']);
		}else{
			errorHandle::newError(__METHOD__.sprintf("() - SQL Error! (%s:%s)", $dbNewGroup->errorCode(), $dbNewGroup->errorMsg()), errorHandle::MEDIUM);
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
		$authCommon->db->beginTransaction();

		// Delete all authorizations for this group
		$dbDelete1 = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `authEntity`='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape("$groupEntity")));

		// Delete all group->group memberships
		$dbDelete2 = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `childGroup`='%s' OR `parentGroup`='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblGroups2Groups),
			$authCommon->db->escape($groupEntity->getMetaData('ID')),
			$authCommon->db->escape($groupEntity->getMetaData('ID'))));

		// Delete all user->group memberships
		$dbDelete3 = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `group`='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblUsers2Groups),
			$authCommon->db->escape($groupEntity->getMetaData('ID'))));

		// Now we can delete the actual group
		$dbDelete4 = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblGroups),
			$authCommon->db->escape($groupEntity->getMetaData('ID'))));

		if(!$dbDelete1->error() and !$dbDelete2->error() and !$dbDelete3->error() and !$dbDelete4->error()){
			$authCommon->db->commit();
			return TRUE;
		}else{
			$authCommon->db->rollback();
			if($dbDelete1->error()) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete1->errorCode(),$dbDelete1->errorMsg()), errorHandle::HIGH);
			if($dbDelete2->error()) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete2->errorCode(),$dbDelete2->errorMsg()), errorHandle::HIGH);
			if($dbDelete3->error()) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete3->errorCode(),$dbDelete3->errorMsg()), errorHandle::HIGH);
			if($dbDelete4->error()) errorHandle::newError(__METHOD__.sprintf(" SQL Error! (%s:%s)",$dbDelete4->errorCode(),$dbDelete4->errorMsg()), errorHandle::HIGH);
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

		$sql = sprintf("SELECT * FROM `%s`.`%s`",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblUsers)
		);
		if(isset($orderBy)) $sql .= " ORDER BY $orderBy";

		$dbUsers = $authCommon->db->query($sql);
		while($row = $dbUsers->fetch()){
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
				$dbGroupID = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s`.`%s` WHERE `username`='%s' LIMIT 1",
					$authCommon->db->escape($authCommon->dbName),
					$authCommon->db->escape($authCommon->tblGroups),
					$authCommon->db->escape($userKey)));
				if(!$dbGroupID->rowCount()){
					errorHandle::newError(__METHOD__."() - Cannot find user for username '$userKey'!", errorHandle::DEBUG);
					return NULL;
				}else{
					$userKey = $dbGroupID->fetchField();
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
			$dbGroup = $authCommon->db->query(sprintf("SELECT %s FROM `%s`.`%s` WHERE `ID`='%s' OR `username`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblGroups),
				$authCommon->db->escape($userKey),
				$authCommon->db->escape($userKey)));

			// Return the final array of fields
			return $dbGroup->rowCount() ? $dbGroup->fetch() : array();
		}
	}


	public static function removeUser($userKey)
	{
		$authCommon  = new parent();

		// Get the fields from the database
		$dbObjects = $authCommon->db->query(sprintf("SELECT DISTINCT authObjectID FROM `%s`.`%s` WHERE authEntity='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape($userKey)));
		if(!$dbObjects->errorMsg()){
			while($row = $dbObjects->fetch()){
				auth::revoke($row['authObjectID'], "uid:$userKey", '*');
			}
			return TRUE;
		}else{
			errorHandle::newError(__METHOD__."() - Failed to find user's authorizations (SQL Error: ".$dbObjects->errorMsg().")!", errorHandle::DEBUG);
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
			$dbNameCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE `name`='%s'",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($name)));
		}else{
			// Name must be unique across the object and global spaces
			$dbNameCheck = $authCommon->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE (`object`='%s' OR `object`='%s') AND `name`='%s'",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape(self::GLOBAL_PERMISSION),
				$authCommon->db->escape($object),
				$authCommon->db->escape($name)));
		}
		if($dbNameCheck->fetchField()){
			// We found a name-collision
			errorHandle::newError(__METHOD__."() - A permission already exists with the name '$name'!", errorHandle::DEBUG);
			return FALSE;
		}

		// If we get here then there's no hole available. We need to insert a new permission row (either starting a new permissions line, or adding to the end of one)
		$dbCreatePermission = $authCommon->db->query(sprintf("INSERT INTO `%s`.`%s` (`object`,`name`,`description`,`system`) VALUES('%s','%s','%s','%s')",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblPermissions),
			$authCommon->db->escape(trim($object)),
			$authCommon->db->escape(trim($name)),
			$authCommon->db->escape(trim($desc)),
			$authCommon->db->escape(bool2str($systemPerm, TRUE))));

		if($dbCreatePermission->error()){
			errorHandle::newError(__METHOD__.sprintf("() - SQL Error! (%s:%s)",$dbCreatePermission->errorCode(),$dbCreatePermission->errorMsg()), errorHandle::MEDIUM);
			return FALSE;
		}else{
			return $dbCreatePermission->insertId();
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
			$dbPermissions = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s`.`%s` WHERE `object`='%s'",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($object)));
			while($permission = $dbPermissions->fetch()){
				$permissionIDs[] = $permission['ID'];
			}
		}

		// Okay, we now have a list of the permission IDs we need to delete
		$authCommon->db->beginTransaction();
		foreach($permissionIDs as $permissionID){
			// Remove all the permission's authorizations
			$dbDelete1 = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `permissionID`='%s'",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblAuthorizations),
				$authCommon->db->escape($permissionID))
			);
			// Remove the permission from the registry
			$dbDelete2 = $authCommon->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($permissionID)));

			// And check for errors
			if($dbDelete1->error() or $dbDelete2->error()){
				$authCommon->db->rollback();
				if($dbDelete1->error()) errorHandle::newError(__METHOD__."() - [Delete1] SQL Error: ".$dbDelete1->errorMsg(), errorHandle::DEBUG);
				if($dbDelete2->error()) errorHandle::newError(__METHOD__."() - [Delete2] SQL Error: ".$dbDelete2->errorMsg(), errorHandle::DEBUG);
				return FALSE;
			}
		}

		// No errors? Good, then we can commit the transaction!
		$authCommon->db->commit();
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
			$dbPermission = $authCommon->db->query(sprintf("SELECT %s FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape((int)$name)));

		}else{
			// Lookup by permission name and object
			$dbPermission = $authCommon->db->query(sprintf("SELECT %s FROM `%s`.`%s` WHERE `object`='%s' AND `name`='%s' LIMIT 1",
				implode(',',$sqlFields),
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape(self::formatName($originObject)),
				$authCommon->db->escape(self::formatName($name))));
		}

		if($dbPermission->error()){
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermission->errorCode().":".$dbPermission->errorMsg().")", errorHandle::DEBUG);
			return NULL;
		}elseif($dbPermission->rowCount()){
			$result = $dbPermission->fetch();
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
			$dbPermissionExists = $authCommon->db->query(sprintf("SELECT ID FROM `%s`.`%s` WHERE ID='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape((int)$name)));

		}else{
			// Lookup by permission name and object
			$dbPermissionExists = $authCommon->db->query(sprintf("SELECT ID FROM `%s`.`%s` WHERE `object`='%s' AND `name`='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape(self::formatName($object)),
				$authCommon->db->escape(self::formatName($name))));
		}


		if($dbPermissionExists->errorMsg()){
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermissionExists->errorMsg().")", errorHandle::DEBUG);
			return FALSE;
		}else{
			return $dbPermissionExists->rowCount() != 0;
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
			$dbPermissions = $authCommon->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `object` = '%s' OR `object` = '%s'",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($object),
				$authCommon->db->escape(self::GLOBAL_PERMISSION)));
		}else{
			$dbPermissions = $authCommon->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `object` = '%s'",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($object)));
		}

		while($row = $dbPermissions->fetch()){
			$row['isGlobal'] = ($row['object'] == self::GLOBAL_PERMISSION) ? TRUE : FALSE;
			$permissions[]   = $row;
		}

		return $permissions;
	}


	public static function getPermissionID($name,$object=self::GLOBAL_PERMISSION){
		$authCommon = new parent();
		$key = md5($object."|".$name);
		if(!isset(self::$permissionIdRegistry[$key])){
			$dbPermission = $authCommon->db->query(sprintf("SELECT `ID` FROM `%s`.`%s` WHERE `name`='%s' AND `object`='%s' LIMIT 1",
				$authCommon->db->escape($authCommon->dbName),
				$authCommon->db->escape($authCommon->tblPermissions),
				$authCommon->db->escape($name),
				$authCommon->db->escape($object)));
			if($dbPermission->error()){
				errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermission->errorCode().":".$dbPermission->errorMsg().")", errorHandle::DEBUG);
				return NULL;
			}elseif($dbPermission->rowCount()){
				$result = $dbPermission->fetch();
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
		$dbPermission = $authCommon->db->query(sprintf("SELECT %s FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
			implode(',',$sqlFields),
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblPermissions),
			$authCommon->db->escape($id)));
		if($dbPermission->error()){
			errorHandle::newError(__METHOD__."() - SQL Error! (".$dbPermission->errorCode().":".$dbPermission->errorMsg().")", errorHandle::DEBUG);
			return NULL;
		}elseif($dbPermission->rowCount()){
			$result = $dbPermission->fetch();
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
		$dbAuth = $authCommon->db->query(sprintf("SELECT `a`.*, `p`.`name` AS `permissionName`, `p`.`description` AS `permissionDesc`, `p`.`object` AS `permissionObject`, `p`.`ID` AS `permissionID` FROM `%s`.`%s` AS `a` LEFT JOIN `%s`.`%s` AS `p` ON `a`.`permissionID`=`p`.`ID` WHERE a.`ID`='%s'",
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblAuthorizations),
			$authCommon->db->escape($authCommon->dbName),
			$authCommon->db->escape($authCommon->tblPermissions),
			$authCommon->db->escape($authID)));
		if($dbAuth->rowCount()){
			return $dbAuth->fetch();
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
			$authCommon->db->beginTransaction();
			foreach($permissionName as $permission){
				$permName = $permission[0];
				$permObj  = isset($permission[1]) ? $permission[1] : auth::GLOBAL_PERMISSION;
				if(!self::getObject($object)->grant($authEntity,$permName,$permObj,$inheritable,$policy)){
					$authCommon->db->rollback();
					return FALSE;
				}
			}
			$authCommon->db->commit();
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
			$authCommon->db->beginTransaction();
			foreach($permissionName as $permission){
				$permName = $permission[0];
				$permObj  = isset($permission[1]) ? $permission[1] : auth::GLOBAL_PERMISSION;
				if(!self::getObject($object)->revoke($authEntity,$permName,$permObj,$policy)){
					$authCommon->db->rollback();
					return FALSE;
				}
			}
			$authCommon->db->commit();
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
