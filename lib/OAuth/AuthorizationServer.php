<?php 

class AuthorizationServer {

    private $_supportedScopes;
    private $_storage;
    private $_authentication;

    public function __construct(Storage $storage, $resourceOwner) {
        $this->_storage = $storage;
        $this->_resourceOwner = $resourceOwner;

        $this->_supportedScopes = array();
    }
 
    public function setSupportedScopes(array $supportedScopes) {
        $this->_supportedScopes = $supportedScopes;
    }

    public function getSupportedScopes() {
        return $this->_supportedScopes;
    }

    public function authorize(Slim_Http_Request $r) {
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
            if($r->get('response_type') !== 'token') {
                $error = array ( "error" => "unsupported_response_type", "error_description" => "response_type not supported");
                if(NULL !== $r->get('state')) {
                    $error += array ( "state" => $r->get('state'));
                }
                return array("action" => "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }

        if(NULL !== $r->get('scope')) {
            // scope       = scope-token *( SP scope-token )
            // scope-token = 1*( %x21 / %x23-5B / %x5D-7E )
            // FIXME: regexp fail? the first + should not be there?
	        $scopeRegExp = '/^(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+(?:\x20(?:\x21|[\x23-\x5B]|[\x5D-\x7E])+)*$/';
            $result = preg_match($scopeRegExp, $r->get('scope'));
    		if($result === 1) { 
                // valid scope
                $requestedScopeList = explode(" ", $r->get('scope'));
                foreach($requestedScopeList as $c) {
                    if(!in_array($c, $this->_supportedScopes)) {
                        $error = array ( "error" => "invalid_scope", "error_description" => "scope not supported");
                        if(NULL !== $r->get('state')) {
                            $error += array ( "state" => $r->get('state'));
                        }
                        return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
                    }
                }

            } else {
                $error = array ( "error" => "invalid_scope", "error_description" => "scope contains invalid characters");
                if(NULL !== $r->get('state')) {
                    $error += array ( "state" => $r->get('state'));
                }
                return array("action"=> "error_redirect", "url" => $client->redirect_uri . "#" . http_build_query($error));
            }
        }

        $approvedScope = $this->_storage->getApprovedScope($r->get('client_id'), $this->_resourceOwner);

        if(FALSE !== $approvedScope) {
            $requestedList = explode(" ", $r->get('scope'));
            $approvedScopeList = explode(" ", $approvedScope->scope);
            $alreadyApproved = TRUE;
            foreach($requestedList as $c) {
                if(!in_array($c, $approvedScopeList)) {
                    $alreadyApproved = FALSE;
                }
            }  
            if ($alreadyApproved) {
                $accessToken = $this->_storage->generateAccessToken($r->get('client_id'), $this->_resourceOwner, $r->get('scope'));
                $token = array("access_token" => $accessToken, "expires_in" => 3600, "token_type" => "bearer");
                if(NULL !== $r->get('scope')) {
                    $token += array ("scope" => $r->get('scope'));
                }
                if(NULL !== $r->get('state')) {
                    $token += array ("state" => $r->get('state'));
                }
                return array("action" => "redirect", "url" => $client->redirect_uri . "#" . http_build_query($token));
            }
        }

        $authorizeNonce = $this->_storage->generateAuthorizeNonce($r->get('client_id'), $this->_resourceOwner);
        return array ("action" => "ask_approval", "authorize_nonce" => $authorizeNonce);
    }

    public function approve(Slim_Http_Request $r) {
        // FIXME: don't allow different scope in post, make sure what is shown is actually also posted!! deal with different scope in FORM post!
        // FIXME: make sure state is retained and can't be modified!

        $authorizeNonce = $this->_storage->getAuthorizeNonce($r->get('client_id'), $this->_resourceOwner, $r->post('authorize_nonce'));
        if(FALSE === $authorizeNonce) { 
            throw new Exception("authorize nonce was not found");
        }

        $client = $this->_storage->getClient($r->get('client_id'));

        if("Approve" === $r->post('approval')) {
            $this->_storage->storeApprovedScope($r->get('client_id'), $this->_resourceOwner, $r->get('scope'));
            return $this->authorize($r);
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

}

class OAuthException extends Exception {

}

?>
