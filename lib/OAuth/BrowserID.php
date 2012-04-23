<?php

class BrowserIDResourceOwner implements IResourceOwner {

    private $_config;

    public function __construct(array $config) {
        $this->_config = $config;
    }

    private static function verifyAssertion($assertion, $audience) {
        $url = $this->_config['verifier'];
        $params = 'assertion='.$assertion.'&audience=' . $audience;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        try {
            $resultObj = json_decode($result, true);
            return (isset($resultObj['email']) ? $resultObj['email'] : false);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getResourceOwnerId() {
        return $this->_config['resourceOwnerId'];
    }

    public function getResourceOwnerDisplayName() {
        return $this->getResourceOwnerId();
    }

}

?>
