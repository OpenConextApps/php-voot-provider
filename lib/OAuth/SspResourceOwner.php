<?php

class SspResourceOwner implements IResourceOwner {

    private $_c;
    private $_ssp;

    public function __construct(Config $c) {
        $this->_c = $c;
        $sspPath = $this->_c->getSectionValue('SspResourceOwner', 'sspPath') . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
        if(!file_exists($sspPath) || !is_file($sspPath) || !is_readable($sspPath)) {
            throw new ResourceOwnerException("invalid path to simpleSAMLphp");
        }
        require_once $sspPath;

        $this->_ssp = new SimpleSAML_Auth_Simple($this->_c->getSectionValue('SspResourceOwner', 'authSource'));
    }

    public function setHint($resourceOwnerIdHint = NULL) {
    }

    public function getResourceOwnerId() {
        $this->_ssp->requireAuth();
        $attributes = $this->_ssp->getAttributes();
        if(!array_key_exists($this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerIdAttributeName'), $attributes)) {
            throw new ResourceOwnerException("resourceOwnerIdAttributeName is not available in SAML attributes");
        }
        return $attributes[$this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerIdAttributeName')][0];
    }

    public function getResourceOwnerDisplayName() {
        $this->_ssp->requireAuth();
        $attributes = $this->_ssp->getAttributes();
        if(!array_key_exists($this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerDisplayNameAttributeName'), $attributes)) {
            throw new ResourceOwnerException("resourceOwnerDisplayNameAttributeName is not available in SAML attributes");
        }
        return $attributes[$this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerDisplayNameAttributeName')][0];
    }

}

?>
