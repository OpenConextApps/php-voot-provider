<?php

class DummyResourceOwner implements IResourceOwner {

    private $_c;

    public function __construct(Config $c) {
        $this->_c = $c;
    }

    public function setHint($resourceOwnerIdHint = NULL) {
    }

    public function getResourceOwnerId() {
        return $this->_c->getSectionValue('DummyResourceOwner', 'resourceOwnerId');
    }

    public function getResourceOwnerDisplayName() {
        return $this->_c->getSectionValue('DummyResourceOwner', 'resourceOwnerDisplayName');
    }

}

?>
