<?php

class SspResourceOwner implements IResourceOwner {

    private $_sspPath;
    private $_authSource;
    private $_resourceOwnerAttributes;
    private $_resourceOwnerIdAttributeName;

    public function __construct() {
        $this->_sspPath = '/var/simplesamlphp/lib/_autoload.php';      // default simpleSAMLphp installation
        $this->_authSource = 'default-sp';
        $this->_resourceOwnerAttributes = array();
        $this->_resourceOwnerIdAttributeName = 'uid';
    }

    public function setPath($sspPath) {
        $this->_sspPath = $sspPath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
    }

    public function setAuthSource($authSource) {
        $this->_authSource = $authSource;
    }

    public function setResourceOwnerIdAttributeName($attributeName) {
        $this->_resourceOwnerIdAttributeName = $attributeName;
    }

    private function _performAuthentication() {
        if(!file_exists($this->_sspPath)) {
            throw new \Exception("invalid path to simpleSAMLphp");
        }
        require_once($this->_sspPath);
        $as = new \SimpleSAML_Auth_Simple($this->_authSource);
        $as->requireAuth();
        $this->_resourceOwnerAttributes = $as->getAttributes();
    }

    public function getResourceOwnerId() {
        // FIXME: really better error checking, maybe user is authenticated but
        // the attribute 'uid' is not available!
        if(!array_key_exists($this->_resourceOwnerIdAttributeName, $this->_resourceOwnerAttributes)) {
            $this->_performAuthentication();
        }
        return $this->_resourceOwnerAttributes[$this->_resourceOwnerIdAttributeName][0];
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
