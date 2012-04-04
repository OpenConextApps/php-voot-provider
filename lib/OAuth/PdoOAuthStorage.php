<?php

class PdoOAuthStorage implements IOAuthStorage {

    private $_config;
    private $_pdo;

    public function __construct(array $config) {
        $this->_config = $config;
        $this->_pdo = new PDO($this->_config['dsn']);
    }

    public function getClient($clientId) {
        $stmt = $this->_pdo->prepare("SELECT * FROM Client WHERE id = :client_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function storeApprovedScope($clientId, $resourceOwner, $scope) {
        $stmt = $this->_pdo->prepare("INSERT INTO Approval (client_id, resource_owner_id, scope) VALUES(:client_id, :resource_owner_id, :scope)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwner, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getApprovedScope($clientId, $resourceOwner, $scope) {
        $stmt = $this->_pdo->prepare("SELECT * FROM Approval WHERE client_id = :client_id AND resource_owner_id = :resource_owner_id AND scope = :scope");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwner, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function generateAccessToken($clientId, $resourceOwner, $scope) {
        $accessToken = $this->_randomHex(16);
        $stmt = $this->_pdo->prepare("INSERT INTO AccessToken (client_id, resource_owner_id, issue_time, expires_in, scope, access_token) VALUES(:client_id, :resource_owner_id, :issue_time, :expires_in, :scope, :access_token)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwner, PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", time(), PDO::PARAM_INT);
        $stmt->bindValue(":expires_in", 3600, PDO::PARAM_INT);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        return ($stmt->execute()) ? $accessToken : FALSE;
    }

    public function getAccessToken($accessToken) {
        $stmt = $this->_pdo->prepare("SELECT * FROM AccessToken WHERE access_token = :access_token");
        $stmt->bindValue(":access_token", $accessToken, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        return $stmt->fetch(PDO::FETCH_OBJ);        
    }

    public function generateAuthorizeNonce($clientId, $resourceOwner, $scope) {
        $authorizeNonce = $this->_randomHex(16);
        $stmt = $this->_pdo->prepare("INSERT INTO AuthorizeNonce (client_id, resource_owner_id, scope, authorize_nonce) VALUES(:client_id, :resource_owner_id, :scope, :authorize_nonce)");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwner, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":authorize_nonce", $authorizeNonce, PDO::PARAM_STR);
        return ($stmt->execute()) ? $authorizeNonce : FALSE;
    }

    public function getAuthorizeNonce($clientId, $resourceOwner, $scope, $authorizeNonce) {
        $stmt = $this->_pdo->prepare("DELETE FROM AuthorizeNonce WHERE client_id = :client_id AND scope = :scope AND authorize_nonce = :authorize_nonce AND resource_owner_id = :resource_owner_id");
        $stmt->bindValue(":client_id", $clientId, PDO::PARAM_STR);
        $stmt->bindValue(":resource_owner_id", $resourceOwner, PDO::PARAM_STR);
        $stmt->bindValue(":scope", $scope, PDO::PARAM_STR);
        $stmt->bindValue(":authorize_nonce", $authorizeNonce, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        return 1 === $stmt->rowCount();
    }

    private function _randomHex($len = 16) {
        $randomString = bin2hex(openssl_random_pseudo_bytes($len, $strong));
        // @codeCoverageIgnoreStart
        if ($strong === FALSE) {
            throw new Exception("unable to securely generate random string");
        }
        // @codeCoverageIgnoreEnd
        return $randomString;
    }

}

?>
