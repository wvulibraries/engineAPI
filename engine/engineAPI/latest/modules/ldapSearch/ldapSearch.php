<?php
/**
 * EngineAPI ldapSearch module
 * @package EngineAPI\modules\ldapSearch
 */
class ldapSearch
{
    /**
     * LDAP user account types (bitwise)
     * @see $this->getAllUsers()
     */
    const USER_ACTIVE = 1;
    const USER_INACTIVE = 2;
    /**
     * LDAP user group types (bitwise)
     * @see $this->getAllGroups()
     */
    const GROUP_GLOBAL_SECURITY=1;
    const GROUP_GLOBAL_DISTRIBUTION=2;
    const GROUP_UNIVERSAL_SECURITY=4;
    const GROUP_UNIVERSAL_DISTRIBUTION=8;
    const GROUP_LOCAL_SECURITY=16;
    const GROUP_LOCAL_DISTRIBUTION=32;

    /**
     * LDAP connection resource
     * @var resource
     */
    private $ldap;
    /**
     * The LDAP server
     * @var string
     */
    private $ldapServer;
    /**
     * The LDAP server port
     * @var int
     */
    private $ldapServerPort;
    /**
     * The LDAP domain name
     * @var string
     */
    private $ldapDomain;
    /**
     * The LDAP bind username
     * @var string
     */
    private $bindUsername;
    /**
     * The LDAP bing password
     * @var string
     */
    private $bindPassword;
    /**
     * The DN to be used as a base for all searches
     * @var string
     */
    private $baseDN;

    private $enginevars;

    /**
     * Class Constructor
     * @param string $configKey
     *        This can either be the name of an engineVars LDAP array, or a fully qualified LDAP URL
     */
    public function __construct($configKey=NULL) {

        $this->set_enginevars(enginevars::getInstance());

        $configKey = trim($configKey);
        if (isnull($configKey) || is_empty($configKey)) return;

        // We need to figure out of the configKey is just an LDAP URL, or if its a configKey
        $urlInfo = parse_url($configKey);
        if (isset($urlInfo['scheme'])) {
            $this->ldapServer = sprintf('%s://%s', $urlInfo['scheme'], $urlInfo['host']);
            if(isset($urlInfo['port'])) $this->ldapServerPort = $urlInfo['port'];
        }
        else {

            $ldapDomain = $this->enginevars->get("ldapDomain");

            if (array_key_exists($configKey, $ldapDomain)) {
                
                $ldapDomain = $this->enginevars->get("ldapDomain");
                foreach($ldapDomain[ $configKey ] as $key => $value) {
                    $this->$key = $value;
                }

            }
            else {
                errorHandle::newError(__METHOD__."() - Domain missing from config: ".$configKey, errorHandle::DEBUG);
            }

        }

    }

    /**
     * Class Destructor
     */
    public function __destruct() {
        return $this->disconnect();
    }

    public function set_enginevars($enginevars) {
        $this->enginevars = $enginevars;
    }

    /**
     * Connect, and bind, to an LDAP server
	 *
     * @param array $params
     * @return null|resource
     */
    public function &connect($params=array())
    {
        if(is_array($params)){
            if(array_key_exists('ldapServer', $params))     $this->ldapServer     = $params['ldapServer'];
            if(array_key_exists('ldapServerPort', $params)) $this->ldapServerPort = $params['ldapServerPort'];
            if(array_key_exists('ldapDomain', $params))     $this->ldapDomain     = $params['ldapDomain'];
            if(array_key_exists('bindUsername', $params))   $this->bindUsername   = $params['bindUsername'];
            if(array_key_exists('bindPassword', $params))   $this->bindPassword   = $params['bindPassword'];
            if(array_key_exists('baseDN', $params))         $this->baseDN         = $params['baseDN'];
        }
        $ldapConnection = ldap_connect($this->ldapServer, $this->ldapServerPort);
        if($ldapConnection === FALSE){
            errorHandle::newError(__METHOD__.'() - Failed to open LDAP connection. '.ldap_errno($ldapConnection).':'.ldap_error($ldapConnection), errorHandle::HIGH);
            return NULL;
        }else{
            ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
            return $ldapConnection;
        }
    }

    /**
     * Disconnect from the LDAP server
	 *
     * @return bool The result of ldap_unbind()
     */
    public function disconnect()
    {
        if(!$this->logout()){
            return FALSE;
        }else{
            $this->ldap = NULL;
            return TRUE;
        }
    }

    /**
     * Login (bind) to the LDAP server
	 *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username,$password)
    {
        $username = trim($username);
        $password = trim($password);
        $bindRDN  = (isset($username))
                ? (($this->ldapDomain)
                        ? $username."@".$this->ldapDomain
                        : $username)
                : NULL;

        // If we're not connected, fix that.
        if(is_null($this->ldap)) $this->ldap =& $this->connect();
        if($this->ldap){
            return (bool)ldap_bind($this->ldap, $bindRDN, $password);
        }else{
            errorHandle::newError(__METHOD__.'() - No LDAP connection available to bind to.', errorHandle::HIGH);
            return FALSE;
        }
    }

    /**
     * Logoug (unbind) from the LDAP server
     * 
     * @return bool
     */
    public function logout()
    {
        return is_resource($this->ldap) ? ldap_unbind($this->ldap) : TRUE;
    }

    /**
     * Checks a user's credentials against the LDAP server.
     * This is done by creating a new connection to the LDAP server (using the passed credentials to bind to the server)
	 *
     * @param string $username
     * @param string $password
     * @param array $connectParams
     * @return bool
     */
    public function checkCredentials($username,$password,$connectParams=array())
    {
        $username = trim($username);
        $password = trim($password);
        $bindRDN  = (isset($username))
                ? (($this->ldapDomain)
                        ? $username."@".$this->ldapDomain
                        : $username)
                : NULL;

        $ldap =& $this->connect($connectParams);
        $bind = ldap_bind($ldap, $bindRDN, $password);
        if($bind) ldap_unbind($bind);
        return $bind;
    }

    /**
     * Sets the Base DN. Returns the old value
	 *
     * @param string $dn
     * @return string
     */
    public function setBaseDN($dn)
    {
        $oldDN = $this->getBaseDN();
        $this->baseDN = trim($dn);
        return $oldDN;
    }

    /**
     * Returns the Base DN
	 *
     * @return string
     */
    public function getBaseDN()
    {
        return (string)$this->baseDN;
    }

    /**
     * Retrieve a single entry from the LDAP server
	 *
     * @param string $dn
     *        The Distinguished Name of the entry we are retrieving
     *
     * @param array $params
     *        An array of search options
     *          + listAttributes - Only return a list of all the attributes
     *          + attributes     - An array (or csv) of LDAP attributes to be returned ('*' will return ALL attributes)
     *                             Note: the DN (Distinguished Name) will always be returned
     *          + returnRaw      - If this is true, then the raw output from ldap_get_entries() will be returned.
     *                             Otherwise, a cleanly formatted array will be returned
     *
     * @return array
     *         A multidimensional array of attributes for the entries which match the given $filter
     */
    public function getEntry($dn, $params = array())
    {
        $attributes = (array_key_exists('listAttributes', $params) and $params['listAttributes'])
                ? array()
                : ((array_key_exists('attributes', $params))
                        ? ((is_array($params['attributes']))
                                ? $params['attributes']
                                : explode(',', $params['attributes']))
                        : array('dn'));

        // Catch the 'ALL' attributes use-case
        if(in_array('*', $attributes)) $attributes=array();

        // If we're not connected, connect now
        if(!$this->ldap){
            $connParams = (array_key_exists('connection', $params)) ? $params['connection'] : NULL;
            if(!$this->ldap =& $this->connect($connParams)){
                return array();
            }
        }

        // Do the LDAP search
        $ldapSearch = ldap_read($this->ldap,$dn,'objectClass=*',$attributes,(int)(array_key_exists('listAttributes', $params) and $params['listAttributes']),1,0,LDAP_DEREF_ALWAYS);

        if(!$ldapSearch){
            errorHandle::newError(__METHOD__."() - Failed to read ldap entry '$dn'. ".ldap_errno($this->ldap).':'.ldap_error($this->ldap), errorHandle::MEDIUM);
            return array();
        }else{
            $entry = ldap_get_entries($this->ldap, $ldapSearch);
            if(array_key_exists('returnRaw', $params) and $params['returnRaw']){
                // Return the RAW result of ldap_get_entries()
                return $entry;
            }else{
                // Return a cleaned, formatted, results array
                $result = $this->formatSearchResults($entry, $attributes);
                return $result[0];
            }
        }
    }

    /**
     * Perform a recursive LDAP search against the LDAP Server
     *
     * @param string $filter
     *        Can be either an LDAP filer, or semantic string
     *
     * @param array $params
     *        An array of search options
     *          + baseDN         - Temporarily alters the Base DN this search is operating on
     *          + sort           - An array (or csv) of LDAP attributes to sort results by
     *          + sizeLimit      - Enables you to limit the count of entries fetched
     *          + timeLimit      - Sets the number of seconds how long is spend on the search
     *          + attributes     - An array (or csv) of LDAP attributes to be returned ('*' will return ALL attributes)
     *                             Note: the DN (Distinguished Name) will always be returned
     *          + returnRaw      - If this is true, then the raw output from ldap_get_entries() will be returned.
     *                             Otherwise, a cleanly formatted array will be returned
     *
     * @return array
     *         A multidimensional array of attributes for the entries which match the given $filter
     */
    public function searchEntries($filter,$params=array())
    {
        // Process user input parameters
        $baseDN    = (array_key_exists('baseDN', $params)) ? $params['baseDN'] : $this->baseDN;
        $sizeLimit = (array_key_exists('sizeLimit', $params)) ? $params['sizeLimit'] : 0;
        $timeLimit = (array_key_exists('timeLimit', $params)) ? $params['timeLimit'] : 0;
        $attributes = (array_key_exists('listAttributes', $params) and $params['listAttributes'])
                ? array()
                : ((array_key_exists('attributes', $params))
                    ? ((is_array($params['attributes']))
                            ? $params['attributes']
                            : explode(',',$params['attributes']))
                    : array('dn'));
        $sort = (array_key_exists('sort', $params))
                ? (is_array($params['sort']))
                        ? array_reverse($params['sort'])
                        : array_reverse(explode(',',$params['sort']))
                : array('dn');

        // Catch the 'ALL' attributes use-case
        if(in_array('*', $attributes)) $attributes=array();

        // Build the filter string (if needed)
        if(!preg_match('/^\(+([\|&])/', $filter)) $filter = self::buildFilterString($filter);

        // If we're not connected, connect now
        if(!$this->ldap){
            $connParams = (array_key_exists('connection', $params)) ? $params['connection'] : NULL;
            if(!$this->ldap =& $this->connect($connParams)){
                return array();
            }
        }

        // Do the LDAP search
        $ldapSearch = ldap_search($this->ldap, $baseDN, $filter, (array)$attributes, 0, $sizeLimit, $timeLimit, LDAP_DEREF_ALWAYS);
        if(!$ldapSearch){
            errorHandle::newError(__METHOD__."() - Failed to search ldap directory. LDAP Error:".ldap_error($this->ldap), errorHandle::MEDIUM);
            return array();
        }

        // Do we need to sort the results?
        if(isset($sort)){
            foreach ($sort as $sortBy) {
                if (in_array($sortBy, $attributes)) { // make sure we sort against an existing field
                    ldap_sort($this->ldap, $ldapSearch, $sortBy);
                }
            }
        }

        // Are we returning the raw result, or the cleaned 'pretty' version?
        $entries = ldap_get_entries($this->ldap, $ldapSearch);
        if(array_key_exists('returnRaw', $params) and $params['returnRaw']){
            // Return the RAW result of ldap_get_entries()
            return $entries;
        }else{
            // Return a cleaned, formatted, results array
            return $this->formatSearchResults($entries, $attributes);
        }
    }

    /**
     * Perform a non-recursive LDAP search against the LDAP Server
     *
     * @param string $filter
     *        Can be either an LDAP filer, or semantic string
     *
     * @param array $params
     *        An array of search options
     *          + baseDN         - Temporarily alters the Base DN this search is operating on
     *          + sort           - An array (or csv) of LDAP attributes to sort results by
     *          + sizeLimit      - Enables you to limit the count of entries fetched
     *          + timeLimit      - Sets the number of seconds how long is spend on the search
     *          + attributes     - An array (or csv) of LDAP attributes to be returned ('*' will return ALL attributes)
     *                             Note: the DN (Distinguished Name) will always be returned
     *          + returnRaw      - If this is true, then the raw output from ldap_get_entries() will be returned.
     *                             Otherwise, a cleanly formatted array will be returned
     *
     * @return array
     *         A multidimensional array of attributes for the entries which match the given $filter
     */
    public function listEntries($filter,$params=array())
    {
        // Process user input parameters
        $baseDN    = (array_key_exists('baseDN', $params)) ? $params['baseDN'] : $this->baseDN;
        $sizeLimit = (array_key_exists('sizeLimit', $params)) ? $params['sizeLimit'] : 0;
        $timeLimit = (array_key_exists('timeLimit', $params)) ? $params['timeLimit'] : 0;
        $attributes = (array_key_exists('listAttributes', $params) and $params['listAttributes'])
                ? array()
                : ((array_key_exists('attributes', $params))
                    ? ((is_array($params['attributes']))
                            ? $params['attributes']
                            : explode(',',$params['attributes']))
                    : array('dn'));
        $sort = (array_key_exists('sort', $params))
                ? (is_array($params['sort']))
                        ? array_reverse($params['sort'])
                        : array_reverse(explode(',',$params['sort']))
                : array('dn');

        // Catch the 'ALL' attributes use-case
        if(in_array('*', $attributes)) $attributes=array();

        // Build the filter string (if needed)
        if(!preg_match('/^\(+([\|&])/', $filter)) $filter = self::buildFilterString($filter);

        // If we're not connected, connect now
        if(!$this->ldap){
            $connParams = (array_key_exists('connection', $params)) ? $params['connection'] : NULL;
            if(!$this->ldap =& $this->connect($connParams)){
                return array();
            }
        }

        // Do the LDAP search
        $ldapSearch = ldap_list($this->ldap, $baseDN, $filter, (array)$attributes, 0, $sizeLimit, $timeLimit,LDAP_DEREF_ALWAYS);
        if(!$ldapSearch){
            errorHandle::newError(__METHOD__."() - Failed to search ldap directory. ".ldap_errno($this->ldap).':'.ldap_error($this->ldap), errorHandle::MEDIUM);
            return array();
        }

        // Do we need to sort the results?
        if(isset($sort)){
            foreach ($sort as $sortBy) {
                if (in_array($sortBy, $attributes)) { // make sure we sort against an existing field
                    ldap_sort($this->ldap, $ldapSearch, $sortBy);
                }
            }
        }

        // Are we returning the raw result, or the cleaned 'pretty' version?
        $entries = ldap_get_entries($this->ldap, $ldapSearch);
        if(array_key_exists('returnRaw', $params) and $params['returnRaw']){
            // Return the RAW result of ldap_get_entries()
            return $entries;
        }else{
            // Return a cleaned, formatted, results array
            return $this->formatSearchResults($entries, $attributes);
        }
    }

    /**
     * Formats the results of getEntry() and search() and returns a cleaned version
	 *
     * @param array $entries
     *        An array of entries straight from ldap_get_entries()
     * @param array $attributes
     *        An array of attributes to return (passing an empty array will return ALL attributes)
     * @return array
     */
    private function formatSearchResults($entries,$attributes)
    {
        $results = array();
        if($entries['count']){
            foreach($entries as $entry){
                // Skip any pseudo-entries
                if(!is_array($entry)) continue;

                // Always include the DN
                $result = array('dn' => $entry['dn']);

                if(!sizeof($attributes)){
                    // Include ALL attributes
                    $attributes = array_filter(array_keys($entry), 'is_string');
                }

                // Add any additional attributes
                foreach($attributes as $attribute){
                    // Make the attribute case-insensitive
                    $attribute = strtolower(trim($attribute));
                    // Skip this attribute if it dosen't exist in the entry, or if it already exists in the result
                    if(!array_key_exists($attribute, $entry) or array_key_exists($attribute, $result)) continue;

                    switch($entry[$attribute]['count']){
                        case 0:
                            $result[$attribute] = NULL;
                            break;
                        case 1:
                            $result[$attribute] = $entry[$attribute][0];
                            break;
                        default:
                            // More than 1
                            $result[$attribute] = $entry[$attribute];
                            unset($result[$attribute]['count']);
                            break;
                    }

                    // Catch any attribute 'patches' (where we want to make the value more usable)
                    // An example of this would be where the original value is binary
                    switch($attribute){
                        case 'objectsid':
                            if($entry['objectsid'][0]) $result['_objectsid'] = $this->binSIDtoText($entry['objectsid'][0]);
                            break;
                        case 'objectguid':
                            if($entry['objectguid'][0]) $result['_objectguid'] = $this->binGIDtoText($entry['objectguid'][0]);
                            break;
                    }
                }

                // Save this entry to the results
                ksort($result, SORT_LOCALE_STRING);
                $results[] = $result;
            }
        }
        return $results;
    }

    /**
     * Returns all user accounts in LDAP stating at the Base DN
	 *
     * @param int $userType
     * @param array $searchParams
     * @return array
     */
    public function getAllUsers($userType=NULL, $searchParams=NULL)
    {
        // Set default
        if(!isset($userType)) $userType=self::USER_ACTIVE;

        // Build filter string
        $filterGroups = array();
        if($userType & self::USER_ACTIVE)   $filterGroups[] = 'objectClass=user and userAccountControl=512';
        if($userType & self::USER_INACTIVE) $filterGroups[] = 'objectClass=user and userAccountControl=514';

        // Search for all users based on the filter
        if(sizeof($filterGroups)){
            return $this->searchEntries(implode(' or ', $filterGroups), (array)$searchParams);
        }else{
            errorHandle::newError(__METHOD__."() - No user filtering set!", errorHandle::DEBUG);
            return array();
        }
    }

    /**
     * Returns all user groups in LDAP stating at the Base DN
	 *
     * @param int $groupType
     *        Bitwise into of groups to be returned
     *        Default: All security groups
     * @param array $searchParams
     *        Optional search parameters (pass through)
     * @return array
     */
    public function getAllGroups($groupType=NULL, $searchParams=NULL)
    {
        // Set default
        if(!isset($groupType)) $groupType = self::GROUP_GLOBAL_SECURITY | self::GROUP_UNIVERSAL_SECURITY | self::GROUP_LOCAL_SECURITY;

        // Build filter string
        $filterGroups = array();
        if($groupType & self::GROUP_GLOBAL_SECURITY)        $filterGroups[] = '(objectCategory=group and groupType=-2147483646)';
        if($groupType & self::GROUP_GLOBAL_DISTRIBUTION)    $filterGroups[] = '(objectCategory=group and groupType=2)';
        if($groupType & self::GROUP_UNIVERSAL_SECURITY)     $filterGroups[] = '(objectCategory=group and groupType=-2147483640)';
        if($groupType & self::GROUP_UNIVERSAL_DISTRIBUTION) $filterGroups[] = '(objectCategory=group and groupType=8)';
        if($groupType & self::GROUP_LOCAL_SECURITY)         $filterGroups[] = '(objectCategory=group and groupType=-2147483644)';
        if($groupType & self::GROUP_LOCAL_DISTRIBUTION)     $filterGroups[] = '(objectCategory=group and groupType=4)';

        // Search for all groups based on the filter
        if(sizeof($filterGroups)){
            return $this->searchEntries(implode(' or ', $filterGroups), (array)$searchParams);
        }else{
            errorHandle::newError(__METHOD__."() - No group filtering set!", errorHandle::DEBUG);
            return array();
        }
    }

    /**
     * Returns all OUs (Organizational Units) starting at the Base DN
	 *
     * @param array $searchParams
     * @return array
     */
    public function getAllOUs($searchParams=NULL)
    {
        return $this->searchEntries('objectCategory=organizationalUnit', (array)$searchParams);
    }

    /**
     * Returns the requested attributes of a given entry
     *
     * @param string $dn
     *        The DN (Distinguished Name) of the entry you are examining
     * @param mixed $attributes
     *        An array (or CSV) of attributes you wish returned
     * @param array $searchParams
     *        An optional array of search parameters
     * @return array
     */
    public function getAttributes($dn,$attributes,$searchParams=NULL)
    {
        return $this->getEntry($dn, array_merge((array)$searchParams, array(
            'attributes' => $attributes
        )));
    }

    /**
     * Returns a list of all attributes available for this entry
     *
     * @param string $dn
     *        The DN (Distinguished Name) of the entry you are examining
     * @param array $searchParams
     *        An optional array of search parameters
     * @return array
     */
    public function listAttributes($dn,$searchParams=NULL)
    {
        return $this->getEntry($dn, array_merge((array)$searchParams, array(
            'listAttributes' => true
        )));
    }

    /**
     * Search for an OU based on its name. Returns the OU's DN (if it exists)
	 *
     * @param string $ou
     * @param bool $recursive
     * @param array $searchParams
     * @return
     */
    public function findOU($ou,$recursive=true,$searchParams=NULL)
    {
        $searchFn = ($recursive) ? 'searchEntries' : 'listEntries';
        $ou = $this->$searchFn("(&(objectClass=organizationalUnit)(ou=$ou))", (array)$searchParams);
        return $ou[0]['dn'];
    }

    /**
     * Search for a group based on its name. Returns the group's DN (if it exists)
	 *
     * @param string $group
     * @param bool $recursive
     * @param array $searchParams
     * @return bool
     */
    public function findGroup($group,$recursive=true,$searchParams=NULL)
    {
        $searchFn = ($recursive) ? 'searchEntries' : 'listEntries';
        $group = $this->$searchFn("(&(objectClass=group)(name=$group))", (array)$searchParams);
        if(isset($group[0]['dn'])){
            return $group[0]['dn'];
        }
        return FALSE;
    }

    /**
     * Search for a user based on their username. Returns the user's DN (if it exists)
	 *
     * @param string $username
     * @param bool $recursive
     * @param array $searchParams
     * @return bool
     */
    public function findUser($username,$recursive=true,$searchParams=NULL)
    {
        $searchFn = ($recursive) ? 'searchEntries' : 'listEntries';
        $user = $this->$searchFn("(&(objectClass=user)(sAMAccountName=$username))", (array)$searchParams);
 		if(isset($user[0]['dn'])){
			return $user[0]['dn'];
		}
		return FALSE;
    }

    /**
     * Returns the DNs for all the users in a given OU
	 *
     * @param string $ouDN
     *        Parent Organizational Unit DN
     * @param int $userType
     *        User account type(s) to return
     * @param array $searchParams
     *        An optional array of search parameters
     * @see $this->getAllUsers()
     * @return array
     */
    public function getUsersInOU($ouDN,$userType=NULL,$searchParams=NULL)
    {
        if(!$this->isOU($ouDN)){
            errorHandle::newError(__METHOD__."() - Specified OU is not an OU!", errorHandle::DEBUG);
            return array();
        }

        return $this->getAllUsers($userType, array_merge(array('baseDN'=>$ouDN),
            (array)$searchParams
        ));
    }

    /**
     * Returns the DNs for all the users in a given Group
	 *
     * @param string $groupDN
     *        Parent group DN
     * @param int $userType
     *        User account type(s) to return
     * @see $this->getAllUsers()
     * @return array
     */
    public function getUsersInGroup($groupDN,$userType=self::USER_ACTIVE)
    {
        if(!$this->isGroup($groupDN)){
            errorHandle::newError(__METHOD__."() - Specified groupDN is not a group!", errorHandle::DEBUG);
            return array();
        }

        $result=array();
        $entry = $this->getAttributes($groupDN, 'member');
        foreach($entry['member'] as $member){
            $isActive = $this->isActiveUser($member);
            if(isset($userType)){
                if($userType & self::USER_ACTIVE and $isActive)    $result[] = $member;
                if($userType & self::USER_INACTIVE and !$isActive) $result[] = $member;
            }else{
                $result[] = $member;
            }
        }

        return $result;
    }

    /**
     * Returns the DNs for all the groups in a given OU
	 *
     * @param string $ouDN
     *        Parent Organizational Unit DN
     * @param int $groupType
     *        Group type(s) to return
     * @param array $searchParams
     *        An optional array of search parameters
     * @return array
     */
    public function getGroupsInOU($ouDN,$groupType=NULL,$searchParams=NULL)
    {
        if(!$this->isOU($ouDN)){
            errorHandle::newError(__METHOD__."() - Specified OU is not a OU!", errorHandle::DEBUG);
            return array();
        }

        return $this->getAllGroups($groupType, array_merge(array(
            'baseDN'=>$ouDN),
            (array)$searchParams
        ));
    }

    /**
     * Returns the DNs for all the groups in a given group
	 *
     * @param string $groupDN
     *        Parent group DN
     * @param int $groupType
     *        Group type(s) to return
     * @return array
     */
    public function getGroupsInGroup($groupDN,$groupType=NULL)
    {
        if(!$this->isGroup($groupDN)){
            errorHandle::newError(__METHOD__."() - Specified groupDN is not a group!", errorHandle::DEBUG);
            return array();
        }

        $result=array();
        $entry = $this->getAttributes($groupDN, 'member');

        if(is_array($entry['member'])){
            foreach($entry['member'] as $member){
                // Skip all non-groups
                if(!$this->isGroup($member)) continue;
                if(isset($groupType)){
                    if($this->getGroupType($member) & $groupType) $result[] = $member;
                }else{
                    $result[] = $member;
                }
            }
        }else{
            $result = $entry['member'];
        }
        return $result;
    }

    /**
     * Returns the DN of the group which a given group is in (if one exists)
	 *
     * @param string $groupDN
     * @return null
     */
    public function getParentGroups($groupDN)
    {
        $result = $this->searchEntries("(&(objectClass=group)(member=$groupDN))");
        if(sizeof($result)){
            return $result;
        }else{
            return NULL;
        }
    }
    public function getParentOU()
    {

    }

    /**
     * Returns the DNs for all the OUs in a given OU
	 *
     * @param  $ouDN
     * @param null $searchParams
     * @return array
     */
    public function getOUsInOU($ouDN,$searchParams=NULL)
    {
        if(!$this->isOU($ouDN)){
            errorHandle::newError(__METHOD__."() - Specified OU is not an OU!", errorHandle::DEBUG);
            return array();
        }

        return $this->getAllOUs(array_merge(array(
            'baseDN'=>$ouDN),
            (array)$searchParams
        ));
    }

    /**
     * Get the user groups of a given user
	 *
     * @param string $userDN
     *        The distinguished name of the user account
     * @param bool $recursive
     *        Set to true to recursively get ALL the user's groups (including inherited ones)
     * @param int $groupTypes
     *        Bitwise filter for group types you want returned
     *        @see $this->getAllGroups()
     *
     * @return array
     */
    public function getGroupsFromUser($userDN,$recursive=FALSE,$groupTypes=NULL)
    {
        if(!$this->isUser($userDN)){
            errorHandle::newError(__METHOD__."() - Specified userDN is not a user!", errorHandle::DEBUG);
            return array();
        }

        // Get some attributes of the user.  (Microsoft AD Hack - Return primaryGroupID and objectSid)
        $user = $this->getAttributes($userDN,'memberOf,primaryGroupID,objectSid');
        $results = array();
        foreach($user['memberof'] as $group){
            if(!isset($groupTypes) || $groupTypes & $this->getGroupType($group)) $results[] = $group;
            if($recursive){
                $results = array_merge($results, $this->__getGroupsFromUser($group, $groupTypes));
            }
        }

        // Microsoft AD Hack
        // AD fails to return the user's Primary Group
        if($user['primarygroupid']){
            $sid = $this->binSIDtoText($user['objectsid']);
            $primaryGroupSID = 'S-'.substr($sid, 0, strrpos($sid,'-')).'-'.$user['primarygroupid'];
            $primaryGroup = $this->searchEntries("(|(objectSid=$primaryGroupSID))", array('recursive'=>true,'sizeLimit'=>1));
            $results[] = $primaryGroup[0]['dn'];
        }

        return array_unique($results);
    }

    /**
     * Helper method of getGroupsFromUser()
	 *
     * @param string $groupDN
     *        The distinguished name of the potential child group
     * @param int $groupTypes
     *        Bitwise filter for group types you want returned
     *        @see $this->getAllGroups()
     * @return array
     */
    private function __getGroupsFromUser($groupDN,$groupTypes=NULL)
    {
        $results = array();
        $groups = $this->getParentGroups($groupDN);
        if(isset($groups)){
            foreach($groups as $group){
                // Skip and groups we're ignoring
                if(isset($groupTypes) and !($groupTypes & $this->getGroupType($group))) continue;
                $results[] = $group['dn'];
                $results = array_merge($results, $this->__getGroupsFromUser($group, $groupTypes));
            }
        }
        return $results;
    }

    /**
     * Returns TRUE ifs the DN is a user account
	 *
     * @param string $dn
     * @return bool
     */
    public function isUser($dn)
    {
        $entry = $this->getAttributes($dn,'objectClass');
        return in_array('user', $entry['objectclass']);
    }

    /**
     * Returns TRUE if the DN is a user account, and is an active account
	 *
     * @param string $dn
     * @return bool
     */
    public function isActiveUser($dn)
    {
        if($this->isUser($dn)){
            $entry = $this->getAttributes($dn,'userAccountControl');
            return $entry['useraccountcontrol'] == 512;
        }
        return false;
    }

    /**
     * Returns TRUE if the DN is an OU (Organizational Unit)
	 *
     * @param  $dn
     * @return bool
     */
    public function isOU($dn)
    {
        $entry = $this->getAttributes($dn,'objectClass');
        return in_array('organizationalUnit', $entry['objectclass']);
    }

    /**
     * Returns TRUE if the DN is Group
	 *
     * @param string $dn
     * @return bool
     */
    public function isGroup($dn)
    {
        $entry = $this->getAttributes($dn,'objectClass');
        return in_array('group', $entry['objectclass']);
    }

    /**
     * Returns the type of a given group
	 *
     * @param string $dn
     * @return int|null
     *         null: Not a group, or an unknown group type
     *         int: one of the self::GROUP_* constants
     */
    public function getGroupType($dn)
    {
        if($this->isGroup($dn)){
            $entry = $this->getAttributes($dn,'groupType');
            switch($entry['grouptype']){
                case -2147483646:
                    // Global security groups
                    return self::GROUP_GLOBAL_SECURITY;
                    break;

                case -2147483640:
                    // Universal security group
                    return self::GROUP_UNIVERSAL_SECURITY;
                    break;

                case -2147483644:
                    // Local security group
                    return self::GROUP_LOCAL_SECURITY;
                    break;

                case 2:
                    // Global distribution list
                    return self::GROUP_GLOBAL_DISTRIBUTION;
                    break;

                case 4:
                    // Universal distribution list
                    return self::GROUP_UNIVERSAL_DISTRIBUTION;
                    break;

                case 8:
                    // Local distribution list
                    return self::GROUP_LOCAL_DISTRIBUTION;
                    break;

                default:
                    return NULL;
                    break;
            }
        }
        return NULL;
    }



    /**
     * Retrieves a requested item from the ldapConfig. If no config is set, will return NULL)
	 *
     * @param  $name
     * @return null|mixed
     */
    private function getConfig($name)
    {
        $name = trim($name);
        if(isset($this->config) and array_key_exists($name, $this->config)){
            return $this->config[$name];
        }else{
            return NULL;
        }
    }


    /**
     * Convert a semantic filter string into an LDAP filter string
     * Example: (a=1 and b=1) or (a=2 and b=2) => (|(&(a=1)(b=1))(&(a=2)(b=2)))
     *
     * @static
     * @param string $str
     * @return string
     */
    public static function buildFilterString($str)
    {
        return '('.self::__buildFilterString($str).')';
    }

    /**
     * Internal working method for self::buildFilterString()
	 *
     * @todo This needs a good bit of work. (It's hardly bullet-proof)
     * @static
     * @see self::buildFilterString()
     * @param string $str
     * @return string
     */
    private static function __buildFilterString($str)
    {
        // Define some RegEx patterns
        $andPattern = '/\)\s(and)\s\(/i';
        $orPattern = '/\)\s(or)\s\(/i';
        $andOrPattern = '/(?<=\)\s)(and|or)(?=\s\()/i';

        // Clean the input string
        $result = $str = trim(preg_replace(
            array(
//                 '/\s*([\(|\)])\s*/',
//                 '/\!\((\w+)\s?=\s?(\w+)\)/',
//                 '/(\S)(and|or|not)/i',
//                 '/(and|or|not)(\S)/i',
            ),
            array(
//                 '$1',
//                 '!$1=$2',
//                 '$1 $2',
//                 '$1 $2',
            ),
            $str));

        // Break down sub-groupings
        for($i=0,$n=0,$captureGroup=''; $i<strlen($str); $i++){
            if($n) $captureGroup.=$str[$i];
            if($str[$i] == '(') $n++;
            if($str[$i] == ')'){
                $n--;
                if(!$n){
                    $captureGroup = substr($captureGroup,0,-1);
                    $result=str_replace($captureGroup, self::__buildFilterString($captureGroup), $result);
                    $captureGroup='';
                }
            }
        }

        // Okay, complete the processing of this grouping

        // Convert key=value to (key=value)
//        $result = preg_replace('/(\s|^)(!?)(\w+)=(\w+)(\s|$)/i', '$1($2$3=$4)$5', $result);
        $result = preg_replace('/(\s|^)(!?)([\w\-\.\:]+)=([\w\-\.\:]+)(\s|$)/i', '$1($2$3=$4)$5', $result);
//        $result = preg_replace('/(\s|^)(!?)([\(\S]+)=([\)\S]+)(\s|$)/i', '$1($2$3=$4)$5', $result);

        // Check for malformed and / or usage
        if(preg_match($andPattern, $result) and preg_match($orPattern, $result)){
            // Error - You can't have AND and OR in the same grouping (one must be a sub-group)
            die("Error - You can't have AND and OR in the same grouping (one must be a sub-group)!");
        }

        $searchParts = array_map('trim', preg_split($andOrPattern, $result));

        preg_match($andOrPattern, $result, $m);
        $bool = (isset($m[1]) and $m[1]=='and') ? '&' : '|';

        return sprintf('%s%s', $bool, implode('',$searchParts));
    }





	/**
	 * Microsoft AD Hack - littleEndian()
	 * MS AD fails to return the user's Primary Group
	 *
	 * Used in: ldapSearch->getGroupsFromUser()
	 *          ldapSearch->formatSearchResults()
	 * References: http://support.microsoft.com/kb/321360
	 *             http://support.microsoft.com/kb/297951
	 *             http://us2.php.net/manual/en/ref.ldap.php#46984
	 *
	 * @param $hex
	 * @return string
	 */
	private function littleEndian($hex){
        $result='';
        for ($x=strlen($hex)-2; $x >= 0; $x=$x-2) {
            $result .= substr($hex,$x,2);
        }
        return $result;
    }

	/**
	 * Microsoft AD Hack - binSIDtoText()
	 * MS AD fails to return the user's Primary Group
	 *
	 * Used in: ldapSearch->getGroupsFromUser()
	 *          ldapSearch->formatSearchResults()
	 * @see http://support.microsoft.com/kb/321360
	 * @see http://support.microsoft.com/kb/297951
	 * @see http://us2.php.net/manual/en/ref.ldap.php#46984
	 * @param $binsid
	 * @return string
	 */
	private function binSIDtoText($binsid) {
        $hex_sid=bin2hex($binsid);
        $rev = hexdec(substr($hex_sid,0,2));
        $subcount = hexdec(substr($hex_sid,2,2));
        $auth = hexdec(substr($hex_sid,4,12));
        $result = "$rev-$auth";
        for ($x=0;$x < $subcount; $x++) {
            $subauth[$x] = hexdec($this->littleEndian(substr($hex_sid,16+($x*8),8)));
            $result .= "-".$subauth[$x];
        }
        return $result;
    }

	/**
	 * Helper Function - Convert binary GID to hex version
	 * This converts the binary GID is search results to a usable hex value
	 *
	 * Used in: ldapSearch->formatSearchResults()
	 * @see http://us2.php.net/manual/en/function.ldap-get-values-len.php#73198
	 * @param $object_guid
	 * @return string
	 */
	private function binGIDtoText($object_guid) {
        $hex_guid = bin2hex($object_guid);
        $hex_guid_to_guid_str = '';
        for($k = 1; $k <= 4; ++$k) {
            $hex_guid_to_guid_str .= substr($hex_guid, 8 - 2 * $k, 2);
        }
        $hex_guid_to_guid_str .= '-';
        for($k = 1; $k <= 2; ++$k) {
            $hex_guid_to_guid_str .= substr($hex_guid, 12 - 2 * $k, 2);
        }
        $hex_guid_to_guid_str .= '-';
        for($k = 1; $k <= 2; ++$k) {
            $hex_guid_to_guid_str .= substr($hex_guid, 16 - 2 * $k, 2);
        }
        $hex_guid_to_guid_str .= '-' . substr($hex_guid, 16, 4);
        $hex_guid_to_guid_str .= '-' . substr($hex_guid, 20);

        return strtoupper($hex_guid_to_guid_str);
    }
}
