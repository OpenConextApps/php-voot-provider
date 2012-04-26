<?php

require_once 'lib/OAuth/AuthorizationServer.php';

class SlimOAuth {

    private $_app;
    private $_oauthConfig;
    
    private $_oauthStorage;

    private $_resourceOwner;
    private $_as;

    public function __construct(Slim $app, array $oauthConfig) {
        $this->_app = $app;
        $this->_oauthConfig = $oauthConfig;

        $oauthStorageBackend = $this->_oauthConfig['OAuth']['storageBackend'];
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_oauthConfig[$oauthStorageBackend]);

        $this->_as = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);

        // in PHP 5.4 $this is possible inside anonymous functions.
        $self = &$this;

        $this->_app->get('/oauth/authorize', function () use ($self) {
            $self->authorize();
        });

        $this->_app->post('/oauth/authorize', function () use ($self) {
            $self->approve();
        });

        // management
        $this->_app->get('/oauth/approval', function () use ($self) {
            $self->getApprovals();
        });

        $this->_app->post('/oauth/approval', function () use ($self) {
            $self->addApproval();
        });

        $this->_app->delete('/oauth/approval/:client_id', function ($clientId) use ($self) {
            $self->deleteApproval($clientId);
        });


	    $this->_app->get('/oauth/whoami', function () use ($self) {
            $self->whoAmI();
        });

        $this->_app->get('/oauth/client/:client_id', function ($clientId) use ($self) {
            $self->getClient($clientId);
        });

        $this->_app->put('/oauth/client/:client_id', function ($clientId) use ($self) {
            $self->updateClient($clientId);
        });

        $this->_app->delete('/oauth/client/:client_id', function ($clientId) use ($self) {
            $self->deleteClient($clientId);
        });
        
        $this->_app->post('/oauth/client', function () use ($self) {
            $self->addClient();
        });

        $this->_app->get('/oauth/client', function () use ($self) {
            $self->getClients();
        });

        // error
        $this->_app->error(function(Exception $e) use ($self) {
            $self->errorHandler($e);
        });

    }

    private function _authenticate() {
        $authMech = $this->_oauthConfig['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_oauthConfig[$authMech]);
        $this->_resourceOwner = $ro->getResourceOwnerId();
    }

    // FIXME: this should probably not be here!
    public function getResourceOwner() {
        $this->_authenticate();
        return $this->_resourceOwner;
    }

    public function authorize() {
        $this->_authenticate();
        $result = $this->_as->authorize($this->_resourceOwner, $this->_app->request()->get());

        // we know that all request parameters we used below are acceptable because they were verified by the authorize method.
        // Do something with case where no scope is requested!
        if($result['action'] === 'ask_approval') { 
            $client = $this->_oauthStorage->getClient($this->_app->request()->get('client_id'));
            $this->_app->render('askAuthorization.php', array (
                'clientId' => $client->id,
                'clientName' => $client->name,
                'redirectUri' => $client->redirect_uri,
                'scope' => $this->_app->request()->get('scope'), 
                'authorizeNonce' => $result['authorize_nonce'],
                'protectedResourceDescription' => $this->_oauthConfig['OAuth']['protectedResourceDescription'],
                'allowFilter' => $this->_oauthConfig['OAuth']['allowResourceOwnerScopeFiltering']));
        } else {
            $this->_app->redirect($result['url']);
        }
    }

    public function approve() {
        $this->_authenticate();
        $result = $this->_as->approve($this->_resourceOwner, $this->_app->request()->get(), $this->_app->request()->post());
        $this->_app->redirect($result['url']);
    }

    // REST API
    public function whoAmI() {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        if(!in_array('oauth_whoami', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_whoami scope");
        }

        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode(array ("id" => $result->resource_owner_id)));
    }

    public function getClient($clientId) {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $result = $this->_oauthStorage->getClient($clientId);
        if(FALSE === $result) {
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    public function deleteClient($clientId) {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $result = $this->_oauthStorage->deleteClient($clientId);
        if(FALSE === $result) {
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    public function updateClient($clientId) {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $result = $this->_oauthStorage->updateClient($clientId, json_decode($this->_app->request()->getBody(), TRUE));
        if(FALSE === $result) {
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    public function addClient() {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $result = $this->_oauthStorage->addClient(json_decode($this->_app->request()->getBody(), TRUE));
        if(FALSE === $result) {
            $this->_app->halt(500);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    public function getClients() {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $result = $this->_oauthStorage->getClients();
        if(FALSE === $result) {
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    public function getApprovals() {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);
        // FIXME: all methods should have data with PDO data from OAuth and not "result"
        $approvals = $this->_oauthStorage->getApprovals($result->resource_owner_id);
        if(FALSE === $result) {
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($approvals));
    }

    public function deleteApproval($clientId) {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);
        $this->_oauthStorage->deleteApproval($clientId, $result->resource_owner_id);
    }

    public function addApproval() {
        $authorizationHeader = self::_getAuthorizationHeader();
        $result = $this->_as->verify($authorizationHeader);

        $data = json_decode($this->_app->request()->getBody(), TRUE);
        // FIXME: we should verify the client exists
        $clientId = $data['client_id'];
        // FIXME: we should verify the scope is valid and normalize it before
        //        storing it
        $scope = $data['scope'];

        $result = $this->_oauthStorage->storeApprovedScope($clientId, $result->resource_owner_id, $scope);
        if(FALSE === $result) {
            $this->_app->halt(500);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));

    }


    public function errorHandler(Exception $e) {
        switch(get_class($e)) {
            case "VerifyException":
                // the request for the resource was not valid, tell client
                list($error, $description) = explode(":", $e->getMessage());
                $this->_app->response()->header('WWW-Authenticate', 'realm="VOOT API",error="' . $error . '",error_description="' . $description . '"');
                $code = 400;
                if($error === 'invalid_request') {
                    $code = 400;
                }
                if($error === 'invalid_token') {
                    $code = 401;
                }
                if($error === 'insufficient_scope') {
                    $code = 403;
                }
                $this->_app->response()->status($code);
                break;
            case "OAuthException":
                // we cannot establish the identity of the client, tell user
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "The identity of the application that tried to access this resource could not be established. Therefore we stopped processing this request. The message below may be of interest to the application developer."));
                break;
            case "ErrorException":
            default:
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "Internal Server Error"), 500);
                break;
        }
    }

    private static function _getAuthorizationHeader() {
        // Apache Only!
        $httpHeaders = apache_request_headers();
        if(!array_key_exists("Authorization", $httpHeaders)) {
            throw new VerifyException("invalid_request: authorization header missing");
        }
        return $httpHeaders['Authorization'];
    }

}

?>
