<?php

class LdapVootStorage implements IVootStorage {

    private $_ldapConnection;
    private $_ldapGroupsDn;

    public function __construct($ldapHost, $ldapGroupsDn) {
        $this->_ldapConnection = ldap_connect($ldapHost);
        $this->_ldapGroupsDn = $ldapGroupsDn;
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        return FALSE;
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        // Example ldapHost: ldap://localhost
        // Example ldapGroupsDn: ou=Groups,dc=wind,dc=surfnet,dc=nl
        // This works for the Fedora Directory Server
        $query = ldap_search($this->_ldapConnection, $this->_ldapGroupsDn, '(uniqueMember=*' . $resourceOwnerId . '*)');
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

// $l = new LdapVootStorage('ldap://localhost', 'ou=Groups,dc=wind,dc=surfnet,dc=nl');
// echo json_encode($l->isMemberOf('fkooman'));

// ldapsearch -H ldap://localhost -b 'ou=Groups,dc=wind,dc=surfnet,dc=nl' -x '(uniqueMember=*fkooman*)'
?>
