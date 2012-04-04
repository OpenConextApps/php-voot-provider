<?php
/**
 * Configuration in config/voot.ini:
 * 
 * [voot]
 * storageBackend = "LdapSURFnetVootStorage"
 *
 * [LdapSURFnetVootStorage]
 * uri = "ldap://directory.surfnet.nl"
 * basePeopleDn = "ou=Persons,ou=Office,dc=surfnet,dc=nl"
 * baseGroupsDn = "ou=Groups,ou=Office,dc=surfnet,dc=nl"
 *
 */

class LdapSURFnetVootStorage implements IVootStorage {

    private $_config;
    private $_ldapConnection;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_ldapConnection = ldap_connect($this->_config['uri']);
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        return FALSE;
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        $filter = '(uid=' . $resourceOwnerId . ')';
        $query = ldap_search($this->_ldapConnection, $this->_config['basePeopleDn'], $filter);
        $entryID = ldap_first_entry($this->_ldapConnection, $query);
        $dn = ldap_get_dn($this->_ldapConnection, $entryID);

        $filter = '(uniqueMember='. $dn .')';
        $query = ldap_search($this->_ldapConnection, $this->_config['baseGroupsDn'], $filter);
        $groups = array();
        for ($entryID = ldap_first_entry($this->_ldapConnection, $query); $entryID !== FALSE; $entryID = ldap_next_entry($this->_ldapConnection, $entryID)) {
            $cnValue = ldap_get_values($this->_ldapConnection, $entryID, 'cn');
            $groupName = $cnValue[0];
            $descValue = ldap_get_values($this->_ldapConnection, $entryID, 'description');
            $description = $descValue[0];

            // FIXME: pretty sure the full DN is not appropriate, maybe strip some stuff there...
            // FIXME: what to do with membership role? everyone is just a member for now...
            $groupId = ldap_get_dn($this->_ldapConnection, $entryID);
            array_push($groups, array ( 'id' => urlencode($groupId), 'title' => $groupName, 'description' => $description, 'voot_membership_role' => 'member'));
        }
        // FIXME: need to limit query somehow...
        $startIndex = 0;
        $totalResults = sizeof($groups);
        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => sizeof($groups), 'entry' => $groups);
    }
}

?>
