<?php

require_once 'lib/OAuth/AuthorizationServer.php';

class SlimOAuth {

    private $_app;
    private $_c;
    
    private $_oauthStorage;

    private $_resourceOwner;
    private $_as;

    public function __construct(Slim $app, Config $c) {
        $this->_app = $app;
        $this->_c = $c;

        $oauthStorageBackend = $this->_c->getValue('storageBackend');
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_c);

        $this->_as = new AuthorizationServer($this->_oauthStorage, $this->_c);

        // in PHP 5.4 $this is possible inside anonymous functions.
        $self = &$this;

        $this->_app->get('/oauth/authorize', function () use ($self) {
            $self->authorize();
        });

        $this->_app->post('/oauth/authorize', function () use ($self) {
            $self->approve();
        });

        $this->_app->post('/oauth/token', function () use ($self) {
            $self->token();
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


	    $this->_app->get('/oauth/userinfo', function () use ($self) {
            $self->userInfo();
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
        $authMech = $this->_c->getValue('authenticationMechanism');
        require_once "lib/OAuth/$authMech.php";
        $this->_resourceOwner = new $authMech($this->_c);
    }

    public function authorize() {
        $this->_authenticate();
        $result = $this->_as->authorize($this->_resourceOwner, $this->_app->request()->get());

        // we know that all request parameters we used below are acceptable because they were verified by the authorize method.
        // Do something with case where no scope is requested!
        if($result['action'] === 'ask_approval') { 
            $client = $this->_oauthStorage->getClient($this->_app->request()->get('client_id'));
            if(FALSE === $client) {
                if($this->_c->getValue('allowUnregisteredClients')) {
                    $client = $this->_oauthStorage->getClientByRedirectUri($this->_app->request()->get('redirect_uri'));
                }   
            }
            $this->_app->render('askAuthorization.php', array (
                'clientId' => $client->id,
                'clientName' => $client->name,
                'clientDescription' => $client->description,
                'clientRedirectUri' => $client->redirect_uri,
                'scope' => $this->_app->request()->get('scope'), 
                'authorizeNonce' => $result['authorize_nonce'],
                'serviceName' => $this->_c->getValue('serviceName'),
                'serviceResources' => $this->_c->getValue('serviceResources'),
                'allowFilter' => $this->_c->getValue('allowResourceOwnerScopeFiltering')));
        } else {
            $this->_app->redirect($result['url']);
        }
    }

    public function approve() {
        $this->_authenticate();
        $result = $this->_as->approve($this->_resourceOwner, $this->_app->request()->get(), $this->_app->request()->post());
        $this->_app->redirect($result['url']);
    }

    public function token() {
        $result = $this->_as->token($this->_app->request()->post(), $this->_app->request()->headers("X-Authorization"));
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($result));
    }

    // REST API
    public function userInfo() {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_userinfo', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_userinfo scope");
        }

        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode(array ("user_id" => $result->resource_owner_id, "name" => $result->resource_owner_display_name)));
    }

    public function getClient($clientId) {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $data = $this->_oauthStorage->getClient($clientId);
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));
    }

    public function deleteClient($clientId) {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $data = $this->_oauthStorage->deleteClient($clientId);
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));

        $this->_app->getLog()->info("oauth client '" . $clientId . "' deleted by '" . $result->resource_owner_id . "'");
    }

    public function updateClient($clientId) {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $data = $this->_oauthStorage->updateClient($clientId, json_decode($this->_app->request()->getBody(), TRUE));
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));

        $this->_app->getLog()->info("oauth client '" . $clientId . "' updated to '" . $this->_app->request()->getBody() . "' by '" . $result->resource_owner_id . "'");
    }

    public function addClient() {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $requestData = json_decode($this->_app->request()->getBody(), TRUE);

        // if id is set, use it for the registration, if not generate one
        if(!array_key_exists('id', $requestData) || empty($requestData['id'])) {
            $requestData['id'] = AuthorizationServer::randomHex(16);
        }

        // if profile is web application and secret is set, use it, if web application
        // and secret is not set generate one
        if(!array_key_exists('secret', $requestData) || empty($requestData['secret'])) {
            if("web_application" === $requestData['type']) {
                $requestData['secret'] = AuthorizationServer::randomHex(16);
            } else {
                $requestData['secret'] = NULL;
            }
        }

        $data = $this->_oauthStorage->addClient($requestData);
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(500);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($requestData));
        $this->_app->getLog()->info("oauth client added '" . json_encode($requestData) . "' by '" . $result->resource_owner_id . "'");
    }

    public function getClients() {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_admin', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_admin scope");
        }

        $data = $this->_oauthStorage->getClients();
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));
    }

    public function getApprovals() {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_approval', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_approval scope");
        }

        $data = $this->_oauthStorage->getApprovals($result->resource_owner_id);
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));
    }

    public function deleteApproval($clientId) {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_approval', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_approval scope");
        }

        $data = $this->_oauthStorage->deleteApproval($clientId, $result->resource_owner_id);
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(404);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));
    }

    public function addApproval() {
        $result = $this->_as->verify($this->_app->request()->headers("X-Authorization"));

        if(!in_array('oauth_approval', AuthorizationServer::getScopeArray($result->scope))) {
            throw new VerifyException("insufficient_scope: need oauth_approval scope");
        }

        $data = json_decode($this->_app->request()->getBody(), TRUE);
        // FIXME: we should verify the client exists
        $clientId = $data['client_id'];
        // FIXME: we should verify the scope is valid and normalize it before
        //        storing it
        $scope = $data['scope'];

        $data = $this->_oauthStorage->addApproval($clientId, $result->resource_owner_id, $scope);
        if(FALSE === $data) {
            // FIXME: better error handling
            $this->_app->halt(500);
        }
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';
        $response->body(json_encode($data));

    }

    public function errorHandler(Exception $e) {
        switch(get_class($e)) {

            case "VerifyException":
                $response = $this->_app->response();
                // the request for the resource was not valid, tell client
                list($error, $description) = explode(":", $e->getMessage());
                $response['WWW-Authenticate'] = sprintf('Bearer realm="OAuth Server",error="%s",error_description="%s"', $error, $description);
                $code = 400;
                if("invalid_request" === $error) {
                    $code = 400;
                }
                if("invalid_token" === $error) {
                    $code = 401;
                }
                if("insufficient_scope" === $error) {
                    $code = 403;
                }
                $response->status($code);
                break;

            case "OAuthException":
                // we cannot establish the identity of the client, tell user
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "The identity of the application that tried to access this resource could not be established. Therefore we stopped processing this request. The message below may be of interest to the application developer."));
                break;

            case "TokenException":
                $response = $this->_app->response();
                // we need to inform the client interacting with the token endpoint
                list($error, $description) = explode(":", $e->getMessage());
                $code = 400;
                if("invalid_client" === $error) {
                    $code = 401;
                    $response['WWW-Authenticate'] = sprintf('Basic realm="OAuth Server",error="%s",error_description="%s"', $error, $description);
                }
                $response->status($code);
                $response['Content-Type'] = 'application/json';
                $response->body(json_encode(array("error" => $error, "error_description" => $description)));
                break;

            case "ErrorException":

            default:
                $this->_app->getLog()->error($e->getMessage());
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "Internal Server Error"), 500);
                break;
        }
    }

}

?>
