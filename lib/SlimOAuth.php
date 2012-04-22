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

        $this->_app->get('/oauth/revoke', function () use ($self) {
            $self->approvals();
        });

        $this->_app->post('/oauth/revoke', function () use ($self) {
            $self->revoke();
        });

        $this->_app->get('/oauth/clients', function () use ($self) {
            $self->clients();
        });
        
        $this->_app->post('/oauth/clients', function () use ($self) {
            $self->register();
        });

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

    public function approvals() {
        $this->_authenticate();
        $approvals = $this->_oauthStorage->getApprovals($this->_resourceOwner);
        $this->_app->render('listApprovals.php', array( 'approvals' => $approvals));
    }

    public function revoke() {
        $this->_authenticate();
        // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id and 
        //        scope can remove an approval for any (authenticated) user by crafting
        //        a POST call to this endpoint. IMPACT: low risk, denial of service.

        // FIXME: we need to also remove the access tokens that are currently used
        //        by this service if the user wants this. Maybe we should have a 
        //        checkbox "terminate current access" or "keep current access
        //        tokens available for at most 1h"
        $this->_oauthStorage->deleteApproval($this->_app->request()->post('client_id'), $this->_resourceOwner, $this->_app->request()->post('scope'));
        $approvals = $this->_oauthStorage->getApprovals($this->_resourceOwner);
        $this->_app->render('listApprovals.php', array( 'approvals' => $approvals));
    }

    public function clients() {
        $this->_authenticate();
        if(!in_array($this->_resourceOwner, $this->_oauthConfig['OAuth']['adminResourceOwnerId'])) {
            throw new AdminException("not an administrator");
        }
        $registeredClients = $this->_oauthStorage->getClients();
        $this->_app->render('listClients.php', array( 'registeredClients' => $registeredClients));
    }

    public function register() {
        $this->_authenticate();
        // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id 
        //        can remove or add! an application by crafting a POST call to this 
        //        endpoint. IMPACT: low, XSS required, how to fake POST on other domain?
        if(!in_array($this->_resourceOwner, $this->_oauthConfig['OAuth']['adminResourceOwnerId'])) {
            throw new AdminException("not an administrator");
        }
        
        // FIXME: should deal with deletion, new registrations, delete
        //        current access tokens?
    }

    public function getResourceOwner() {
        $this->_authenticate();
        return $this->_resourceOwner;
    }

    public function errorHandler(Exception $e) {
        switch(get_class($e)) {
            case "VerifyException":
                // the request for the resource was not valid, tell client
                list($error, $description) = explode(":", $e->getMessage());
                $this->_app->response()->header('WWW-Authenticate', 'realm="VOOT API",error="' . $error . '",error_description="' . $description . '"');
                $this->_app->response()->status(401);
                break;
            case "OAuthException":
                // we cannot establish the identity of the client, tell user
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "The identity of the application that tried to access this resource could not be established. Therefore we stopped processing this request. The message below may be of interest to the application developer."));
                break;
            case "AdminException":
                // the authenticated user wants to perform some operation that is 
                // privileged
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "You are not authorized to perform this operation."), 403);
                break;
            case "ErrorException":
            default:
                $this->_app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "Internal Server Error"), 500);
                break;
        }
    }

}

?>
