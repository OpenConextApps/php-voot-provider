<?php

class LdapVootStorage implements IVootStorage {

    private $_ldapConnection;
    private $_ldapGroupDn;

    public function __construct($ldapHost, $ldapGroupDn) {
        $this->_ldapConnection = ldap_connect($ldapHost);
        $this->_ldapGroupDn = $ldapGroupDn;
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        return FALSE;
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        $filter = '(uniqueMember=uid=' . $resourceOwnerId . ',' . $this->_ldapPeopleDn . ')';        
        // $filter = '(uniqueMember=cn=' . $resourceOwnerId . ',' . $this->_ldapPeopleDn . ')';

        $query = ldap_search($this->_ldapConnection, $this->_ldapGroupsDn, $filter);
        $groups = array();
        for ($entryID = ldap_first_entry($this->_ldapConnection,$query); $entryID !== FALSE; $entryID = ldap_next_entry($this->_ldapConnection, $entryID)) {
            $values = ldap_get_values($this->_ldapConnection, $entryID, 'cn');
            $groupName = $values[0];
            // FIXME: pretty sure the full DN is not appropriate, maybe strip some stuff there...
            // FIXME: what to do with membership role? everyone is just a member for now...
            $groupId = ldap_get_dn($this->_ldapConnection, $entryID);
            array_push($groups, array ( 'id' => $groupId, 'title' => $groupName, 'voot_membership_role' => 'member'));
        }
        // FIXME: need to limit query somehow...
        $startIndex = 0;
        $totalResults = sizeof($groups);
        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => sizeof($groups), 'entry' => $groups);
    }
}

?>
