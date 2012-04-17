<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/SlimOAuth.php';
require_once 'lib/Voot/Provider.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null,
    'debug' => false
));

$vootConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);
$oauthConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "oauth.ini", TRUE);

$oauthStorageBackend = $oauthConfig['OAuth']['storageBackend'];
require_once "lib/OAuth/$oauthStorageBackend.php";
$oauthStorage = new $oauthStorageBackend($oauthConfig[$oauthStorageBackend]);

$vootStorageBackend = $vootConfig['voot']['storageBackend'];
require_once "lib/Voot/$vootStorageBackend.php";
$vootStorage = new $vootStorageBackend($vootConfig[$vootStorageBackend]);

$s = new SlimOAuth($app, $oauthStorage, $oauthConfig);

$app->get('/groups/:name', function ($name) use ($app, $oauthConfig, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);

    // Apache Only!
    $httpHeaders = apache_request_headers();
    if(!array_key_exists("Authorization", $httpHeaders)) {
        throw new VerifyException("invalid_request: authorization header missing");
    }
    $authorizationHeader = $httpHeaders['Authorization'];

    $result = $o->verify($authorizationHeader);
    $g = new Provider($vootStorage);
    $grp_array = $g->isMemberOf($result->resource_owner_id, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);
});

$app->get('/people/:name/:groupId', function ($name, $groupId) use ($app, $oauthConfig, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);

    // Apache Only!
    $httpHeaders = apache_request_headers();
    if(!array_key_exists("Authorization", $httpHeaders)) {
        throw new VerifyException("invalid_request: authorization header missing");
    }
    $authorizationHeader = $httpHeaders['Authorization'];

    $result = $o->verify($authorizationHeader);
    $g = new Provider($vootStorage);
    $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);
});

$app->run();

?>
