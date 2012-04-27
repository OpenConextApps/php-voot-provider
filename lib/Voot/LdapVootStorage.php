<?php

class LdapVootStorage implements IVootStorage {

    private $_config;
    private $_ldapConnection;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_ldapConnection = @ldap_connect($this->_config['uri']);
        if(FALSE === $this->_ldapConnection) {
            throw new Exception("unable to connect to ldap server");
        }
        if(array_key_exists('bindDn', $this->_config) && array_key_exists('bindPass', $this->_config)) {
            if(FALSE === @ldap_bind($this->_ldapConnection, $this->_config['bindDn'], $this->_config['bindPass'])) {
                throw new Exception("unable to bind to ldap server, possibly invalid credentials");
            }
        }
    }

    private function _getUserDn($resourceOwnerId) {
        /* get the user distinguishedName */
        $filter = '(' . $this->_config['userIdAttribute'] . '=' . $resourceOwnerId . ')';
        $query = ldap_search($this->_ldapConnection, $this->_config['peopleDn'], $filter);
        if(FALSE === $query) {
            throw new Exception("directory query for user failed");
        }
        /* we assume there is only one entry for the specified user, if not
           we only look at the first result */
        $entry = ldap_first_entry($this->_ldapConnection, $query);
        if(FALSE === $entry) {
            throw new Exception("user not found in directory");
        }
        $userDn = ldap_get_dn($this->_ldapConnection, $entry);
        if(FALSE === $userDn) {
            throw new Exception("unable to get user distinguishedName");
        }
        return $userDn;
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        $userDn = $this->_getUserDn($resourceOwnerId);
    
        // get all members from the group specified by $groupId
        // get the uid from all members in the group

        // this is NOT NICE! (expensive, for every user we need to do a call to fetch the uid! n^2!)

        // find the members of the group
        //ldapsearch -x -H ldap://directory -b '<GROUP DN>' 'uniqueMember=<USER DN>'

        // convert user DN to uid
        //ldapsearch -x -H ldap://directory -b '<USER DN>' uid cn

        return FALSE;
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        $userDn = $this->_getUserDn($resourceOwnerId);

        $userGroups = array();
        /* get the groups the user is a member of */
        $filter = '(' . $this->_config['memberAttribute'] . '=' . $userDn . ')';
        $query = ldap_search($this->_ldapConnection, $this->_config['groupDn'], $filter);
        if(FALSE === $query) {
            throw new Exception("directory query for groups failed");
        }

        $entry = ldap_first_entry($this->_ldapConnection, $query);
        while(FALSE !== $entry) {
            $attributes = ldap_get_attributes($this->_ldapConnection, $entry);
            if(FALSE === $attributes) {
                throw new Exception("unable to get group attributes");
            }
            $commonName = array_key_exists("cn", $attributes) ? $attributes["cn"][0] : "";
            $description = array_key_exists("description", $attributes) ? $attributes["description"][0] : "";
            $distinguishedName = ldap_get_dn($this->_ldapConnection, $entry);
            if(FALSE === $distinguishedName) {
                throw new Exception("unable to get distinguishedName");
            }
            array_push($userGroups, array ('id' => urlencode($distinguishedName),
                                           'title' => $commonName, 
                                           'description' => $description, 
                                           'voot_membership_role' => 'member'));            
            $entry = ldap_next_entry($this->_ldapConnection, $entry);

        }

        // FIXME: we need to implement paging for LDAP as well...
        $startIndex = 0;
        $totalResults = sizeof($userGroups);
        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => $totalResults, 'entry' => $userGroups);
    }
}

?>
