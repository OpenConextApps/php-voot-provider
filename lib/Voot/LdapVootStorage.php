<?php

class LdapVootStorage implements IVootStorage {

    private $_config;
    private $_ldapConnection;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_ldapConnection = ldap_connect($this->_config['uri']);

        if(FALSE === $this->_ldapConnection) {
            throw new Exception("unable to connect to ldap server");
        }

        if(array_key_exists('bindDn', $this->_config) && array_key_exists('bindPass', $this->_config)) {
            if(FALSE === ldap_bind($this->_ldapConnection, $this->_config['bindDn'], $this->_config['bindPass'])) {
                throw new Exception("unable to bind to ldap server");
            }
        }
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        return FALSE;
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        /* get the user DN */
        $filter = '(' . $this->_config['userIdAttribute'] . '=' . $resourceOwnerId . ')';
        $query = ldap_search($this->_ldapConnection, $this->_config['peopleDn'], $filter);
        $entryID = ldap_first_entry($this->_ldapConnection, $query);
        $userDn = ldap_get_dn($this->_ldapConnection, $entryID);

        /* get the group memberships */
        $filter = '(' . $this->_config['memberAttribute'] . '=' . $userDn . ')';
        $query = ldap_search($this->_ldapConnection, $this->_config['groupDn'], $filter);

        $groups = array();

        for ($entryID = ldap_first_entry($this->_ldapConnection, $query); $entryID !== FALSE; $entryID = ldap_next_entry($this->_ldapConnection, $entryID)) {

            $attributes = ldap_get_attributes($this->_ldapConnection, $entryID);

            $commonName = array_key_exists("cn", $attributes) ? $attributes["cn"][0] : "";
            $description = array_key_exists("description", $attributes) ? $attributes["description"][0] : "";
            $distinguishedName = ldap_get_dn($this->_ldapConnection, $entryID);

            array_push($groups, array ( 'id' => urlencode($distinguishedName), 'title' => $commonName, 'description' => $description, 'voot_membership_role' => 'member'));
        }
        $startIndex = 0;
        $totalResults = sizeof($groups);
        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => sizeof($groups), 'entry' => $groups);
    }
}

?>
