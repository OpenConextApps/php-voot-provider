<?php

namespace fkooman\VootProvider;

use fkooman\Config\Config;

class LdapVootStorage implements VootStorageInterface
{
    private $config;
    private $ldapConnection;

    public function __construct(Config $c)
    {
        $this->config = $c;
        $this->ldapConnection = @ldap_connect($this->config->s('LdapVootStorage')->l('uri'));
        if (false === $this->ldapConnection) {
            throw new VootStorageException("ldap_error", "unable to connect to ldap server");
        }

        $bindDn = $this->config->s('LdapVootStorage')->l('bindDn', false);
        $bindPass = $this->config->s('LdapVootStorage')->l('bindPass', false);
        if (null !== $bindDn) {
            if (false === @ldap_bind($this->ldapConnection, $bindDn, $bindPass)) {
                throw new VootStorageException(
                    "ldap_error",
                    "unable to bind to ldap server, possibly invalid credentials"
                );
            }
        }
    }

    private function getUserDn($resourceOwnerId)
    {
        /* get the user distinguishedName */
        $filter = '(' . $this->config->s('LdapVootStorage')->l('userIdAttribute') . '=' . $resourceOwnerId . ')';
        $query = @ldap_search($this->ldapConnection, $this->config->s('LdapVootStorage')->l('peopleDn'), $filter);
        if (false === $query) {
            throw new VootStorageException("ldap_error", "directory query for user failed");
        }
        /* we assume there is only one entry for the specified user, if not
           we only look at the first result */
        $entry = @ldap_first_entry($this->ldapConnection, $query);
        if (false === $entry) {
            throw new VootStorageException("not_found", "user not found");
        }
        $userDn = @ldap_get_dn($this->ldapConnection, $entry);
        if (false === $userDn) {
            throw new VootStorageException("ldap_error", "unable to get user distinguishedName");
        }

        return $userDn;
    }

    private function getUserAttributesByDn($userDn)
    {
        $query = @ldap_read(
            $this->ldapConnection,
            $userDn,
            "(objectClass=*)",
            array_values(
                $this->config->s('LdapVootStorage')->s('attributeMapping')->toArray()
            )
        );
        if (false === $query) {
            throw new VootStorageException("ldap_error", "directory query for user failed");
        }
        $entry = @ldap_first_entry($this->ldapConnection, $query);
        if (false === $entry) {
            throw new VootStorageException("not_found", "user not found");
        }
        $attributes = @ldap_get_attributes($this->ldapConnection, $entry);
        if (false === $attributes) {
            throw new VootStorageException("ldap_error", "unable to get user attributes");
        }
        $filteredAttributes = $this->filterAttributes($attributes);

        return $filteredAttributes;
    }

    public function getUserAttributes($resourceOwnerId)
    {
        $filter = sprintf(
            '(%s=%s)',
            $this->config->s('LdapVootStorage')->l('userIdAttribute'),
            $resourceOwnerId
        );
        $query = @ldap_search(
            $this->ldapConnection,
            $this->config->s('LdapVootStorage')->l('peopleDn'),
            $filter
        );
        if (false === $query) {
            throw new VootStorageException(
                "ldap_error",
                "directory query for user failed"
            );
        }
        /* we assume there is only one entry for the specified user, if not
           we only look at the first result */
        $entry = @ldap_first_entry($this->ldapConnection, $query);
        if (false === $entry) {
            throw new VootStorageException("not_found", "user not found");
        }
        $attributes = @ldap_get_attributes($this->ldapConnection, $entry);
        if (false === $attributes) {
            throw new VootStorageException("ldap_error", "unable to get user attributes");
        }
        $data = array($this->filterAttributes($attributes));

        // backwards compatible "emails" element with array
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]["emails"] = array($data[$i]['mail']);
        }

        return array(
            'startIndex' => 0,
            'totalResults' => count($data),
            'itemsPerPage' => count($data),
            'entry' => $data
        );
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

        $memberAttribute = $this->config->s('LdapVootStorage')->l('memberAttribute');

        $userDn = $this->getUserDn($resourceOwnerId);

        // FIXME: make sure the user is member of the group being requested

        $filter = '(cn=' . $groupId . ')';
        $query = @ldap_search(
            $this->ldapConnection,
            $this->config->s('LdapVootStorage')->l('groupDn'),
            $filter,
            array(
                $memberAttribute
            )
        );
        if (false === $query) {
            throw new VootStorageException("ldap_error", "directory query for group failed");
        }

        $entry = @ldap_first_entry($this->ldapConnection, $query);
        if (false === $entry) {
            throw new VootStorageException("not_found", "group not found");
        }
        $attributes = @ldap_get_attributes($this->ldapConnection, $entry);
        if (false === $attributes) {
            throw new VootStorageException("ldap_error", "unable to get group attributes");
        }

        $data = array();
        if (array_key_exists($memberAttribute, $attributes)) {
            // we have some members
            for ($i = 0; $i < $attributes[$memberAttribute]["count"]; $i++) {
                // member DN
                // fetch attributes for this particular user
                $userAttributes = $this->getUserAttributesByDn($attributes[$memberAttribute][$i]);
                $userAttributes['voot_membership_role'] = "member";
                array_push($data, $userAttributes);
            }
        }

        // backwards compatible "emails" element with array
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]["emails"] = array($data[$i]['mail']);
        }

        return array(
            'startIndex' => 0,
            'totalResults' => count($data),
            'itemsPerPage' => count($data),
            'entry' => $data
        );
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null)
    {
        $userDn = $this->getUserDn($resourceOwnerId);

        $userGroups = array();
        /* get the groups the user is a member of */
        $filter = '(' . $this->config->s('LdapVootStorage')->l('memberAttribute') . '=' . $userDn . ')';
        $query = @ldap_search($this->ldapConnection, $this->config->s('LdapVootStorage')->l('groupDn'), $filter);
        if (false === $query) {
            throw new VootStorageException("ldap_error", "directory query for groups failed");
        }

        $entry = @ldap_first_entry($this->ldapConnection, $query);
        while (false !== $entry) {
            $attributes = @ldap_get_attributes($this->ldapConnection, $entry);
            if (false === $attributes) {
                throw new VootStorageException("ldap_error", "unable to get group attributes");
            }
            $commonName = array_key_exists("cn", $attributes) ? $attributes["cn"][0] : null;
            $displayName = array_key_exists("displayName", $attributes) ? $attributes["displayName"][0] : null;
            $description = array_key_exists("description", $attributes) ? $attributes["description"][0] : null;
            $distinguishedName = @ldap_get_dn($this->ldapConnection, $entry);
            if (false === $distinguishedName) {
                throw new VootStorageException("ldap_error", "unable to get distinguishedName");
            }
            if (null === $commonName) {
                throw new VootStorageException("ldap_error", "no cn for group");
            }
            $a = array();
            $a['id'] = $commonName;
            $a['title'] = null !== $displayName ? $displayName : $commonName;
            if (null !== $description) {
                $a['description'] = $description;
            }
            $a['voot_membership_role'] = 'member';
            array_push($userGroups, $a);
            $entry = @ldap_next_entry($this->ldapConnection, $entry);
        }

        // FIXME: we need to implement paging for LDAP as well...
        $startIndex = 0;
        $totalResults = sizeof($userGroups);

        return array(
            'startIndex' => $startIndex,
            'totalResults' => $totalResults,
            'itemsPerPage' => $totalResults,
            'entry' => $userGroups
        );
    }

    private function filterAttributes($attributes)
    {
        $attributeMapping = $this->config->s('LdapVootStorage')->s('attributeMapping')->toArray();
        $filteredAttributes = array();
        foreach ($attributeMapping as $k => $v) {
            if (array_key_exists($v, $attributes)) {
                $filteredAttributes[$k] = $attributes[$v][0];
            }
        }
        if (!array_key_exists("id", $filteredAttributes)) {
            throw new VootStorageException(
                "ldap_error",
                "mapping for 'id' attribute not set in LDAP response"
            );
        }

        return $filteredAttributes;
    }
}
