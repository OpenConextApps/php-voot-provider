<?php

require_once 'ext/browserid/browserid.php';

class BrowserIDResourceOwner implements IResourceOwner {

    private $_config, $_verifier;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_verifier = new BrowserIDVerifier($config);
    }

    public function getResourceOwnerId() {
        $this->_verifier->requireAuth();
        $attributes = $this->_verifier->getAttributes();
        return $attributes[$this->_config['resourceOwnerIdAttributeName']];
    }

    public function getResourceOwnerDisplayName() {
        $this->_verifier->requireAuth();
        $attributes = $this->_verifier->getAttributes();
        return $attributes[$this->_config['resourceOwnerDisplayNameAttributeName']];
    }

}

?>
