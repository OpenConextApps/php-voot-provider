<?php 

interface IResourceOwner {
    public function getResourceOwnerId         ();
    public function getResourceOwnerDisplayName();
}

interface IOAuthStorage {
    public function storeAccessToken      ($accessToken, $issueTime, $clientId, $resourceOwnerId, $resourceOwnerDisplayName, $scope, $expiry);
    public function getAccessToken        ($accessToken);
    public function storeAuthorizationCode($authorizationCode, $issueTime, $clientId, $redirectUri, $accessToken);
    public function getAuthorizationCode  ($authorizationCode, $redirectUri);
    public function deleteAuthorizationCode($authorizationCode, $redirectUri);

    public function getClients            ();
    public function getClient             ($clientId);
    public function getClientByRedirectUri($redirectUri);
    public function addClient             ($data);
    public function updateClient          ($clientId, $data);
    public function deleteClient          ($clientId);

    public function getApprovals          ($resourceOwnerId);
    public function getApproval           ($clientId, $resourceOwnerId);
    public function addApproval           ($clientId, $resourceOwnerId, $scope);
    public function updateApproval        ($clientId, $resourceOwnerId, $scope);
    public function deleteApproval        ($clientId, $resourceOwnerId);

}

/**
 * Thrown when the resource owner needs to be  informed of an error
 */
class ResourceOwnerException extends Exception {

}

/**
 * Thrown when the verification of the access token fails
 */
class VerifyException extends Exception {

}

/**
 * Thrown when interaction with the token endpoint fails
 * https://tools.ietf.org/html/draft-ietf-oauth-v2-26#section-5.2
 */
class TokenException extends Exception {

}

/**
 * Thrown when the client needs to be informed of an error
 */
class ClientException extends Exception {

    private $_description;
    private $_client;
    private $_state;

    public function __construct($message, $description, $client, $state, $code = 0, Exception $previous = null) {
        $this->_description = $description;
        $this->_client = $client;
        $this->_state = $state;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription() {
        return $this->_description;
    }

    public function getClient() {
        return $this->_client;
    }

    public function getState() {
        return $this->_state;
    }

}

/**
 * When something went wrong with storing or retrieving 
 * something storage
 */
class StorageException extends Exception {

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
            throw new ResourceOwnerException('client_id missing');
        }

        $clientIdLength = strlen($clientId);
        if(NULL === $clientIdLength || $clientIdLength < 1 || $clientIdLength > 64) {
            throw new ResourceOwnerException('client_id length exceeded');
        }

        if(NULL === $responseType) {
            throw new ResourceOwnerException('response_type missing');
        }

        $client = $this->_storage->getClient($clientId);
        if(FALSE === $client) {
            if(!$this->_c->getValue('allowUnregisteredClients')) {
                throw new ResourceOwnerException('client not registered');
            }
            // we need a redirectUri for unregistered clients
            if(NULL === $redirectUri) {
                throw new ResourceOwnerException('redirect_uri required for unregistered clients');
            }
            // validate the redirectUri
            $u = filter_var($redirectUri, FILTER_VALIDATE_URL);
            if(FALSE === $u) {
                throw new ResourceOwnerException("redirect_uri is malformed");
            }
            // redirectUri MUST NOT contain fragment
            $fragment = parse_url($redirectUri, PHP_URL_FRAGMENT);
            if($fragment !== NULL) {
                throw new ResourceOwnerException("redirect_uri must not contain fragment");
            }
            // clientId MUST be hostname of redirect_uri
            $host = parse_url($redirectUri, PHP_URL_HOST);
            if($host !== $clientId) {
                throw new ResourceOwnerException("client_id should match with hostname of redirect_uri");
            }
            $client = (object) array ("id" => $host, "name" => $host, "description" => "UNREGISTERED APPLICATION", "type" => "user_agent_based_application", "redirect_uri" => $redirectUri);
        }

        if(NULL !== $redirectUri) {
            if($client->redirect_uri !== $redirectUri) {
                throw new ResourceOwnerException('specified redirect_uri not the same as registered redirect_uri');
            }
        }

        // we need to make sure the client can only request the grant types belonging to its profile
        $allowedClientProfiles = array ( "web_application" => array ("code"),
                                         "native_application" => array ("token", "code"),
                                         "user_agent_based_application" => array ("token"));

        if(!in_array($responseType, $allowedClientProfiles[$client->type])) {

            throw new ClientException("unsupported_response_type", "response_type not supported by client profile", $client, $state);
        }

        $requestedScope = self::normalizeScope($scope);

        if(FALSE === $requestedScope) {
            throw new ClientException("invalid_scope", "malformed scope", $client, $state);
        }

        if(!$this->_c->getValue('allowAllScopes')) {
            if(FALSE === self::isSubsetScope($requestedScope, $this->_c->getValue('supportedScopes'))) {
                // scope not supported
                throw new ClientException("invalid_scope", "scope not supported", $client, $state);
            }
        }

        if(in_array('oauth_admin', self::getScopeArray($requestedScope))) {
            // administrator scope requested, need to be in admin list
            if(!in_array($resourceOwner->getResourceOwnerId(), $this->_c->getValue('adminResourceOwnerId'))) {
                throw new ClientException("invalid_scope", "scope not supported: resource owner is not an administrator", $client, $state);
            }
        }
   
        $approvedScope = $this->_storage->getApproval($clientId, $resourceOwner->getResourceOwnerId(), $requestedScope);
        if(FALSE === $approvedScope || FALSE === self::isSubsetScope($requestedScope, $approvedScope->scope)) {
            return array ("action" => "ask_approval", "client" => $client);
        } else {
            // approval already exists for this scope
            $accessToken = self::randomHex(16);
            $this->_storage->storeAccessToken($accessToken, time(), $clientId, $resourceOwner->getResourceOwnerId(), $resourceOwner->getResourceOwnerDisplayName(), $requestedScope, $this->_c->getValue('accessTokenExpiry'));

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
                $authorizationCode = self::randomHex(16);
                $this->_storage->storeAuthorizationCode($authorizationCode, time(), $clientId, $redirectUri, $accessToken);
                $token = array("code" => $authorizationCode);
                if(NULL !== $state) {
                    $token += array ("state" => $state);
                }
                return array("action" => "redirect", "url" => $client->redirect_uri . "?" . http_build_query($token));
            }
        }
    }

    public function approve(IResourceOwner $resourceOwner, array $get, array $post) {
        $clientId     = self::getParameter($get, 'client_id');
        $responseType = self::getParameter($get, 'response_type');
        $redirectUri  = self::getParameter($get, 'redirect_uri');
        $scope        = self::normalizeScope(self::getParameter($get, 'scope'));
        $state        = self::getParameter($get, 'state');

        $result = $this->authorize($resourceOwner, $get);
        $postScope = self::normalizeScope(self::getParameter($post, 'scope'));
        $approval = self::getParameter($post, 'approval');

        if($result['action'] !== "ask_approval") {
            return $result;
        }

        if("Approve" === $approval) {
            if(FALSE === self::isSubsetScope($postScope, $scope)) {
                // FIXME: should this actually be an authorize exception? this is a user error!
                throw new ClientException("invalid_scope", "approved scope is not a subset of requested scope", $client, $state);
            }

            $approvedScope = $this->_storage->getApproval($clientId, $resourceOwner->getResourceOwnerId());
            if(FALSE === $approvedScope) {
                // no approved scope stored yet, new entry
                $this->_storage->addApproval($clientId, $resourceOwner->getResourceOwnerId(), $postScope);
            } else if(!self::isSubsetScope($postScope, $approvedScope->scope)) {
                // not a subset, merge and store the new one
                $mergedScopes = self::mergeScopes($postScope, $approvedScope->scope);
                $this->_storage->updateApproval($clientId, $resourceOwner->getResourceOwnerId(), $mergedScopes);
            } else {
                // subset, approval for superset of scope already exists, do nothing
            }
            $get['scope'] = $postScope;
            return $this->authorize($resourceOwner, $get);

        } else {
            $client = $this->_storage->getClient($clientId);
            throw new ClientException("access_denied", "not authorized by resource owner", $client, $state);
        }
    }

    public function token(array $post, $authorizationHeader) {
        // exchange authorization code for access token
        $grantType   = self::getParameter($post, 'grant_type');
        $code        = self::getParameter($post, 'code');
        $redirectUri = self::getParameter($post, 'redirect_uri');

        if(NULL === $grantType) {
            throw new TokenException("invalid_request: the grant_type parameter is missing");
        }
        if("authorization_code" !== $grantType) {
            throw new TokenException("unsupported_grant_type: the requested grant type is not supported");
        }
        if(NULL === $code) {
            throw new TokenException("invalid_request: the code parameter is missing");
        }
        $result = $this->_storage->getAuthorizationCode($code, $redirectUri);
        if(FALSE === $result) {
            throw new TokenException("invalid_grant: the authorization code was not found");
        }
        if(time() > $result->issue_time + 600) {
            throw new TokenException("invalid_grant: the authorization code expired");
        }

        $client = $this->_storage->getClient($result->client_id);
        if("user_agent_based_application" === $client->type) {
            throw new TokenException("unauthorized_client: this client type is not allowed to use the token endpoint");
        }
        if("web_application" === $client->type) {
            // REQUIRE basic auth
            if(NULL === $authorizationHeader || empty($authorizationHeader)) {
                throw new TokenException("invalid_client: this client requires authentication");
            }
            if(FALSE === self::_verifyBasicAuth($authorizationHeader, $client)) {
                throw new TokenException("invalid_client: client authentication failed");
            }
        }
        if("native_application" === $client->type) {
            // MAY use basic auth, so only check when Authorization header is provided
            if(NULL !== $authorizationHeader && !empty($authorizationHeader)) {
                if(FALSE === self::_verifyBasicAuth($authorizationHeader, $client)) {
                    throw new TokenException("invalid_client: client authentication failed");
                }
            }
        }
        // we need to be able to delete, otherwise someone else was first!
        if(FALSE === $this->_storage->deleteAuthorizationCode($code, $redirectUri)) {
            throw new TokenException("invalid_grant: this grant was already used");
        }
        return $this->_storage->getAccessToken($result->access_token);
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

    private static function _verifyBasicAuth($authorizationHeader, $client) {
        // b64token = 1*( ALPHA / DIGIT / "-" / "." / "_" / "~" / "+" / "/" ) *"="
        // FIXME: basic is more restrictive than Bearer?
        $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
        $result = preg_match('|^Basic (?P<value>' . $b64TokenRegExp . ')$|', $authorizationHeader, $matches);
        if($result === FALSE || $result === 0) {
            return FALSE;
        }
        $basicAuth = $matches['value'];
        $decodedBasicAuth = base64_decode($basicAuth, TRUE);
        $colonPosition = strpos($decodedBasicAuth, ":");
        if ($colonPosition === FALSE || $colonPosition === 0 || $colonPosition + 1 === strlen($decodedBasicAuth)) {
            return FALSE;
        }
        $u = substr($decodedBasicAuth, 0, $colonPosition);
        $p = substr($decodedBasicAuth, $colonPosition + 1);
        return ($u === $client->id && $p === $client->secret);
    }

    public static function getParameter(array $parameters, $key) {
        return (array_key_exists($key, $parameters) && !empty($parameters[$key])) ? $parameters[$key] : NULL;
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

   public static function randomHex($len = 16) {
        $randomString = bin2hex(openssl_random_pseudo_bytes($len, $strong));
        // @codeCoverageIgnoreStart
        if (FALSE === $strong) {
            throw new Exception("unable to securely generate random string");
        }
        // @codeCoverageIgnoreEnd
        return $randomString;
    }

}

?>
