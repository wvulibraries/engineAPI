<?php
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
		$sql = sprintf("SELECT * FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblObjects),
			$this->db->escape($this->objectID));
		$dbObject = $this->db->query($sql);
		if($dbObject->rowCount()){
			$row = $dbObject->fetch();
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
		$sql = sprintf("SELECT `ID` FROM `%s`.`%s` WHERE `parent`='%s'",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblObjects),
			$this->db->escape($this->getMetaData('ID')));
		$dbObjects = $this->db->query($sql);
		while($row = $dbObjects->fetch()){
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
		$sql = sprintf("SELECT `a`.*, `p`.`name` AS `permissionName`, `p`.`description` AS `permissionDesc`, `p`.`ID` AS `permissionID` FROM `%s`.`%s` AS `a` LEFT JOIN `%s`.`%s` AS `p` ON `a`.`permissionID`=`p`.`ID` WHERE `authObjectID`='%s'",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape($this->dbName),
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
		while($row = $dbAuthorizations->fetch()){
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
		$dbDelete = $this->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `authObjectID`='%s' AND `inheritedFrom` <> ''",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape($this->getMetaData('ID'))));
		return !$dbDelete->error();
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
		$this->db->beginTransaction();

		// Save the new value to the object's metaData (testing for it to be successful)
		if(!auth::updateObject($this->objectID, array('inherits' => $newState))){
			errorHandle::newError(__METHOD__."() - Failed to save the new inheritance state", errorHandle::DEBUG);
			$this->db->rollback();
			return FALSE;
		}else{
			if($newState){
				/*
				 * We're turning inheritance ON!
				 * We need to get my parent, and propagate it's permissions down to me
				 */
				if($this->getParent()->propagateInheritance($this->objectID)){
					$this->db->commit();
					return TRUE;
				}else{
					errorHandle::newError(__METHOD__."() - Propagate changes failed!", errorHandle::HIGH);
					$this->db->rollback();
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
					$dbAction = $this->db->query(sprintf("UPDATE `%s`.`%s` SET `inheritedFrom`='' WHERE `authObjectID`='%s' AND `inheritedFrom`<>''",
						$this->db->escape($this->dbName),
						$this->db->escape($this->tblAuthorizations),
						$this->db->escape($this->objectID)));
				}else{
					// Remove all my inherited authorizations
					$dbAction = $this->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `authObjectID`='%s' AND `inheritedFrom`<>''",
						$this->db->escape($this->dbName),
						$this->db->escape($this->tblAuthorizations),
						$this->db->escape($this->objectID)));
				}

				if($dbAction->error()){
					errorHandle::newError(__METHOD__."() - Failed to update authorizations in the database! (SQL Error: ".$dbAction->errorMsg().")", errorHandle::CRITICAL);
					$this->db->rollback();
					return FALSE;
				}else{
					// Now propagate these changes down to my children
					if($this->propagateInheritance()){
						$this->db->commit();
						return TRUE;
					}else{
						errorHandle::newError(__METHOD__."() - Propagate changes failed!", errorHandle::HIGH);
						$this->db->rollback();
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
		$this->db->beginTransaction();

		// We need to get all this object's authorizations (both inherited and local which are set to inherit)
		$authorizations = array();
		$dbAuthorizations = $this->db->query(sprintf("SELECT a.*, p.name AS `permissionName`, p.object AS `permissionObject` FROM `%s`.`%s` a LEFT JOIN `%s`.`%s` p ON a.permissionID=p.ID WHERE `inheritable`='1' AND `authObjectID`='%s'",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblPermissions),
			$this->db->escape($this->getMetaData('ID'))));
		while($row = $dbAuthorizations->fetch()){
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
				$this->db->rollback();
				return FALSE;
			}
			// Now, we loop on each of MY authorizations, and grant them down to the child
			foreach($authorizations as $authorization){
				$inheritedFrom = $authorization['inheritedFrom'] ? $authorization['inheritedFrom'] : $this->getMetaData('ID');
				if(!$child->grant($authorization['authEntity'],$authorization['permissionName'],$authorization['permissionObject'],(bool)$authorization['inheritable'],$authorization['policy'],$inheritedFrom)){
					errorHandle::newError(__METHOD__."() - An error occurred with a grant!", errorHandle::DEBUG);
					$this->db->rollback();
					return FALSE;
				}
			}
			// Lastly, we manually trigger the propagation for the child
			$child->propagateInheritance();
		}

		// Lastly, we need to commit everything we've done
		$this->db->commit();
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
		$dbAuthorizationCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s`.`%s` WHERE `authEntity`='%s' AND `permissionID`='%s' AND `policy`='%s' AND `authObjectID`='%s'",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblAuthorizations),
			$this->db->escape("$entity"),
			$this->db->escape($permissionID),
			$this->db->escape($policy),
			$this->db->escape($this->objectID)));
		if($dbAuthorizationCheck->fetchField()){
			errorHandle::newError(__METHOD__."() - Authorization already exists - move along now. (Entity: $entity PermissionID: $permissionID Policy: $policy ObjectID: {$this->objectID})", errorHandle::DEBUG);
			return TRUE;
		}else{
			// Add the authorization
			$dbAddAuthorization = $this->db->query(sprintf("INSERT INTO `%s`.`%s` (`ID`,`authEntity`,`permissionID`,`policy`,`authObjectID`,`inheritable`,`inheritedFrom`) VALUES('%s','%s','%s','%s','%s','%s','%s')",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape($this->authUUID()),
				$this->db->escape("$entity"),
				$this->db->escape($permissionID),
				$this->db->escape($policy),
				$this->db->escape($this->objectID),
				$this->db->escape((int)$inheritable),
				$this->db->escape($inheritedFrom)));
			if($dbAddAuthorization->error()){
				errorHandle::newError(__METHOD__.sprintf("() - SQL Error! (%s:%s)",$dbAddAuthorization->errorCode(),$dbAddAuthorization->errorMsg()), errorHandle::MEDIUM);
				return FALSE;
			}
		}

		// Lastly, we need to propagate these changes down the object's tree
		if($this->autoPropagate && $inheritable === TRUE){
			if($this->propagateInheritance()){
				$this->db->commit();
				return TRUE;
			}else{
				$this->db->rollback();
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
			$dbAllAuths = $this->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `authEntity`='%s' AND `authObjectID`='%s' AND `inheritedFrom` = ''",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape("$entity"),
				$this->db->escape($this->objectID)));

			// Since we'll doing alot of looping, start a transaction
			$this->db->beginTransaction();
			// Disable autoPropaget (we'll manualy trigger it at the end)
			$this->autoPropagate = FALSE;
			// For each authorization, remove it
			while($row = $dbAllAuths->fetch()){
				$this->revoke($entity,$row['permissionID']);
			}
			// Manually trigger propagation
			$this->propagateInheritance();
			$this->autoPropagate = TRUE;
			// Lastly, commit and end the transaction
			$this->db->commit();
		}else{
			// Get to the permissionID
			$permissionID = is_numeric($permissionName) ? $permissionName : auth::lookupPermission($permissionName,$permissionOrigin);
			// Lookup the existing authorization
			$dbAuthorizationLookup = $this->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `authEntity`='%s' AND `permissionID`='%s' AND `authObjectID`='%s' LIMIT 1",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblAuthorizations),
				$this->db->escape("$entity"),
				$this->db->escape($permissionID),
				$this->db->escape($this->objectID)));

			if(!$dbAuthorizationLookup->rowCount()){
				errorHandle::newError(__METHOD__."() - Can't locate authorization!", errorHandle::MEDIUM);
				return FALSE;
			}

			// Is this a local permission?
			$authRow = $dbAuthorizationLookup->fetch();
			if(!$authRow['inheritedFrom']){
				// Yes - We just need to remove this authorization
				$dbRevoke = $this->db->query(sprintf("DELETE FROM `%s`.`%s` WHERE `ID`='%s' LIMIT 1",
					$this->db->escape($this->dbName),
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

?>