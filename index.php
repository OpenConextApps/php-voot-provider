<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/OAuth/AuthorizationServer.php';
require_once 'lib/Voot/Provider.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null
));

$config = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);

$oauthStorageBackend = $config['OAuth']['storageBackend'];
require_once "lib/OAuth/$oauthStorageBackend.php";
$oauthStorage = new $oauthStorageBackend($config[$oauthStorageBackend]);

$vootStorageBackend = $config['voot']['storageBackend'];
require_once "lib/Voot/$vootStorageBackend.php";
$vootStorage = new $vootStorageBackend($config[$vootStorageBackend]);

$app->get('/oauth/authorize', function () use ($app, $oauthStorage, $config) {
    $authMech = $config['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($config[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $o = new AuthorizationServer($oauthStorage, $config['OAuth']);
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

$app->post('/oauth/authorize', function () use ($app, $oauthStorage, $config) {
    $authMech = $config['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($config[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $o = new AuthorizationServer($oauthStorage, $config['OAuth']);
    $result = $o->approve($resourceOwner, $app->request());
    $app->redirect($result['url']);
});

$app->get('/oauth/revoke', function() use ($app, $oauthStorage, $config) {
    $authMech = $config['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($config[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $approvals = $oauthStorage->getApprovals($resourceOwner);
    $app->render('listApprovals.php', array( 'approvals' => $approvals));
});

$app->post('/oauth/revoke', function() use ($app, $oauthStorage, $config) {
    // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id and 
    //        scope can remove an approval for any (authenticated) user by crafting
    //        a POST call to this endpoint. IMPACT: low risk, denial of service.

    // FIXME: we need to also remove the access tokens that are currently used
    //        by this service if the user wants this. Maybe we should have a 
    //        checkbox "terminate current access" or "keep current access
    //        tokens available for at most 1h"
    $authMech = $config['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($config[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $oauthStorage->deleteApproval($app->request()->post('client_id'), $resourceOwner, $app->request()->post('scope'));
    $approvals = $oauthStorage->getApprovals($resourceOwner);
    $app->render('listApprovals.php', array( 'approvals' => $approvals));
});

$app->get('/oauth/clients', function() use ($app, $oauthStorage, $config) {
    $authMech = $config['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($config[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if($resourceOwner !== $config['OAuth']['adminResourceOwnerId']) {
        $app->halt(403, "Unauthorized");
    }
    $registeredClients = $oauthStorage->getClients();
    $app->render('listClients.php', array( 'registeredClients' => $registeredClients));
});

$app->post('/oauth/clients', function() use ($app, $oauthStorage, $config) {
    $authMech = $config['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($config[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if($resourceOwner !== $config['OAuth']['adminResourceOwnerId']) {
        $app->halt(403, "Unauthorized");
    }
    
    // FIXME: should deal with deletion, new registrations, delete
    //        current access tokens?

});

$app->get('/groups/:name', function ($name) use ($app, $config, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $o = new AuthorizationServer($oauthStorage, $config['OAuth']);
    $result = $o->verify($app->request());
    $g = new Provider($vootStorage);
    $grp_array = $g->isMemberOf($result->resource_owner_id, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);
});

$app->get('/people/:name/:groupId', function ($name, $groupId) use ($app, $config, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $o = new AuthorizationServer($oauthStorage, $config['OAuth']);
    $result = $o->verify($app->request());
    $g = new Provider($vootStorage);
    $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);
});

$app->run();

?>
