<?php

require_once 'lib/OAuth/AuthorizationServer.php';

class SlimOAuth {

    private $_app;

    public function __construct(Slim $app, IOAuthStorage $storage, array $config) {
        $this->_app = $app;
        $this->_storage = $storage;
        $this->_config = $config;

        // in PHP 5.4 $this is possible.
        $self = &$this;

        $this->_app->get('/oauth/authorize', function () use ($self) {
            $self->_authorize();
        });

        $this->_app->post('/oauth/authorize', function () use ($self) {
            $self->_approve();
        });

        $this->_app->get('/oauth/revoke', function () use ($self) {
            $self->_approvals();
        });

        $this->_app->post('/oauth/revoke', function () use ($self) {
            $self->_revoke();
        });

        $this->_app->get('/oauth/clients', function () use ($self) {
            $self->_clients();
        });
        
        $this->_app->post('/oauth/clients', function () use ($self) {
            $self->_register();
        });

        $this->_app->error(function(Exception $e) use ($self) {
            $self->_errorHandler($e);
        });

    }

    public function _authorize() {
        $authMech = $this->_config['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_config[$authMech]);
        $resourceOwner = $ro->getResourceOwnerId();
        $o = new AuthorizationServer($this->_storage, $this->_config['OAuth']);
        $result = $o->authorize($resourceOwner, $this->_app->request()->get());
        // we know that all request parameters we used below are acceptable because they were verified by the authorize method.
        // Do something with case where no scope is requested!
        if($result['action'] === 'ask_approval') { 
            $client = $this->_storage->getClient($this->_app->request()->get('client_id'));
            $this->_app->render('askAuthorization.php', array (
                'clientId' => $client->id,
                'clientName' => $client->name,
                'redirectUri' => $client->redirect_uri,
                'scope' => $this->_app->request()->get('scope'), 
                'authorizeNonce' => $result['authorize_nonce'],
                'allowFilter' => $this->_config['OAuth']['allowResourceOwnerScopeFiltering']));
        } else {
            $this->_app->redirect($result['url']);
        }
    }

    public function _approve() {
        $authMech = $this->_config['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_config[$authMech]);
        $resourceOwner = $ro->getResourceOwnerId();
        $o = new AuthorizationServer($this->_storage, $this->_config['OAuth']);
        $result = $o->approve($resourceOwner, $this->_app->request()->get(), $this->_app->request()->post());
        $this->_app->redirect($result['url']);
    }

    public function _approvals() {
        $authMech = $this->_config['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_config[$authMech]);
        $resourceOwner = $ro->getResourceOwnerId();
        $approvals = $this->_storage->getApprovals($resourceOwner);
        $this->_app->render('listApprovals.php', array( 'approvals' => $approvals));
    }

    public function _revoke() {
        // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id and 
        //        scope can remove an approval for any (authenticated) user by crafting
        //        a POST call to this endpoint. IMPACT: low risk, denial of service.

        // FIXME: we need to also remove the access tokens that are currently used
        //        by this service if the user wants this. Maybe we should have a 
        //        checkbox "terminate current access" or "keep current access
        //        tokens available for at most 1h"
        $authMech = $this->_config['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_config[$authMech]);
        $resourceOwner = $ro->getResourceOwnerId();
        $this->_storage->deleteApproval($this->_app->request()->post('client_id'), $resourceOwner, $this->_app->request()->post('scope'));
        $approvals = $this->_storage->getApprovals($resourceOwner);
        $this->_app->render('listApprovals.php', array( 'approvals' => $approvals));
    }

    public function _clients() {
        $authMech = $this->_config['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_config[$authMech]);
        $resourceOwner = $ro->getResourceOwnerId();
        if(!in_array($resourceOwner, $this->_config['OAuth']['adminResourceOwnerId'])) {
            throw new AdminException("not an administrator");
        }
        $registeredClients = $this->_storage->getClients();
        $this->_app->render('listClients.php', array( 'registeredClients' => $registeredClients));
    }

    public function _register() {
        // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id 
        //        can remove or add! an application by crafting a POST call to this 
        //        endpoint. IMPACT: low, XSS required, how to fake POST on other domain?
        $authMech = $this->_config['OAuth']['authenticationMechanism'];
        require_once "lib/OAuth/$authMech.php";
        $ro = new $authMech($this->_config[$authMech]);
        $resourceOwner = $ro->getResourceOwnerId();
        if(!in_array($resourceOwner, $this->_config['OAuth']['adminResourceOwnerId'])) {
            throw new AdminException("not an administrator");
        }
        
        // FIXME: should deal with deletion, new registrations, delete
        //        current access tokens?
    }

    public function _errorHandler(Exception $e) {
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
            default:
                $this->_app->halt(500);
        }
    }

}

?>
