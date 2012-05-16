<?php

class BrowserIDResourceOwner implements IResourceOwner {

    private $_config;
    private $_verifier;

    public function __construct(Config $c) {
        $this->_c = $c;

        $bPath = $this->_c->getSectionValue('BrowserIDResourceOwner', 'browserIDPath') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'BrowserIDVerifier.php';
        if(!file_exists($bPath) || !is_file($bPath) || !is_readable($bPath)) {
            throw new Exception("invalid path to php-browserid");
        }
        require_once $bPath;

        $this->_verifier = new BrowserIDVerifier($this->_c->getSectionValue('BrowserIDResourceOwner', 'verifierAddress'));
    }

    public function getResourceOwnerId() {
        return $this->_verifier->authenticate();
    }

    public function getResourceOwnerDisplayName() {
        return $this->_verifier->authenticate();
    }

}
