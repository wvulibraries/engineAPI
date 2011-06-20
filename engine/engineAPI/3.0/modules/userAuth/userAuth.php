<?php
/**
 * @todo Add a method for adding a user to a group(s)
 * @todo Add a method for adding a group to a group(s)
 */
class userAuth
{
    /**
     * The database table where user accounts live
     * @var string
     */
    public $tblUsers = 'auth_users';

    /**
     * The database table where user groups live
     * @var string
     */
    public $tblGroups = 'auth_groups';

    /**
     * The database table where the permissions list lives
     * The 'permissions list' is a master listing of ALL available permissions across the entire system
     * @var string
     */
    public $tblPermissions = 'auth_permissions';

    /**
     * The database table where the system authorizations live
     * Authorizations is the mapping of a permission to a given user group or account
     * @var string
     */
    public $tblAuthorizations = 'auth_authorizations';

    /**
     * The database table representing the many to many linking between users and groups
     * @var string
     */
    public $tblUsers2Groups = 'auth_users_groups';

    /**
     * The database table representing the many to many linking between groups and groups
     * @var string
     */
    public $tblGroups2Groups = 'auth_groups_groups';

    /**
     * A default authToken. If this is specified, it will be used in lue of specifying one on each checkPermission() call
     * @var string
     */
    public $defaultToken;

    /**
     * A link back to the EngineAPI class
     * @var EngineAPI
     */
    private $engine;

    /**
     * The MySQL database connection
     * @var engineDB
     */
    private $db;

    /**
     * A stack of ALL the user's groups which they are a member of
     * @var array
     */
    private $groups=array();

    /**
     * A stack of ALL the user's permissions.
     * This stack if built by extracting all the user's group's permissions, then extracting the user's permissions.
     * @var array
     */
    private $permissions=array();

    /**
     * Class Constructor
     * @param string|int $userKey
     *        The user's key. (This can be either the user's ID, Username)
     */
    public function __construct($userKey)
    {
        // Link to the engine instance
        $this->engine = EngineAPI::singleton();

        // Copy in any engine config items
        global $engineVars;
        if(array_key_exists('userAuth',$engineVars)){
            if(array_key_exists('tblUsers',$engineVars['userAuth']))          $this->tblUsers          = $engineVars['userAuth']['tblUsers'];
            if(array_key_exists('tblGroups',$engineVars['userAuth']))         $this->tblGroups         = $engineVars['userAuth']['tblGroups'];
            if(array_key_exists('tblPermissions',$engineVars['userAuth']))    $this->tblPermissions    = $engineVars['userAuth']['tblPermissions'];
            if(array_key_exists('tblAuthorizations',$engineVars['userAuth'])) $this->tblAuthorizations = $engineVars['userAuth']['tblAuthorizations'];
            if(array_key_exists('tblUsers2Groups',$engineVars['userAuth']))   $this->tblUsers2Groups   = $engineVars['userAuth']['tblUsers2Groups'];
            if(array_key_exists('tblGroups2Groups',$engineVars['userAuth']))  $this->tblGroups2Groups  = $engineVars['userAuth']['tblGroups2Groups'];
            if(array_key_exists('defaultToken',$engineVars['userAuth']))      $this->defaultToken      = $engineVars['userAuth']['defaultToken'];
        }

        // Connect to the database
        if(!$this->db = $this->engine->getPrivateVar('engineDB')){
            errorHandle::newError(__METHOD__.'() - Cannot get to the engineDB!', errorHandle::CRITICAL);
            return FALSE;
        }

        // Get the user's ID
        $sqlUser = $this->db->query(sprintf("SELECT `ID` FROM `%s` WHERE `ID`='%s' OR `username` LIKE '%s' LIMIT 1",
            $this->db->escape($this->tblUsers),
            $this->db->escape($userKey),
            $this->db->escape($userKey)));
        if(!$sqlUser['numRows']){
            errorHandle::newError(__METHOD__."() - Can't locate a user account with given userKey '$userKey'!", errorHandle::HIGH);
            return FALSE;
        }

        // Save the User's ID for later use
        $userID = mysql_result($sqlUser['result'],0,'ID');

        // Get the user's local groups
        $sqlLocalGroups = $this->db->query(sprintf('SELECT `%s`.* FROM `%s` LEFT JOIN `%s` ON `%s`.group = `%s`.`ID` WHERE `%s`.user=%s',
            $this->db->escape($this->tblGroups),
            $this->db->escape($this->tblGroups),
            $this->db->escape($this->tblUsers2Groups),
            $this->db->escape($this->tblUsers2Groups),
            $this->db->escape($this->tblGroups),
            $this->db->escape($this->tblUsers2Groups),
            $this->db->escape($userID)));
        while($row = mysql_fetch_assoc($sqlLocalGroups['result'])){
            $this->groups[ $row['ID'] ] = $row;
            $this->__getGroups($row['ID']);
        }

        // Add the user's LDAP groups
        if(sessionGet('authType') == 'ldap'){
            global $ldapSearch;

            $groupCleanDNs = array();
            foreach($_SESSION['auth_ldap']['groups'] as $groupDN){
                $groupCleanDNs[] = "'".$this->db->escape($groupDN)."'";
            }

            $sqlLdapGroups = $this->db->query(sprintf('SELECT * FROM `%s` WHERE ldapDN IN (%s)',
                $this->db->escape($this->tblGroups),
                implode(',', $groupCleanDNs)
            ));
            while($row = mysql_fetch_assoc($sqlLdapGroups['result'])){
                $this->groups[ $row['ID'] ] = $row;
                $this->__getGroups($row['ID']);
            }
        }

        // Get the user's group permissions
        $groupIDs = array();
        foreach($this->groups as $group){ $groupIDs[] = $group['ID']; }
        $sqlGroupPermissions = $this->db->query(sprintf("SELECT * FROM %s WHERE groupID IN (%s)",
            $this->db->escape($this->tblAuthorizations),
            implode(',', $groupIDs)
        ));
        while($row = mysql_fetch_assoc($sqlGroupPermissions['result'])){
            $authToken = $row['authToken'];
            $this->permissions[$authToken] = (array_key_exists($authToken, $this->permissions))
                    ? $this->permissions[$authToken] | $row['permissions']
                    : $row['permissions'];
        }

        // Get the user's permissions
        $sqlUserPermissions = $this->db->query(sprintf("SELECT * FROM %s WHERE userID='%s'",
            $this->db->escape($this->tblAuthorizations),
            $this->db->escape($userID)));
        while($row = mysql_fetch_assoc($sqlUserPermissions['result'])){
            $authToken = $row['authToken'];
            $this->permissions[$authToken] = (array_key_exists($authToken, $this->permissions))
                    ? $this->permissions[$authToken] | $row['permissions']
                    : $row['permissions'];
        }

        // Sort the permissions list (to clean it up)
        ksort($this->permissions);
    }

    /**
     * Helper Function - Recursively get a group's groups.
     * @param int $groupID
     * @return array
     */
    private function __getGroups($groupID)
    {
        $result = array();

        $sqlGroups = $this->db->query(sprintf('SELECT `%s`.* FROM `%s` LEFT JOIN `%s` ON `%s`.parentGroup = `%s`.`ID` WHERE `%s`.childGroup=%s',
            $this->db->escape($this->tblGroups),
            $this->db->escape($this->tblGroups),
            $this->db->escape($this->tblGroups2Groups),
            $this->db->escape($this->tblGroups2Groups),
            $this->db->escape($this->tblGroups),
            $this->db->escape($this->tblGroups2Groups),
            $this->db->escape($groupID)));
        if($sqlGroups['numRows']){
            while($row = mysql_fetch_assoc($sqlGroups['result'])){
                $this->groups[ $row['ID'] ] = $row;
                $this->__getGroups($row['ID']);
            }
        }
        return $result;
    }

    /**
     * Check the user's permissions against a given token and permission
     * @param string|int $authToken
     *        If a string is given, this will be the authToken
     *        If an int is given, the default authToken will be used. (and this int will be the permission)
     * @param int|null $permission
     *        If given, this will be the bitwise permission to check for
     * @return bool
     */
    public function checkPermission($authToken,$permission=NULL)
    {
        if(is_numeric($authToken)){
            // The user is wanting to use a pre-set authToken
            $t = $this->defaultToken;
            if(is_null($t)){
                errorHandle::newError(__METHOD__."() - Trying to us a default authToken when one isn't set.", errorHandle::DEBUG);
                return FALSE;
            }
            $permission = $authToken;
            $authToken  = $t;
        }

        if(array_key_exists($authToken, $this->permissions)){
            if(isset($permission)){
                return (bool)bcBitwise::bcAND($permission, $this->permissions[$authToken]);
            }else{
                return TRUE;
            }
        }else{
            // The user is about to be denied. The only thing that will save them now is a wildcard authToken
            foreach($this->permissions as $auth => $perm){
                if(substr($auth,0,1) == substr($auth,-1)){
                    if(preg_match($auth,$authToken)) return TRUE;
                }
            }
            return FALSE;
        }
    }

    /**
     * Gets a listing of all the groups the user is in
     * @return array
     */
    public function getUserGroups()
    {
        return $this->groups;
    }

    /**
     * Gets an array of all the user's active permissions.
     * The key's are the authToken, and the values are the permissions for that token
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Returns the next available permission number for a given authToken
     * @todo Is this needed as a public function? (since it's only real use is to create a new permission which is handled in $this->addPermission()
     * @param string $authToken
     * @return string
     */
    public function nextPermission($authToken)
    {
        $result = array();

        $sqlPermissions = $this->db->query(sprintf("SELECT permission,isEmpty FROM `%s` WHERE authToken='%s'",
            $this->db->escape($this->tblPermissions),
            $this->db->escape($authToken)));

        if($sqlPermissions['numRows']){
            while($row = mysql_fetch_assoc($sqlPermissions['result'])){
                if(!$row['isEmpty']){
                    // Skip non-empty permissions
                    continue;
                }else{
                    // Return the 1st 'empty' permission we find
                    return $row['permission'];
                }
            }
            // If we get here then there were no 'holes' in the permissions list. Take the last one, and double it.
            return bcmul( (string)$row['permission'], '2');
        }else{
            // If we're here, then there were no permissions defined for this token. (This is the 1st)
            return '1';
        }
    }

    /**
     * Grant a given authToken (and permission) to a given target (or set of targets)
     * @param string|array $target
     *        The target(s) to apply the given authorization to
     *        Target Syntax:
     *         + g[id]:GROUP_ID - Apply this permission to the specified group
     *         + u[id]:USER_ID - Apply this permission to the specified user
     *        Note: To specify a set of targets pass them as an array, otherwise a string will do
     * @param string $authToken
     *        The authToken to assign
     * @param int $permission
     *        The permission to assign
     * @param string $notes
     *
     * @return int
     *         The number of grants which completed successfully will be returned
     */
    public function grantPermission($target,$authToken,$permission=NULL,$notes='')
    {
        $results = array();
        // Make sure we're working with an array of targets
        $targets = (array)$target; unset($target);
        foreach($targets as $target){
            // Make sure this is a valid target
            if(preg_match('|^([ug])(?:id)?:(\d+)$|i', $target, $m)){
                // Make sure this is a valid user/group id
                $tbl = (strtolower($m[1]) == 'u') ? $this->tblUsers : $this->tblGroups;
                $sqlIdCheck = $this->db->query(sprintf("SELECT COUNT(`ID`) AS `i` FROM `%s` WHERE `ID` = '%s'", $this->db->escape($tbl), $this->db->escape($m[2])));
                if(!mysql_result($sqlIdCheck['result'], 0, 'i')){
                    errorHandle::newError(__METHOD__."() - Grant target does not exist.", errorHandle::DEBUG);
                    continue;
                }

                // Make sure this is a valid permission
                $sql = ($permission)
                        ? sprintf("SELECT COUNT(*) AS `i` FROM `%s` WHERE `authToken` = '%s' AND `permission` = '%s'",
                            $this->db->escape($this->tblPermissions),
                            $this->db->escape($authToken),
                            $this->db->escape($permission))
                        : sprintf("SELECT COUNT(*) AS `i` FROM `%s` WHERE `authToken` = '%s'",
                            $this->db->escape($this->tblPermissions),
                            $this->db->escape($authToken));
                $sqlIdCheck = $this->db->query($sql);
                if(!mysql_result($sqlIdCheck['result'], 0, 'i')){
                    errorHandle::newError(__METHOD__."() - Specified authToken/permission hasn't been defined yet.", errorHandle::DEBUG);
                    continue;
                }


                // Look for an already existing authorization
                $field = (strtolower($m[1]) == 'u') ? 'userID' : 'groupID';
                $sqlExistingAuth = $this->db->query(sprintf("SELECT * FROM `%s` WHERE `authToken`='%s' AND `%s`='%s'",
                    $this->db->escape($this->tblAuthorizations),
                    $this->db->escape($authToken),
                    $this->db->escape($field),
                    $this->db->escape($m[2])));
                if($sqlExistingAuth['numRows']){
                    // Update the existing authorization for this token
                    $sqlUpdateAuth = $this->db->query(sprintf("UPDATE `%s` SET `permissions`='%s', `notes`='%s' WHERE `authToken`='%s' AND `%s`='%s' LIMIT 1",
                        $this->db->escape($this->tblAuthorizations),
                        $this->db->escape(bcBitwise::bcOR(mysql_result($sqlExistingAuth['result'], 0, 'permissions'), $permission)),
                        $this->db->escape($notes),
                        $this->db->escape($authToken),
                        $this->db->escape($field),
                        $this->db->escape($m[2])));
                    $results[] = ($sqlUpdateAuth['errorNumber']) ? 0 : 1;
                }else{
                    // Create a new authorization for this token
                    $sqlNewAuth = $this->db->query(sprintf("INSERT INTO `%s` (`authToken`,`%s`,`permissions`,`notes`) VALUES('%s','%s','%s','%s')",
                        $this->db->escape($this->tblAuthorizations),
                        $this->db->escape($field),
                        $this->db->escape($authToken),
                        $this->db->escape($m[2]),
                        $this->db->escape($permission),
                        $this->db->escape($notes)));
                    $results[] = ($sqlNewAuth['errorNumber']) ? 0 : 1;
                }
            }
        }

        // Return the results
        return array_sum($results);
    }

    /**
     * Revoke a given authToken (and permission) from a given target (or set of targets)
     * @param string|array $target
     *        The target(s) to apply the given authorization to
     *        Target Syntax:
     *         + g[id]:GROUP_ID - Apply this permission to the specified group
     *         + u[id]:USER_ID - Apply this permission to the specified user
     *        Note: To specify a set of targets pass them as an array, otherwise a string will do
     * @param string $authToken
     *        The authToken to assign
     * @param int $permission
     *        The permission to assign
     * @return int
     *         The number of revocations which completed successfully will be returned
     */
    public function revokePermission($target,$authToken,$permission=0)
    {
        $results = array();
        // Make sure we're working with an array of targets
        $targets = (array)$target; unset($target);
        foreach($targets as $target){
            // Make sure this is a valid target
            if(preg_match('|^([ug])(?:id)?:(\d+)$|i', $target, $m)){
                // Okay, if $permission is zero. then we're removing the entire authentication row. Otherwise, we're just revoking the permission itself
                $field = (strtolower($m[1]) == 'u') ? 'userID' : 'groupID';
                if(!$permission){
                    // Remove the auth row
                    $sqlAuthRevoke = $this->db->query(sprintf("DELETE FROM `%s` WHERE `authToken`='%s' AND `%s`='%s' LIMIT 1",
                        $this->db->escape($this->tblAuthorizations),
                        $this->db->escape($authToken),
                        $this->db->escape($field),
                        $this->db->escape($m[2])));
                    $results[] = ($sqlAuthRevoke['errorNumber']) ? 0 : 1;
                }else{
                    // Update the auth row (remove the permission)
                    $sqlExistingAuth = $this->db->query(sprintf("SELECT `permissions` FROM `%s` WHERE `authToken`='%s' AND `%s`='%s'",
                        $this->db->escape($this->tblAuthorizations),
                        $this->db->escape($authToken),
                        $this->db->escape($field),
                        $this->db->escape($m[2])));
                    
                    if($sqlExistingAuth['numRows']){
                        $sqlAuthUpdate = $this->db->query(sprintf("UPDATE `%s` SET `permissions`='%s' WHERE `authToken`='%s' AND `%s`='%s' LIMIT 1",
                            $this->db->escape($this->tblAuthorizations),
                            $this->db->escape(bcBitwise::bcXOR(mysql_result($sqlExistingAuth['result'],0,'permissions'), $permission)),
                            $this->db->escape($authToken),
                            $this->db->escape($field),
                            $this->db->escape($m[2])));
                        $results[] = ($sqlAuthUpdate['errorNumber']) ? 0 : 1;
                    }else{
                        errorHandle::newError(__METHOD__."() - Specified authToken/permission dosen't exist on the target.", errorHandle::DEBUG);
                        continue;
                    }
                }
            }
        }

        // Return the results
        return array_sum($results);
    }

    /**
     * Adds a new permission to the database allowing it to be used and assigned
     * @param string $authToken
     *        The authToken for this permission
     * @param string $name
     *        The name of this permission
     * @param string $desc
     *        An optional description for this permission
     * @return string
     *         The id of the new permission will be returned
     */
    public function addPermission($authToken,$name,$desc='')
    {
        $sqlPermissions = $this->db->query(sprintf("SELECT `permission`,`isEmpty` FROM `%s` WHERE `authToken`='%s' ORDER BY `permission` + 0 ASC",
            $this->db->escape($this->tblPermissions),
            $this->db->escape($authToken)));

        $lastPermission=0;
        while($row = mysql_fetch_assoc($sqlPermissions['result'])){
            if(!$row['isEmpty']){
                // Skip non-empty permissions
                $lastPermission = $row['permission'];
                continue;
            }else{
                // Okay, we are now at the 1st 'empty' permission. This is where we'll place this new permission
                $this->db->query(sprintf("UPDATE `%s` SET `isEmpty`='0',`name`='%s',`description`='%s' WHERE `authToken`='%s' AND `permission`='%s'",
                    $this->db->escape($this->tblPermissions),
                    $this->db->escape(trim($name)),
                    $this->db->escape(trim($desc)),
                    $this->db->escape($authToken),
                    $this->db->escape($row['permission'])));

                // Return the permission id
                return $row['permission'];
            }
        }

        /*
         * If we are here, then there were no holes available in the permissions set.
         * Now, to get the next number we need to double the last permission we found.
         * If after doubling it's still 0, then that means this is the 1st permission for the given
         * authToken and we need to manually set the next number at 1.
         */
        $nextNumber = ($lastPermission) ? bcmul( $lastPermission, '2') : 1;
        $this->db->query(sprintf("INSERT INTO `%s` (`authToken`,`permission`,`name`,`description`) VALUES('%s','%s','%s','%s')",
            $this->db->escape($this->tblPermissions),
            $this->db->escape(trim($authToken)),
            $this->db->escape(trim($nextNumber)),
            $this->db->escape(trim($name)),
            $this->db->escape(trim($desc))));

        // Lastly, we need to return the nextNumber
        return $nextNumber;
    }

    /**
     * This will remove a permission from the system.
     * This is done by revoking the permission from all parties, then flagging it as an 'empty' permission in the database
     * @param string $authToken
     * @param int $permission
     * @return bool
     */
    public function removePermission($authToken,$permission)
    {
        // Check to make sure the token is valid
        if($this->checkToken($authToken)){
            // Get all the authorization candidates
            $sqlAuth = $this->db->query(sprintf("SELECT `ID`,`permissions` FROM `%s` WHERE `authToken`='%s'",
                $this->db->escape($this->tblAuthorizations),
                $this->db->escape($authToken)));

            if($sqlAuth['numRows']){
                while($row = mysql_fetch_assoc($sqlAuth)){
                    // If AND passes, then this row contains this permission. (So we need to update it)
                    if(bcBitwise::bcAND($row['permissions'], $permission)){
                        $newPermission = bcBitwise::bcXOR($row['permissions'], $permission);
                        $this->db->query(sprintf("UPDATE `%s` SET `permissions`='%s' WHERE `ID`='%s' LIMIT 1",
                            $this->db->escape($this->tblAuthorizations),
                            $this->db->escape($newPermission),
                            $this->db->escape($row['ID'])));
                    }
                }
            }
            
            // Okay, we can now remove the permission
            $sqlRemovePermission = $this->db->query(sprintf("UPDATE `%s` SET `isEmpty`='1', `name`='', `description`='' WHERE `authToken`='%s' AND `permission`='%s' LIMIT 1",
                $this->db->escape($this->tblPermissions),
                $this->db->escape($authToken),
                $this->db->escape($permission)));

            // Return the result
            return $sqlRemovePermission['errorNumber'] == 0;
        }else{
            errorHandle::newError(__METHOD__."() - Specified authToken dosen't exist.", errorHandle::DEBUG);
            return false;
        }
    }

    /**
     * Returns TRUE if the given token has been defined in the 'permissions' database table
     * @param string $authToken
     * @return bool
     */
    public function checkToken($authToken)
    {
        $sqltokenCheck = $this->db->query(sprintf("SELECT COUNT(`authToken`) FROM `%s` WHERE `authToken`='%s'",
            $this->db->escape($this->tblPermissions),
            $this->db->escape($authToken)));
        return (bool)$sqltokenCheck['numRows'];
    }
}