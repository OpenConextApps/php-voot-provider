<?php

class SspResourceOwner implements IResourceOwner {

    private $_config;
    private $_resourceOwnerAttributes;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_config['sspPath'] .= DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
        if(!file_exists($this->_config['sspPath'])) {
            throw new Exception("invalid path to simpleSAMLphp");
        }
        require_once $this->_config['sspPath'];

        $this->_resourceOwnerAttributes = array();
    }

    private function _performAuthentication() {
        $as = new SimpleSAML_Auth_Simple($this->_config['authSource']);
        $as->requireAuth();
        $this->_resourceOwnerAttributes = $as->getAttributes();
    }

    public function getResourceOwnerId() {
        // FIXME: really better error checking, maybe user is authenticated but
        // the attribute 'uid' is not available!
        if(!array_key_exists($this->_config['resourceOwnerIdAttributeName'], $this->_resourceOwnerAttributes)) {
            $this->_performAuthentication();
        }
        return $this->_resourceOwnerAttributes[$this->_config['resourceOwnerIdAttributeName']][0];
    }

    public function getResourceOwnerDisplayName() {
        // FIXME: really better error checking, maybe user is authenticated but
        // the attribute 'dn' is not available!
        if(!array_key_exists('dn', $this->_resourceOwnerAttributes)) {
            $this->_performAuthentication();
        }
        return $this->_resourceOwnerAttributes['dn'][0];
    }

}

?>
