<?php

class SspResourceOwner implements IResourceOwner {

    private $_c;
    private $_ssp;

    public function __construct(Config $c) {
        $this->_c = $c;
        $this->_c->getSectionValue('SspResourceOwner', 'sspPath') .= DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '_autoload.php';
        if(!file_exists($this->_c->getSectionValue('SspResourceOwner', 'sspPath'))) {
            throw new Exception("invalid path to simpleSAMLphp");
        }
        require_once $this->_c->getSectionValue('SspResourceOwner', 'sspPath');

        $this->_ssp = new SimpleSAML_Auth_Simple($this->_c->getSectionValue('SspResourceOwner', 'authSource'));
    }

    public function getResourceOwnerId() {
        $this->_ssp->requireAuth();
        $attributes = $this->_ssp->getAttributes();
        if(!array_key_exists($this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerIdAttributeName'), $attributes)) {
            throw new Exception("resourceOwnerIdAttributeName is not available in SAML attributes");
        }
        return $attributes[$this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerIdAttributeName')][0];
    }

    public function getResourceOwnerDisplayName() {
        $this->_ssp->requireAuth();
        $attributes = $this->_ssp->getAttributes();
        if(!array_key_exists($this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerDisplayNameAttributeName'), $attributes)) {
            throw new Exception("resourceOwnerDisplayNameAttributeName is not available in SAML attributes");
        }
        return $attributes[$this->_c->getSectionValue('SspResourceOwner', 'resourceOwnerDisplayNameAttributeName')][0];
    }

}

?>
