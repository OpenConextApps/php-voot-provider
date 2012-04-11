<?php

class SspResourceOwner implements IResourceOwner {

    private $_config;
    private $_ssp;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_config['sspPath'] .= DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
        if(!file_exists($this->_config['sspPath'])) {
            throw new Exception("invalid path to simpleSAMLphp");
        }
        require_once $this->_config['sspPath'];

        $this->_ssp = new SimpleSAML_Auth_Simple($this->_config['authSource']);
    }

    private function _performAuthentication() {
        $this->_ssp->requireAuth();
        $this->_resourceOwnerAttributes = $as->getAttributes();
    }

    public function getResourceOwnerId() {
        $this->_ssp->requireAuth();
        $attributes = $as->getAttributes();
        if(!array_key_exists($this->_config['resourceOwnerIdAttributeName'], $attributes)) {
            throw new Exception("resourceOwnerIdAttributeName is not available in SAML attributes");
        }
        return $attributes[$this->_config['resourceOwnerIdAttributeName']][0];
    }

    public function getResourceOwnerDisplayName() {
        $this->_ssp->requireAuth();
        $attributes = $as->getAttributes();
        if(!array_key_exists($this->_config['resourceOwnerDisplayNameAttributeName'], $attributes)) {
            throw new Exception("resourceOwnerDisplayNameAttributeName is not available in SAML attributes");
        }
        return $attributes[$this->_config['resourceOwnerDisplayNameAttributeName']][0];
    }

}

?>
