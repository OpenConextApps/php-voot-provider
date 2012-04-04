<?php

class DummyResourceOwner implements IResourceOwner {

    private $_config;

    public function __construct(array $config) {
        $this->_config = $config;
    }

    public function getResourceOwnerId() {
        return $this->_config['resourceOwnerId'];
    }

    public function getResourceOwnerDisplayName() {
        return $this->_config['resourceOwnerDisplayName'];
    }

}

?>
