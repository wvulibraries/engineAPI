<?php
class ldapSearch {
	
	private $domain       = NULL;
	private $ldapUsername = NULL;
	private $ldapPassword = NULL;
	private $ldapServer   = NULL;
	private $ldapDomain   = NULL;
	private $ldapDN       = NULL;
	private $filter       = NULL;
	private $attributes   = array();
	private $ldapSort     = array();
	private $ldapConn     = NULL;
	
	function __construct($domain) {
		
		$this->domain = $domain;
		
		$this->setVars();
		
		$this->ldapConn = ldap_connect($this->ldapServer);
		
		ldap_set_option($this->ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldapConn, LDAP_OPT_REFERRALS, 0);
		
		@ldap_bind($this->ldapConn, $this->ldapUsername."@".$this->ldapDomain, $this->ldapPassword);
		
	}
	
	function __destruct() {
		ldap_unbind($this->ldapConn);
	}
	
	private function setVars() {
		
		global $engineVars;
		
		$this->ldapUsername = "engineAPI";
		$this->ldapPassword = "Te\$t1234";
		$this->ldapServer   = $engineVars['domains'][$this->domain]['ldapServer'];
		$this->ldapDomain   = $engineVars['domains'][$this->domain]['ldapDomain'];
		$this->ldapDN       = $engineVars['domains'][$this->domain]['dn'];
		
	}
	
	public function addFilter($str) {
		$this->filter = $str;
	}
	
	public function addAttribute($attr) {
		$this->attributes[] = $attr;
	}
	
	public function sortOrder($sortArray) {
		$this->ldapSort = array_reverse($sortArray);
	}
	
	public function search() {
		
		$sr = ldap_search($this->ldapConn, $this->ldapDN, $this->filter, $this->attributes);
		if (!$sr) {
			return "Search Error";
		}
		
		
		foreach ($this->ldapSort as $val) {
			if (in_array($val, $this->attributes)) { // make sure we sort against an existing field
				ldap_sort($this->ldapConn, $sr, $val);
			}
		}
		
		$results = ldap_get_entries($this->ldapConn, $sr);
		
		$this->setVars(); // set back to original
		
		return $results;
				
	}
	
	public function getAllUsers($show="all") {
		
		$txt = NULL;
		if ($show == "active") {
			$txt = "(!(userAccountControl:1.2.840.113556.1.4.803:=2))";
		}
		else if ($show == "disabled") {
			$txt = "(userAccountControl:1.2.840.113556.1.4.803:=2)";
		}
		
		$this->addFilter("(&(objectCategory=person)(objectClass=user)".$txt.")");
		$this->addAttribute("sn");
		$this->addAttribute("givenname");
		$this->addAttribute("cn");
		$this->sortOrder(array("cn","givenname","sn"));
		
		$results = $this->search();
		
		if (!is_array($results)) {
			return "No results found.";
		}
		else {
			$return = array();
			foreach ($results as $result) {
				$return[] = $result['cn'][0];
			}
			
			return $return;
		}
		
	}
	
	public function getAllGroups($scope="global",$type="security") {
		
		$filter = NULL;
		
		if ($scope == "global" && $type == "security") {
			$filter = "(&(objectcategory=group)(groupType=-2147483646))";
		}
		else if ($scope == "global" && $type == "distribution") {
			$filter = "(&(objectcategory=group)(groupType=2))";
		}
		else if ($scope == "universal" && $type == "security") {
			$filter = "(&(objectcategory=group)(groupType=-2147483640))";
		}
		else if ($scope == "universal" && $type == "distribution") {
			$filter = "(&(objectcategory=group)(groupType=8))";
		}
		else if ($scope == "local" && $type == "security") {
			$filter = "(&(objectcategory=group)(groupType=-2147483644))";
		}
		else if ($scope == "local" && $type == "distribution") {
			$filter = "(&(objectcategory=group)(groupType=4))";
		}
		else {
			$filter = "(&(objectcategory=group))";
		}
		
		$this->addFilter($filter);
		$this->addAttribute("cn");
		$this->sortOrder(array("cn"));
		
		$results = $this->search();
		
		if (!is_array($results)) {
			return "No results found.";
		}
		else {
			$return = array();
			foreach ($results as $result) {
				$return[] = $result['cn'][0];
			}
			
			return $return;
		}
		
	}
	
	public function getAllOUs() {
		
		$this->addFilter("(&(objectcategory=organizationalUnit))");
		$this->addAttribute("ou");
		$this->sortOrder(array("ou"));
		
		$results = $this->search();
		
		if (!is_array($results)) {
			return "No results found.";
		}
		else {
			$return = array();
			foreach ($results as $result) {
				$return[] = $result['ou'][0];
			}
			
			return $return;
		}
		
	}
	
	public function getUsersInOU($ou,$show="all") {
		$this->ldapDN = "OU=".$ou.",".$this->ldapDN;
		return $this->getAllUsers($show);
	}
	
	public function getUsersInContainer($cn,$show="all") {
		$this->ldapDN = "CN=".$cn.",".$this->ldapDN;
		return $this->getAllUsers($show);
	}
	
	public function getUsersInGroup($grp,$show="all") {
		
		$txt = NULL;
		if ($show == "active") {
			$txt = "(!(userAccountControl:1.2.840.113556.1.4.803:=2))";
		}
		else if ($show == "disabled") {
			$txt = "(userAccountControl:1.2.840.113556.1.4.803:=2)";
		}
		
		$this->addFilter("(&(objectCategory=person)(objectClass=user)".$txt.")");
		$this->addAttribute("sn");
		$this->addAttribute("givenname");
		$this->addAttribute("memberof");
		$this->addAttribute("cn");
		$this->sortOrder(array("cn","givenname","sn"));
		
		$results = $this->search();
		
		if (!is_array($results)) {
			return "No results found.";
		}
		else {
			$return = array();
			foreach ($results as $result) {
				if (isset($result['memberof'])) {
					foreach ($result['memberof'] as $group) {
						if (strpos($group,$grp)) {
							$return[] = $result['cn'][0];
							break;
						}
					}
				}
			}
			
			return $return;
		}
		
	}
	
	public function getGroupsInOU($ou,$scope="global",$type="security") {
		$this->ldapDN = "OU=".$ou.",".$this->ldapDN;
		return $this->getAllGroups($scope,$type);
	}
	
	public function getGroupsInContainer($cn,$scope="global",$type="security") {
		$this->ldapDN = "CN=".$cn.",".$this->ldapDN;
		return $this->getAllGroups($scope,$type);
	}
	
	public function getGroupsFromUsername($username) {
		
		$this->addFilter("(&(samaccountname=".$username."))");
		$this->addAttribute("memberof");
		$this->sortOrder(array("memberof"));
		
		$results = $this->search();
		
		if (!is_array($results)) {
			print "No results found.";
		}
		else {
			foreach ($results as $result) {
				if (isset($result['memberof'])) {
					foreach ($result['memberof'] as $group) {
						$regex = '/^CN=(.+?)\,/';
						preg_match($regex,$group,$matches);
						
						if (isset($matches[1])) {
							$return[] = $matches[1];
						}
					}
				}
			}
			
			natcasesort($return);
			return $return;
		}
	}
	
	public function getOUsInOU($ou) {
		$this->ldapDN = "OU=".$ou.",".$this->ldapDN;
		return $this->getAllOUs();
	}
	
	public function getOUsInContainer($cn) {
		$this->ldapDN = "CN=".$cn.",".$this->ldapDN;
		return $this->getAllOUs();
	}
	
}
?>
