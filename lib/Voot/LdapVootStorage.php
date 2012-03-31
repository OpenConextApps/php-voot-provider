<?php

class LdapVootStorage implements IVootStorage {

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        return FALSE;
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        return FALSE;
    }

}

?>
