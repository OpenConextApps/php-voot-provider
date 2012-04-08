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
        $app->render('askAuthorization.php', array (
            'clientId' => $app->request()->get('client_id'), 
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
