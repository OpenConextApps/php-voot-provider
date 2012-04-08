<?php 

interface IResourceOwner {
    public function getResourceOwnerId         ();
    public function getResourceOwnerDisplayName();
}

interface IOAuthStorage {
    public function getClient             ($clientId);
    public function storeApprovedScope    ($clientId, $resourceOwner, $scope);
    public function getApprovedScope      ($clientId, $resourceOwner);
    public function generateAccessToken   ($clientId, $resourceOwner, $scope, $expiry);
    public function getAccessToken        ($accessToken);
    public function generateAuthorizeNonce($clientId, $resourceOwner, $scope);
    public function getAuthorizeNonce     ($clientId, $resourceOwner, $scope, $authorizeNonce);
}

class OAuthException extends Exception {

}

class AuthorizationServer {

    private $_storage;
    private $_config;

    public function __construct(IOAuthStorage $storage, array $config) {
        $this->_storage = $storage;
        $this->_config = $config;
    }
 
    public function authorize($resourceOwner, Slim_Http_Request $r) {
        if(NULL === $r->get('client_id')) {
            throw new OAuthException('client_id missing');
        }

        if(NULL === $r->get('response_type')) {
            throw new OAuthException('response_type missing');
        }

        $client = $this->_storage->getClient($r->get('client_id'));

        if(FALSE === $client) {
            throw new OAuthException('client not registered');
        }

        if(NULL !== $r->get('redirect_uri')) {
            if($client->redirect_uri !== $r->get('redirect_uri')) {
                throw new OAuthException('specified redirect_uri not the same as registered redirect_uri');
            }
        }

        if(NULL !== $r->get('response_type')) {
            if("token" !== $r->get('response_type')) {
                $error = array ( "error" => "unsupported_response_type", "error_description" => "response_type not supported");
                if(NULL !== $r->get('state')) {
                    $error += array ( "state" => $r->get('state'));
                }
                return array("action" => "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }

        $requestedScope = self::normalizeScope($r->get('scope'));

        if(FALSE === $requestedScope) {
            // malformed scope
            $error = array ( "error" => "invalid_scope", "error_description" => "malformed scope");
            if(NULL !== $r->get('state')) {
                $error += array ( "state" => $r->get('state'));
            }
            return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
        } else {
            if(FALSE === self::isSubsetScope($requestedScope, $this->_config['supportedScopes'])) {
                // scope not supported
                $error = array ( "error" => "invalid_scope", "error_description" => "scope not supported");
                if(NULL !== $r->get('state')) {
                    $error += array ( "state" => $r->get('state'));
                }
                return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }
   
        $approvedScope = $this->_storage->getApprovedScope($r->get('client_id'), $resourceOwner, $requestedScope);

        if(FALSE === $approvedScope || FALSE === self::isSubsetScope($requestedScope, $approvedScope->scope)) {
            // need to ask user, scope not yet approved
            $authorizeNonce = $this->_storage->generateAuthorizeNonce($r->get('client_id'), $resourceOwner, $requestedScope);
            return array ("action" => "ask_approval", "authorize_nonce" => $authorizeNonce);
        } else {
            // approval already exists for this scope
            $accessToken = $this->_storage->generateAccessToken($r->get('client_id'), $resourceOwner, $requestedScope, $this->_config['accessTokenExpiry']);
            $token = array("access_token" => $accessToken, 
                           "expires_in" => $this->_config['accessTokenExpiry'], 
                           "token_type" => "bearer", 
                           "scope" => $requestedScope);
            if(NULL !== $r->get('state')) {
                $token += array ("state" => $r->get('state'));
            }
            return array("action" => "redirect", "url" => $client->redirect_uri . "#" . http_build_query($token));
        }
    }

    // FIXME: clean this method up!
    public function approve($resourceOwner, Slim_Http_Request $r) {
        // FIXME: don't allow different scope in post, make sure what is shown is actually also posted!! deal with different scope in FORM post!
        // FIXME: make sure state is retained and can't be modified!

        $authorizeNonce = $this->_storage->getAuthorizeNonce($r->get('client_id'), $resourceOwner, $r->get('scope'), $r->post('authorize_nonce'));
        if(FALSE === $authorizeNonce) { 
            throw new Exception("authorize nonce was not found");
        }

        $client = $this->_storage->getClient($r->get('client_id'));

        if("Approve" === $r->post('approval')) {
            $this->_storage->storeApprovedScope($r->get('client_id'), $resourceOwner, $r->get('scope'));
            return $this->authorize($resourceOwner, $r);
        } else {
            $error = array ( "error" => "access_denied", "error_description" => "not authorized by resource owner");
            if(NULL !== $r->get('state')) {
                $error += array ( "state" => $r->get('state'));
            }
            return array("action" => "redirect_error", "url" => $client->redirect_uri . "#" . http_build_query($error));
        }
    }

    public function verify(Slim_Http_Request $r) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';

        // FIXME: only works on Apache!
        $headers = apache_request_headers();
        if(!array_key_exists("Authorization", $headers)) {
            throw new Exception("invalid_request: authorization header missing");
        }
        $authzHeader = $headers['Authorization'];
        
        $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $authzHeader, $matches);
        if($result === FALSE || $result === 0) {
            throw new Exception("invalid_token: the access token is malformed");
        }

        //$accessToken = base64_decode($matches['value'], TRUE);
        //if($accessToken === FALSE) {
        //    throw new Exception("invalid_token: the access token is malformed");
        //}
        $accessToken = $matches['value'];


        // FIXME: getAccessToken in Storage
        $token = $this->_storage->getAccessToken($accessToken);
        if($token === FALSE) {
            throw new Exception("invalid_token: the access token is invalid");
        }
        if(time() > $token->issue_time + $token->expires_in) {
            throw new Exception("invalid_token: the access token expired");
        }
        return $token;
    }

    public static function isValidScopeToken($scopeToTest) {
        // scope       = scope-token *( SP scope-token )
        // scope-token = 1*( %x21 / %x23-5B / %x5D-7E )
        $scopeToken = '(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+';
        $scope = '/^' . $scopeToken . '(?:\x20' . $scopeToken . ')*$/';
        $result = preg_match($scope, $scopeToTest);
		return $result === 1;
    }

    public static function getScopeArray($scopeToConvert) {
        return is_array($scopeToConvert) ? $scopeToConvert : explode(" ", $scopeToConvert);
    }

    public static function getScopeString($scopeToConvert) {
        return is_array($scopeToConvert) ? implode(" ", $scopeToConvert) : $scopeToConvert;
    }

    public static function normalizeScope($scopeToNormalize, $toArray = FALSE) {
        if(self::isValidScopeToken($scopeToNormalize)) {
            $a = self::getScopeArray($scopeToNormalize);
            sort($a, SORT_STRING);
            return $toArray ? $a : self::getScopeString($a);
        }
        return FALSE;
    }

    /**
     * Compares two scopes and returns true if $s is a subset of $t
     */
    public static function isSubsetScope($s, $t) {
        $u = self::getScopeArray($s);
        $v = self::getScopeArray($t);
        foreach($u as $i) {
            if(!in_array($i, $v)) {
                return FALSE;
            }
        }
        return TRUE;
    }

}

?>
