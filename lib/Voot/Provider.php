<?php

require_once "lib/Voot/IVootStorage.php";

class Provider {

    private $_storage;

    public function __construct(IVootStorage $storage) {
        $this->_storage = $storage;
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        return $this->_storage->getGroupMembers($resourceOwnerId, $groupId, $startIndex, $count);
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        return $this->_storage->isMemberOf($resourceOwnerId, $startIndex, $count);
    }

}
?>
