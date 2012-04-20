<?php 

interface IResourceOwner {
    public function getResourceOwnerId         ();
    public function getResourceOwnerDisplayName();
}

interface IOAuthStorage {
    public function storeApprovedScope    ($clientId, $resourceOwner, $scope);
    public function updateApprovedScope   ($clientId, $resourceOwner, $scope);

    public function getApprovedScope      ($clientId, $resourceOwner);
    public function generateAccessToken   ($clientId, $resourceOwner, $scope, $expiry);
    public function getAccessToken        ($accessToken);
    public function generateAuthorizeNonce($clientId, $resourceOwner, $scope);
    public function getAuthorizeNonce     ($clientId, $resourceOwner, $scope, $authorizeNonce);

    public function getClient             ($clientId);

    // management interface
    public function getClients            ();
    public function addClient             ($data);
    public function updateClient          ($clientId, $data);
    public function deleteClient          ($clientId);

    public function getApprovals          ($resourceOwner);
    public function deleteApproval        ($clientId, $resourceOwner, $scope);

}

class OAuthException extends Exception {

}

class VerifyException extends Exception {

}

class AdminException extends Exception {

}

class AuthorizationServer {

    private $_storage;
    private $_config;

    public function __construct(IOAuthStorage $storage, array $config) {
        $this->_storage = $storage;
        $this->_config = $config;
    }
 
    public function authorize($resourceOwner, array $get) {
        $clientId     = self::getParameter($get, 'client_id');
        $responseType = self::getParameter($get, 'response_type');
        $redirectUri  = self::getParameter($get, 'redirect_uri');
        $scope        = self::getParameter($get, 'scope');
        $state        = self::getParameter($get, 'state');

        if(NULL === $clientId) {
            throw new OAuthException('client_id missing');
        }

        if(NULL === $responseType) {
            throw new OAuthException('response_type missing');
        }

        $client = $this->_storage->getClient($clientId);

        if(FALSE === $client) {
            throw new OAuthException('client not registered');
        }

        if(NULL !== $redirectUri) {
            if($client->redirect_uri !== $redirectUri) {
                throw new OAuthException('specified redirect_uri not the same as registered redirect_uri');
            }
        }

        if(NULL !== $responseType) {
            if("token" !== $responseType) {
                $error = array ( "error" => "unsupported_response_type", "error_description" => "response_type not supported");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                return array("action" => "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }

        $requestedScope = self::normalizeScope($scope);

        if(FALSE === $requestedScope) {
            // malformed scope
            $error = array ( "error" => "invalid_scope", "error_description" => "malformed scope");
            if(NULL !== $state) {
                $error += array ( "state" => $state);
            }
            return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
        } else {
            if(FALSE === self::isSubsetScope($requestedScope, $this->_config['supportedScopes'])) {
                // scope not supported
                $error = array ( "error" => "invalid_scope", "error_description" => "scope not supported");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }
   
        $approvedScope = $this->_storage->getApprovedScope($clientId, $resourceOwner, $requestedScope);

        if(FALSE === $approvedScope || FALSE === self::isSubsetScope($requestedScope, $approvedScope->scope)) {
            // need to ask user, scope not yet approved
            $authorizeNonce = $this->_storage->generateAuthorizeNonce($clientId, $resourceOwner, $requestedScope);
            return array ("action" => "ask_approval", "authorize_nonce" => $authorizeNonce);
        } else {
            // approval already exists for this scope
            $accessToken = $this->_storage->generateAccessToken($clientId, $resourceOwner, $requestedScope, $this->_config['accessTokenExpiry']);
            $token = array("access_token" => $accessToken, 
                           "expires_in" => $this->_config['accessTokenExpiry'], 
                           "token_type" => "bearer", 
                           "scope" => $requestedScope);
            if(NULL !== $state) {
                $token += array ("state" => $state);
            }
            return array("action" => "redirect", "url" => $client->redirect_uri . "#" . http_build_query($token));
        }
    }

    public function approve($resourceOwner, array $get, array $post) {
        $clientId       = self::getParameter($get, 'client_id');
        $responseType   = self::getParameter($get, 'response_type');
        $redirectUri    = self::getParameter($get, 'redirect_uri');
        $scope          = self::getParameter($get, 'scope');
        $state          = self::getParameter($get, 'state');

        $authorizeNonce = self::getParameter($post, 'authorize_nonce');
        $postScope      = self::normalizeScope(self::getParameter($post, 'scope'));
        $approval       = self::getParameter($post, 'approval');

        if(FALSE === $this->_storage->getAuthorizeNonce($clientId, $resourceOwner, $scope, $authorizeNonce)) {
            throw new Exception("authorize nonce was not found");
        }

        $client = $this->_storage->getClient($clientId);

        if("Approve" === $approval) {
            if(FALSE === self::isSubsetScope($postScope, $scope)) {
                $error = array ( "error" => "invalid_scope", "error_description" => "approved scope is not a subset of requested scope");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                return array("action" => "redirect_error", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }

            $approvedScope = $this->_storage->getApprovedScope($clientId, $resourceOwner);
            if(FALSE === $approvedScope) {
                // no approved scope stored yet, new entry
                $this->_storage->storeApprovedScope($clientId, $resourceOwner, $postScope);
            } else if(!self::isSubsetScope($postScope, $approvedScope->scope)) {
                // not a subset, merge and store the new one
                $mergedScopes = self::mergeScopes($postScope, $approvedScope->scope);
                $this->_storage->updateApprovedScope($clientId, $resourceOwner, $mergedScopes);
            } else {
                // subset, approval for superset of scope already exists, do nothing
            }
            $get['scope'] = $postScope;
            return $this->authorize($resourceOwner, $get);

        } else {
            $error = array ( "error" => "access_denied", "error_description" => "not authorized by resource owner");
            if(NULL !== $state) {
                $error += array ( "state" => $state);
            }
            return array("action" => "redirect_error", "url" => $client->redirect_uri . "#" . http_build_query($error));
        }
    }

    public function verify($authorizationHeader) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';

        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if($result === FALSE || $result === 0) {
            throw new VerifyException("invalid_token: the access token is malformed");
        }
        $accessToken = $matches['value'];
        $token = $this->_storage->getAccessToken($accessToken);
        if($token === FALSE) {
            throw new VerifyException("invalid_token: the access token is invalid");
        }
        if(time() > $token->issue_time + $token->expires_in) {
            throw new VerifyException("invalid_token: the access token expired");
        }
        return $token;
    }

    public static function getParameter(array $parameters, $key) {
        return array_key_exists($key, $parameters) ? $parameters[$key] : NULL;
    }

    private static function _isValidScopeToken($scopeToTest) {
        // scope       = scope-token *( SP scope-token )
        // scope-token = 1*( %x21 / %x23-5B / %x5D-7E )
        $scopeToken = '(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+';
        $scope = '/^' . $scopeToken . '(?:\x20' . $scopeToken . ')*$/';
        $result = preg_match($scope, $scopeToTest);
		return $result === 1;
    }

    private static function _getScopeArray($scopeToConvert) {
        return is_array($scopeToConvert) ? $scopeToConvert : explode(" ", $scopeToConvert);
    }

    private static function _getScopeString($scopeToConvert) {
        return is_array($scopeToConvert) ? implode(" ", $scopeToConvert) : $scopeToConvert;
    }

    public static function normalizeScope($scopeToNormalize, $toArray = FALSE) {
        $scopeToNormalize = self::_getScopeString($scopeToNormalize);
        if(self::_isValidScopeToken($scopeToNormalize)) {
            $a = self::_getScopeArray($scopeToNormalize);
            sort($a, SORT_STRING);
            $a = array_unique($a, SORT_STRING);
            return $toArray ? $a : self::_getScopeString($a);
        }
        return FALSE;
    }

    /**
     * Compares two scopes and returns true if $s is a subset of $t
     */
    public static function isSubsetScope($s, $t) {
        $u = self::normalizeScope($s, TRUE);
        $v = self::normalizeScope($t, TRUE);
        foreach($u as $i) {
            if(!in_array($i, $v)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public static function mergeScopes($s, $t) {
        $u = self::normalizeScope($s, TRUE);
        $v = self::normalizeScope($t, TRUE);
        return self::normalizeScope(array_merge($u, $v));
    }
}

?>
