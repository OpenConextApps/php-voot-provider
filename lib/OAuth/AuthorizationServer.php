<?php 

interface IResourceOwner {
    public function getResourceOwnerId         ();
    public function getResourceOwnerDisplayName();
}

interface IOAuthStorage {

    // FIXME: the next three should probably be renamed to
    //        addApproval, updateApproval, getApproval
    //        to make them more in line with the getApprovals, deleteApproval
    public function storeApprovedScope       ($clientId, $resourceOwnerId, $scope);
    public function updateApprovedScope      ($clientId, $resourceOwnerId, $scope);
    public function getApprovedScope         ($clientId, $resourceOwnerId);

    public function generateAccessToken      ($clientId, $resourceOwnerId, $resourceOwnerDisplayName, $scope, $expiry);
    public function getAccessToken           ($accessToken);
    public function generateAuthorizeNonce   ($clientId, $resourceOwnerId, $responseType, $redirectUri, $scope, $state);
    public function generateAuthorizationCode($clientId, $redirectUri, $accessToken);

    public function getAuthorizeNonce        ($clientId, $resourceOwnerId, $scope, $authorizeNonce);
    public function getAuthorizationCode     ($authorizationCode, $redirectUri);

    public function getClient                ($clientId);
    public function getClientByRedirectUri   ($redirectUri);

    // management interface
    public function getClients               ();
    public function addClient                ($data);
    public function updateClient             ($clientId, $data);
    public function deleteClient             ($clientId);

    public function getApprovals             ($resourceOwnerId);
    public function deleteApproval           ($clientId, $resourceOwnerId);

}

/**
 * Exception thrown when the user instead of the client needs to be informed
 * of an error, i.e.: when the client identity cannot be confirmed or is not
 * valid
 */
class OAuthException extends Exception {

}

/**
 * Exception thrown when the verification of the access token fails
 */
class VerifyException extends Exception {

}

/**
 * When interaction with the token endpoint fails
 * https://tools.ietf.org/html/draft-ietf-oauth-v2-26#section-5.2
 */
class TokenException extends Exception {

}

class AuthorizationServer {

    private $_storage;
    private $_c;

    public function __construct(IOAuthStorage $storage, Config $c) {
        $this->_storage = $storage;
        $this->_c = $c;
    }
 
    public function authorize(IResourceOwner $resourceOwner, array $get) {
        $clientId     = self::getParameter($get, 'client_id');
        $responseType = self::getParameter($get, 'response_type');
        $redirectUri  = self::getParameter($get, 'redirect_uri');
        $scope        = self::normalizeScope(self::getParameter($get, 'scope'));
        $state        = self::getParameter($get, 'state');

        if(NULL === $clientId) {
            throw new OAuthException('client_id missing');
        }

        if(NULL === $responseType) {
            throw new OAuthException('response_type missing');
        }

        $client = $this->_storage->getClient($clientId);
        if(FALSE === $client) {
            if(!$this->_c->getValue('allowUnregisteredClients')) {
                throw new OAuthException('client not registered');
            }      

            // we need a redirectUri for unregistered clients
            if(NULL === $redirectUri) {
                throw new OAuthException('redirect_uri required for unregistered clients');
            }
            // validate the redirectUri
            $u = filter_var($redirectUri, FILTER_VALIDATE_URL);
            if(FALSE === $u) {
                throw new OAuthException("redirect_uri is malformed");
            }
            // redirectUri MUST NOT contain fragment (should not be possible to
            // introduce this using the browser...)
            $uriParts = parse_url($redirectUri);
            if(array_key_exists("fragment", $uriParts)) {
                throw new OAuthException("redirect_uri must not contain fragment");
            }

            // this client is unregistered and unregistered clients are allowed,
            // check for the client using its redirect_uri as client_id
            $client = $this->_storage->getClientByRedirectUri($redirectUri);
            if(FALSE === $client) { 
                // create a new one
                $newClient = array ( 'name' => $uriParts['host'],
                                     'description' => "UNREGISTERED (" . $uriParts['host'] . ")",
                                     'redirect_uri' => $redirectUri,
                                     'type' => 'user_agent_based_application');
                if(FALSE === $this->_storage->addClient($newClient)) {
                    throw new OAuthException('unable to dynamically register client');
                }
                $client = $this->_storage->getClientByRedirectUri($redirectUri);
            }
        }

        if(NULL !== $redirectUri) {
            if($client->redirect_uri !== $redirectUri) {
                throw new OAuthException('specified redirect_uri not the same as registered redirect_uri');
            }
        }

        if(NULL !== $responseType) {
            if("token" !== $responseType && "code" !== $responseType) {
                $error = array ( "error" => "unsupported_response_type", "error_description" => "response_type not supported");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                // FIXME: how to know how to return the error? either token or code type?
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
        }

        if(!$this->_c->getValue('allowAllScopes')) {
            if(FALSE === self::isSubsetScope($requestedScope, $this->_c->getValue('supportedScopes'))) {
                // scope not supported
                $error = array ( "error" => "invalid_scope", "error_description" => "scope not supported");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }

        if(in_array('oauth_admin', self::getScopeArray($requestedScope))) {
            // administrator scope requested, need to be in admin list
            if(!in_array($resourceOwner->getResourceOwnerId(), $this->_c->getValue('adminResourceOwnerId'))) {
                $error = array ( "error" => "invalid_scope", "error_description" => "scope not supported resource owner is not an administrator");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }
   
        $approvedScope = $this->_storage->getApprovedScope($clientId, $resourceOwner->getResourceOwnerId(), $requestedScope);

        if(FALSE === $approvedScope || FALSE === self::isSubsetScope($requestedScope, $approvedScope->scope)) {
            // need to ask user, scope not yet approved
            $authorizeNonce = $this->_storage->generateAuthorizeNonce($clientId, $resourceOwner->getResourceOwnerId(), $responseType, $redirectUri, $scope, $state);
            return array ("action" => "ask_approval", "authorize_nonce" => $authorizeNonce);
        } else {
            // approval already exists for this scope
            $accessToken = $this->_storage->generateAccessToken($clientId, $resourceOwner->getResourceOwnerId(), $resourceOwner->getResourceOwnerDisplayName(), $requestedScope, $this->_c->getValue('accessTokenExpiry'));

            if("token" === $responseType) {
                // implicit grant
                $token = array("access_token" => $accessToken, 
                               "expires_in" => $this->_c->getValue('accessTokenExpiry'), 
                               "token_type" => "bearer", 
                               "scope" => $requestedScope);
                if(NULL !== $state) {
                    $token += array ("state" => $state);
                }
                return array("action" => "redirect", "url" => $client->redirect_uri . "#" . http_build_query($token));
            } else {
                // authorization code grant

                // we already generated an access_token, and we register this together with the authorization code
                $authorizationCode = $this->_storage->generateAuthorizationCode($clientId, $redirectUri, $accessToken);
                $token = array("code" => $authorizationCode);
                if(NULL !== $state) {
                    $token += array ("state" => $state);
                }
                return array("action" => "redirect", "url" => $client->redirect_uri . "?" . http_build_query($token));
            }
        }
    }

    public function approve(IResourceOwner $resourceOwner, array $get, array $post) {
        $clientId       = self::getParameter($get, 'client_id');
        $responseType   = self::getParameter($get, 'response_type');
        $redirectUri    = self::getParameter($get, 'redirect_uri');
        $scope          = self::normalizeScope(self::getParameter($get, 'scope'));
        $state          = self::getParameter($get, 'state');

        $authorizeNonce = self::getParameter($post, 'authorize_nonce');
        $postScope      = self::normalizeScope(self::getParameter($post, 'scope'));
        $approval       = self::getParameter($post, 'approval');

        // FIXME: normalizeScope returns FALSE if it is a broken scope, do something
        //        with this...
        // FIXME: we should add all parameters from above to the 
        //        getAuthorizeNonce check, also responseType, redirectUri, state...
        if(FALSE === $this->_storage->getAuthorizeNonce($clientId, $resourceOwner->getResourceOwnerId(), $scope, $authorizeNonce)) {
            throw new Exception("authorize nonce was not found");
        }

        $client = $this->_storage->getClient($clientId);
        if(FALSE === $client) {
            if(!$this->_c->getValue('allowUnregisteredClients')) {
                throw new OAuthException('client not registered');
            }
            // this client is unregistered and unregistered clients are allowed,
            // check for the client using its redirect_uri as client_id
            $client = $this->_storage->getClientByRedirectUri($redirectUri);
            if(FALSE === $client) { 
                throw new OAuthException('client not registered');
            }
        }

        if("Approve" === $approval) {
            if(FALSE === self::isSubsetScope($postScope, $scope)) {
                $error = array ( "error" => "invalid_scope", "error_description" => "approved scope is not a subset of requested scope");
                if(NULL !== $state) {
                    $error += array ( "state" => $state);
                }
                return array("action" => "redirect_error", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }

            $approvedScope = $this->_storage->getApprovedScope($clientId, $resourceOwner->getResourceOwnerId());
            if(FALSE === $approvedScope) {
                // no approved scope stored yet, new entry
                $this->_storage->storeApprovedScope($clientId, $resourceOwner->getResourceOwnerId(), $postScope);
            } else if(!self::isSubsetScope($postScope, $approvedScope->scope)) {
                // not a subset, merge and store the new one
                $mergedScopes = self::mergeScopes($postScope, $approvedScope->scope);
                $this->_storage->updateApprovedScope($clientId, $resourceOwner->getResourceOwnerId(), $mergedScopes);
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

    public function token(array $post) {
        // exchange authorization code for access token
        $grantType   = self::getParameter($post, 'grant_type');
        $code        = self::getParameter($post, 'code');
        $redirectUri = self::getParameter($post, 'redirect_uri');

        if(NULL === $grantType) {
            throw new TokenException("invalid_request: the grant_type parameter is missing");
        }
        if("code" !== $grantType) {
            throw new TokenException("unsupported_grant_type: the requested grant type is not supported");
        }
        if(NULL === $code) {
            throw new TokenException("invalid_request: the code parameter is missing");
        }
        $result = $this->_storage->getAuthorizationCode($code, $redirectUri);
        if(FALSE === $result) {
            throw new TokenException("invalid_request: some as of yet undetermined error occurred");
        }
        return $result;
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

    public static function getScopeArray($scopeToConvert) {
        return is_array($scopeToConvert) ? $scopeToConvert : explode(" ", $scopeToConvert);
    }

    public static function getScopeString($scopeToConvert) {
        return is_array($scopeToConvert) ? implode(" ", $scopeToConvert) : $scopeToConvert;
    }

    public static function normalizeScope($scopeToNormalize, $toArray = FALSE) {
        $scopeToNormalize = self::getScopeString($scopeToNormalize);
        if(self::_isValidScopeToken($scopeToNormalize)) {
            $a = self::getScopeArray($scopeToNormalize);
            sort($a, SORT_STRING);
            $a = array_unique($a, SORT_STRING);
            return $toArray ? $a : self::getScopeString($a);
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
