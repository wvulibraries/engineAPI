<?php
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
		$dbUser = $this->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `ID`='%s' OR `username`='%s' LIMIT 1",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblUsers),
			$this->db->escape($userKey),
			$this->db->escape($userKey)));

		if(!$dbUser->rowCount()){
			errorHandle::newError(__METHOD__."() - No user found with userKey '$userKey'!", errorHandle::DEBUG);
		}else{
			// Save the meta data
			$this->metaData = $dbUser->fetch();
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
			$dbMemberOf = $this->db->query(sprintf("SELECT `group` FROM `%s`.`%s` WHERE `user`='%s'",
				$this->db->escape($this->dbName),
				$this->db->escape($this->tblUsers2Groups),
				$this->db->escape($this->getMetaData('ID'))));
			if($dbMemberOf->rowCount()){
				while($row = $dbMemberOf->fetch()){
					$this->memberOf[] = auth::getEntity("gid:".$row['group'], TRUE);
				}
			}
		}
	}
}
?>