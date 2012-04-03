<?php

class DummyResourceOwner implements IResourceOwner {

    private $_resourceOwnerId;
    private $_resourceOwnerDisplayName;

    public function __construct($resourceOwnerId = "johndoe", $resourceOwnerDisplayName = "John Doe") {
        $this->_resourceOwnerId = $resourceOwnerId;
        $this->_resourceOwnerDisplayName = $resourceOwnerDisplayName;
    }

    public function getResourceOwnerId() {
        return $this->_resourceOwnerId;
    }

    public function getResourceOwnerDisplayName() {
        $this->_resourceOwnerDisplayName;
    }

}

?>
