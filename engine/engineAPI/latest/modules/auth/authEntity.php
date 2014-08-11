<?php
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
				$dbAssignmentCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE `user`='%s' AND `group`='%s'",
					$this->db->escape($this->dbName),
					$this->db->escape($this->tblUsers2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				if($dbAssignmentCheck->fetchField()){
					errorHandle::newError(__METHOD__."() - Assignment already exists.", errorHandle::DEBUG);
					return TRUE;
				}else{
					$sql = (is_bool($ldapAssignment))
						? sprintf("INSERT INTO `%s`.`%s` (`user`,`group`,`ldapAssignment`) VALUES('%s','%s','%s')",
							$this->db->escape($this->dbName),
							$this->db->escape($this->tblUsers2Groups),
							$this->db->escape($this->getMetaData('ID')),
							$this->db->escape($targetEntity->getMetaData('ID')),
							$this->db->escape( (int)$ldapAssignment ))
						: sprintf("INSERT INTO `%s`.`%s` (`user`,`group`) VALUES('%s','%s')",
							$this->db->escape($this->tblUsers2Groups),
							$this->db->escape($this->dbName),
							$this->db->escape($this->getMetaData('ID')),
							$this->db->escape($targetEntity->getMetaData('ID')));
					$dbAssignment = $this->db->query($sql);
				}
				break;

			case authEntity::TYPE_GROUP:
				$dbAssignmentCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE `childGroup`='%s' AND `parentGroup`='%s'",
					$this->db->escape($this->dbName),
					$this->db->escape($this->tblGroups2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				if($dbAssignmentCheck->fetchField()){
					errorHandle::newError(__METHOD__."() - Assignment already exists.", errorHandle::DEBUG);
					return TRUE;
				}else{
					$dbAssignment = $this->db->query(sprintf("INSERT INTO `%s`.`%s` (`childGroup`,`parentGroup`) VALUES('%s','%s')",
						$this->db->escape($this->dbName),
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
		if(isset($dbAssignment) and !$dbAssignment->errorCode()){
			return TRUE;
		}else{
			if(!isset($dbAssignment)){
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (There's no sql result!)", errorHandle::HIGH);
			}else{
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (SQL Error: ".$dbAssignment->errorMsg().")", errorHandle::HIGH);
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
				$dbAssignmentDelete = $this->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `user`='%s' AND `group`='%s'",
					$this->db->escape($this->dbName),
					$this->db->escape($this->tblUsers2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				break;

			case authEntity::TYPE_GROUP:
				$dbAssignmentDelete = $this->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `childGroup`='%s' AND `parentGroup`='%s'",
					$this->db->escape($this->dbName),
					$this->db->escape($this->tblGroups2Groups),
					$this->db->escape($this->getMetaData('ID')),
					$this->db->escape($targetEntity->getMetaData('ID'))));
				break;

			default:
				errorHandle::newError(__METHOD__."() - Fetal Error - Unknown entity type for 'this'!", errorHandle::HIGH);
				return FALSE;
		}

		// Okay, if we've gotten here, then we did try to assign THIS entity to the target entity. We need to check the result and return back to the user.
		if(isset($dbAssignmentDelete) and !$dbAssignmentDelete->errorCode()){
			return TRUE;
		}else{
			if(!isset($dbAssignmentDelete)){
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (There's no sql result!)", errorHandle::HIGH);
			}else{
				errorHandle::newError(__METHOD__."() - Assignment SQL Error! (SQL Error: ".$dbAssignmentDelete->errorMsg().")", errorHandle::HIGH);
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
			if(!$dbAuthorizations->errorMsg()){
				while($row = $dbAuthorizations->fetch()){
					$this->authSearchTrees["$object"][ $row['authEntity'] ][] = $row;
				}
			}else{
				errorHandle::newError(__METHOD__."() - Failed to get auth search tree! (SQL Error: ".$dbAuthorizations->errorMsg()." | SQL: $sql)", errorHandle::DEBUG);
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
?>