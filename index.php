<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/OAuth/AuthorizationServer.php';
require_once 'lib/Voot/Provider.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null,
    'debug' => false
));

$app->error(function ( Exception $e ) use ($app) {
    switch(get_class($e)) {
        case "VerifyException":
            // the request for the resource was not valid, tell client
            list($error, $description) = explode(":", $e->getMessage());
            $app->response()->header('WWW-Authenticate', 'realm="VOOT API",error="' . $error . '",error_description="' . $description . '"');
            $app->response()->status(401);
            break;
        case "OAuthException":
            // we cannot establish the identity of the client, tell user
            $app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "The identity of the application that tried to access this resource could not be established. Therefore we stopped processing this request. The message below may be of interest to the application developer."));
            break;
        case "AdminException":
            // the authenticated user wants to perform some operation that is 
            // privileged
            $app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "You are not authorized to perform this operation."), 403);
            break;
        default:
            $app->halt(500);
    }
});

$vootConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);
$oauthConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "oauth.ini", TRUE);

$oauthStorageBackend = $oauthConfig['OAuth']['storageBackend'];
require_once "lib/OAuth/$oauthStorageBackend.php";
$oauthStorage = new $oauthStorageBackend($oauthConfig[$oauthStorageBackend]);

$vootStorageBackend = $vootConfig['voot']['storageBackend'];
require_once "lib/Voot/$vootStorageBackend.php";
$vootStorage = new $vootStorageBackend($vootConfig[$vootStorageBackend]);

$app->get('/oauth/authorize', function () use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
    $result = $o->authorize($resourceOwner, $app->request());
    // we know that all request parameters we used below are acceptable because they were verified by the authorize method.
    // Do something with case where no scope is requested!
    if($result['action'] === 'ask_approval') { 
        $client = $oauthStorage->getClient($app->request()->get('client_id'));
        $app->render('askAuthorization.php', array (
            'clientId' => $client->id,
            'clientName' => $client->name,
            'redirectUri' => $client->redirect_uri,
            'scope' => $app->request()->get('scope'), 
            'authorizeNonce' => $result['authorize_nonce']));
    } else {
        $app->redirect($result['url']);
    }
});

$app->post('/oauth/authorize', function () use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
    $result = $o->approve($resourceOwner, $app->request());
    $app->redirect($result['url']);
});

$app->get('/oauth/revoke', function() use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $approvals = $oauthStorage->getApprovals($resourceOwner);
    $app->render('listApprovals.php', array( 'approvals' => $approvals));
});

$app->post('/oauth/revoke', function() use ($app, $oauthStorage, $oauthConfig) {
    // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id and 
    //        scope can remove an approval for any (authenticated) user by crafting
    //        a POST call to this endpoint. IMPACT: low risk, denial of service.

    // FIXME: we need to also remove the access tokens that are currently used
    //        by this service if the user wants this. Maybe we should have a 
    //        checkbox "terminate current access" or "keep current access
    //        tokens available for at most 1h"
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $oauthStorage->deleteApproval($app->request()->post('client_id'), $resourceOwner, $app->request()->post('scope'));
    $approvals = $oauthStorage->getApprovals($resourceOwner);
    $app->render('listApprovals.php', array( 'approvals' => $approvals));
});

$app->get('/oauth/clients', function() use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if(!in_array($resourceOwner, $oauthConfig['OAuth']['adminResourceOwnerId'])) {
        throw new AdminException("not an administrator");
    }
    $registeredClients = $oauthStorage->getClients();
    $app->render('listClients.php', array( 'registeredClients' => $registeredClients));
});

$app->post('/oauth/clients', function() use ($app, $oauthStorage, $oauthConfig) {
    // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id 
    //        can remove or add! an application by crafting a POST call to this 
    //        endpoint. IMPACT: high risk, fake client registration
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if(!in_array($resourceOwner, $oauthConfig['OAuth']['adminResourceOwnerId'])) {
        throw new AdminException("not an administrator");
    }
    
    // FIXME: should deal with deletion, new registrations, delete
    //        current access tokens?

});

$app->get('/groups/:name', function ($name) use ($app, $oauthConfig, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
    $result = $o->verify($app->request());
    $g = new Provider($vootStorage);
    $grp_array = $g->isMemberOf($result->resource_owner_id, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);
});

$app->get('/people/:name/:groupId', function ($name, $groupId) use ($app, $oauthConfig, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
    $result = $o->verify($app->request());
    $g = new Provider($vootStorage);
    $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);
});

$app->run();

?>
