<?php

interface IVootStorage {

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null);
    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null);

}

?>
