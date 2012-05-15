<?php

require_once 'ext/browserid-session/browserid.php';

class BrowserIDResourceOwner implements IResourceOwner {

    private $_config;
    private $_verifier;

    public function __construct(Config $c) {
        $this->_c = $c;
        $this->_verifier = new BrowserIDVerifier();
    }

    public function getResourceOwnerId() {
        $this->_verifier->requireAuth();
        $attributes = $this->_verifier->getAttributes();
        return $attributes[$this->_c->getSectionValue('BrowserIDResourceOwner', 'resourceOwnerIdAttributeName')];
    }

    public function getResourceOwnerDisplayName() {
        $this->_verifier->requireAuth();
        $attributes = $this->_verifier->getAttributes();
        return $attributes[$this->_c->getSectionValue('BrowserIDResourceOwner', 'resourceOwnerDisplayNameAttributeName')];
    }

}

?>
