<?php
class authCommon{
	public $dbName='EngineAPI';
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

	protected function __construct() {
		$engine     = EngineAPI::singleton();
		$engineVars = enginevars::getInstance();

		if (!$this->db = db::get(EngineAPI::DB_CONNECTION)) {
			errorHandle::newError(__METHOD__.'() - Cannot get to the engineDB! ('.EngineAPI::DB_CONNECTION.')', errorHandle::CRITICAL);
		}

		$userAuth = $engineVars->get('userAuth');

		if (!is_empty($userAuth)) {
			if (array_key_exists('dbName', $userAuth)) {
				$this->dbName = $userAuth['dbName'];
			}
			if (array_key_exists('tblUsers', $userAuth)) {
				$this->tblUsers = $userAuth['tblUsers'];
			}
			if (array_key_exists('tblGroups', $userAuth)) {
				$this->tblGroups = $userAuth['tblGroups'];
			}
			if (array_key_exists('tblPermissions', $userAuth)) {
				$this->tblPermissions = $userAuth['tblPermissions'];
			}
			if (array_key_exists('tblAuthorizations', $userAuth)) {
				$this->tblAuthorizations = $userAuth['tblAuthorizations'];
			}
			if (array_key_exists('tblUsers2Groups', $userAuth)) {
				$this->tblUsers2Groups = $userAuth['tblUsers2Groups'];
			}
			if (array_key_exists('tblGroups2Groups', $userAuth)) {
				$this->tblGroups2Groups = $userAuth['tblGroups2Groups'];
			}
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
	protected function getChildren($id, $returnObject=TRUE) {
		$result = array();
		$dbChildren = $this->db->query(sprintf("SELECT * FROM `%s`.`%s` WHERE `parent`='%s'",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblObjects),
			$this->db->escape($id)));
		while ($row = $dbChildren->fetch()) {
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
	protected function getParent($id, $returnObject=TRUE) {
		$dbParent = $this->db->query(sprintf("SELECT `A`.* FROM `%s`.`%s` AS `A` LEFT JOIN `%s`.`%s` AS `B` ON `B`.`parent`=`A`.`ID` WHERE `B`.`ID`='%s'",
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblObjects),
			$this->db->escape($this->dbName),
			$this->db->escape($this->tblObjects),
			$this->db->escape($id)));
		if($dbParent->rowCount()){
			$parent = $dbParent->fetch();
			return ($returnObject) ? auth::getObject($parent['ID']) : $parent;
		}else{
			return NULL;
		}
	}

	protected function authUUID() {
		return md5(uniqid('', TRUE));
	}
}
?>
