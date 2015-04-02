<?php
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
		$dbGroup = $this->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `ID`='%s' OR `ldapDN`='%s' LIMIT 1",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblGroups),
			$this->db->escape($groupKey),
			$this->db->escape($groupKey)));
		if(!$dbGroup->rowCount()){
			errorHandle::newError(__METHOD__."() - No group found with groupKey '$groupKey'!", errorHandle::DEBUG);
		}else{
			// Save the metadata
			$this->metaData = $dbGroup->fetch();
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
			$sql = sprintf("UPDATE `%s`.`%s` SET %s WHERE `ID`='%s' LIMIT 1",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblGroups),
				implode(',', $changedFields),
				$this->db->escape($this->getMetaData('ID')));
			$dbUpdate = $this->db->query($sql);
			if(!$dbUpdate->rowCount()){
				errorHandle::newError(__METHOD__."() - Failed to update group! (SQL Error: ".$dbUpdate->errorMsg().")", errorHandle::DEBUG);
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
			$dbMembers = $this->db->query(sprintf("(SELECT 'group' AS `entityType`,`childGroup` AS `ID` FROM `%s`.`%s` WHERE `parentGroup`='%s') UNION (SELECT 'user' AS `entityType`,`user` AS `ID` FROM `%s`.`%s` WHERE `group`='%s')",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblGroups2Groups),
				$this->db->escape($this->getMetaData('ID')),
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblUsers2Groups),
				$this->db->escape($this->getMetaData('ID'))));
			if($dbMembers->rowCount()){
				while($row = $dbMembers->fetch()){
					$objID = ($row['entityType'] == 'group') ? 'gid:'.$row['ID'] : 'uid:'.$row['ID'];
					$this->members[] = auth::getEntity($objID, TRUE);
				}
			}
			// Get the memberOf entities
			$dbMemberOf = $this->db->query(sprintf("SELECT `parentGroup` FROM `%s`.`%s` WHERE `childGroup`='%s'",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblGroups2Groups),
				$this->db->escape($this->getMetaData('ID'))));
			if($dbMemberOf->rowCount()){
				while($row = $dbMemberOf->fetch()){
					$this->memberOf[] = auth::getEntity("gid:".$row['parentGroup'], TRUE);
				}
			}
		}
	}
}
?>