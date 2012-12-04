<?php

namespace VootProvider;

use \RestService\Utils\Config as Config;

class LdapVootStorage implements IVootStorage
{
    private $_c;
    private $_ldapConnection;

    public function __construct(Config $c)
    {
        $this->_c = $c;
        $this->_ldapConnection = @ldap_connect($this->_c->getSectionValue('LdapVootStorage', 'uri'));
        if (FALSE === $this->_ldapConnection) {
            throw new VootStorageException("ldap_error", "unable to connect to ldap server");
        }

        if (NULL !== $this->_c->getSectionValue('LdapVootStorage', 'bindDn', FALSE)) {
            if (FALSE === @ldap_bind($this->_ldapConnection, $this->_c->getSectionValue('LdapVootStorage', 'bindDn'), $this->_c->getSectionValue('LdapVootStorage', 'bindPass', FALSE))) {
                throw new VootStorageException("ldap_error", "unable to bind to ldap server, possibly invalid credentials");
            }
        }
    }

    private function _getUserDn($resourceOwnerId)
    {
        /* get the user distinguishedName */
        $filter = '(' . $this->_c->getSectionValue('LdapVootStorage', 'userIdAttribute') . '=' . $resourceOwnerId . ')';
        $query = @ldap_search($this->_ldapConnection, $this->_c->getSectionValue('LdapVootStorage', 'peopleDn'), $filter);
        if (FALSE === $query) {
            throw new VootStorageException("ldap_error", "directory query for user failed");
        }
        /* we assume there is only one entry for the specified user, if not
           we only look at the first result */
        $entry = @ldap_first_entry($this->_ldapConnection, $query);
        if (FALSE === $entry) {
            throw new VootStorageException("not_found", "user not found");
        }
        $userDn = @ldap_get_dn($this->_ldapConnection, $entry);
        if (FALSE === $userDn) {
            throw new VootStorageException("ldap_error", "unable to get user distinguishedName");
        }

        return $userDn;
    }

    private function _getUserAttributesByDn($userDn)
    {
        $query = @ldap_read($this->_ldapConnection, $userDn, "(objectClass=*)", array_values($this->_c->getSectionValue('LdapVootStorage', 'attributeMapping')));
        if (FALSE === $query) {
            throw new VootStorageException("ldap_error", "directory query for user failed");
        }
        $entry = @ldap_first_entry($this->_ldapConnection, $query);
        if (FALSE === $entry) {
            throw new VootStorageException("not_found", "user not found");
        }
        $attributes = @ldap_get_attributes($this->_ldapConnection, $entry);
        if (FALSE === $attributes) {
            throw new VootStorageException("ldap_error", "unable to get user attributes");
        }
        $filteredAttributes = $this->_filterAttributes($attributes);

        return $filteredAttributes;
    }

    public function getUserAttributes($resourceOwnerId)
    {
        $filter = '(' . $this->_c->getSectionValue('LdapVootStorage', 'userIdAttribute') . '=' . $resourceOwnerId . ')';
        $query = @ldap_search($this->_ldapConnection, $this->_c->getSectionValue('LdapVootStorage', 'peopleDn'), $filter);
        if (FALSE === $query) {
            throw new VootStorageException("ldap_error", "directory query for user failed");
        }
        /* we assume there is only one entry for the specified user, if not
           we only look at the first result */
        $entry = @ldap_first_entry($this->_ldapConnection, $query);
        if (FALSE === $entry) {
            throw new VootStorageException("not_found", "user not found");
        }
        $attributes = @ldap_get_attributes($this->_ldapConnection, $entry);
        if (FALSE === $attributes) {
            throw new VootStorageException("ldap_error", "unable to get user attributes");
        }
        $filteredAttributes = $this->_filterAttributes($attributes);
        $startIndex = 0;
        $totalResults = 1;

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => $totalResults, 'entry' => $filteredAttributes);
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null)
    {
        // get the members of a group by its cn
        // ldapsearch -x -H ldap://localhost -b 'ou=Groups,dc=wind,dc=surfnet,dc=nl' '(cn=SCRUM-team)' uniqueMember

        // get all members from the group specified by $groupId
        // get the uid from all members in the group

        // this is NOT NICE! (expensive, for every user we need to do a call to fetch the uid! n^2!)

        // find the members of the group
        //ldapsearch -x -H ldap://directory -b '<GROUP DN>' 'uniqueMember=<USER DN>'

        // convert user DN to uid
        //ldapsearch -x -H ldap://directory -b '<USER DN>' uid cn

        $memberAttribute = $this->_c->getSectionValue('LdapVootStorage', 'memberAttribute');

        $userDn = $this->_getUserDn($resourceOwnerId);

        // FIXME: make sure the user is member of the group being requested

        $filter = '(cn=' . $groupId . ')';
        $query = @ldap_search($this->_ldapConnection, $this->_c->getSectionValue('LdapVootStorage', 'groupDn'), $filter, array($memberAttribute));
        if (FALSE === $query) {
            throw new VootStorageException("ldap_error", "directory query for group failed");
        }

        $entry = @ldap_first_entry($this->_ldapConnection, $query);
        if (FALSE === $entry) {
            throw new VootStorageException("not_found", "group not found");
        }
        $attributes = @ldap_get_attributes($this->_ldapConnection, $entry);
        if (FALSE === $attributes) {
            throw new VootStorageException("ldap_error", "unable to get group attributes");
        }

        $members = array();

        if (array_key_exists($memberAttribute, $attributes)) {
            // we have some members
            for ($i = 0; $i < $attributes[$memberAttribute]["count"]; $i++) {
                // member DN
                // fetch attributes for this particular user
                array_push($members, $this->_getUserAttributesByDn($attributes[$memberAttribute][$i]) + array('voot_membership_role' => "member"));
            }
        }

        $startIndex = 0;
        $totalResults = sizeof($members);

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => $totalResults, 'entry' => $members);
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null)
    {
        $userDn = $this->_getUserDn($resourceOwnerId);

        $userGroups = array();
        /* get the groups the user is a member of */
        $filter = '(' . $this->_c->getSectionValue('LdapVootStorage', 'memberAttribute') . '=' . $userDn . ')';
        $query = @ldap_search($this->_ldapConnection, $this->_c->getSectionValue('LdapVootStorage', 'groupDn'), $filter);
        if (FALSE === $query) {
            throw new VootStorageException("ldap_error", "directory query for groups failed");
        }

        $entry = @ldap_first_entry($this->_ldapConnection, $query);
        while (FALSE !== $entry) {
            $attributes = @ldap_get_attributes($this->_ldapConnection, $entry);
            if (FALSE === $attributes) {
                throw new VootStorageException("ldap_error", "unable to get group attributes");
            }
            $commonName = array_key_exists("cn", $attributes) ? $attributes["cn"][0] : NULL;
            $displayName = array_key_exists("displayName", $attributes) ? $attributes["displayName"][0] : NULL;
            $description = array_key_exists("description", $attributes) ? $attributes["description"][0] : NULL;
            $distinguishedName = @ldap_get_dn($this->_ldapConnection, $entry);
            if (FALSE === $distinguishedName) {
                throw new VootStorageException("ldap_error", "unable to get distinguishedName");
            }
            if (NULL === $commonName) {
                throw new VootStorageException("ldap_error", "no cn for group");
            }
            $a = array();
            $a['id'] = $commonName;
            $a['title'] = NULL !== $displayName ? $displayName : $commonName;
            if (NULL !== $description) {
                $a['description'] = $description;
            }
            $a['voot_membership_role'] = 'member';
            array_push($userGroups, $a);
            $entry = @ldap_next_entry($this->_ldapConnection, $entry);
        }

        // FIXME: we need to implement paging for LDAP as well...
        $startIndex = 0;
        $totalResults = sizeof($userGroups);

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => $totalResults, 'entry' => $userGroups);
    }

    private function _filterAttributes($attributes)
    {
        $attributeMapping = $this->_c->getSectionValue('LdapVootStorage', 'attributeMapping');
        $filteredAttributes = array();
        foreach ($attributeMapping as $k => $v) {
            if (array_key_exists($v, $attributes)) {
                $filteredAttributes[$k] = $attributes[$v][0];
            }
        }
        if (!array_key_exists("id", $filteredAttributes)) {
            throw new VootStorageException("ldap_error", "mapping for 'id' attribute not set in LDAP response");
        }

        return $filteredAttributes;
    }

}
