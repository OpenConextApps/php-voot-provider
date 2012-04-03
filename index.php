<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/OAuth/AuthorizationServer.php';
require_once 'lib/OAuth/PdoOAuthStorage.php';
require_once 'lib/Voot/Provider.php';

$app = new Slim(array(
    'session.handler' => null
));

$config = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);

$oauthDsn = 'sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'oauth2.sqlite';
$oauthStorage = new PdoOAuthStorage(new PDO($oauthDsn));

$vootStorageBackend = $config['voot']['storageBackend'];
if($vootStorageBackend === "PdoVootStorage") {
    require_once "lib/Voot/PdoVootStorage.php";
    $vootDsn = 'sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'voot.sqlite';
    $vootStorage = new PdoVootStorage(new PDO($vootDsn));
} else if($vootStorageBackend === "LdapVootStorage") {
    require_once "lib/Voot/LdapVootStorage.php";
    $vootStorage = new LdapVootStorage($config['vootLdap']['host'], $config['vootLdap']['groupDn']);
} else {
    $app->halt("unsupported voot backend");
}

$app->get('/oauth/authorize', function () use ($app, $oauthStorage, $config) {

    $authMech = $config['oauth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech();
    if($authMech === "SspResourceOwner") {
        $ro = new $authMech();
        $ro->setPath($config['oauthSsp']['sspPath']);
        $ro->setAuthSource($config['oauthSsp']['authSource']);
        $ro->setResourceOwnerIdAttributeName($config['oauthSsp']['resourceOwnerIdAttributeName']);
    }else if($authMech === "DummyResourceOwner") {
        $ro = new $authMech($config['oauthDummy']['resourceOwnerId'], $config['oauthDummy']['resourceOwnerDisplayName']);
    } else {
        $app->halt("unsupported authentication backend");
    }
    
    $resourceOwner = $ro->getResourceOwnerId();

    $o = new AuthorizationServer($oauthStorage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));
    $result = $o->authorize($app->request());
    if($result['action'] === 'ask_approval') { 
        // we know that all request parameters we used below are acceptable because they were verified by the authorize method.

        // FIXME: template! Do something with case where no scope is requested!

            echo '<html><head><title>Authorization</title></head><body>' . PHP_EOL;
            echo '<h2>Authorization Requested</h2>' . PHP_EOL;
            echo '<p>The application <strong>' . $app->request()->get('client_id') . '</strong> wants access to your group membership details with the following permissions:' . PHP_EOL;
            if(NULL !== $app->request()->get('scope')) {
                echo '<ul>' . PHP_EOL;
                foreach(AuthorizationServer::validateAndSortScope($app->request()->get('scope')) as $s) {
                    echo '<li>' . $s . '</li>' . PHP_EOL;
                }
                echo '</ul>' . PHP_EOL;
            }
            echo 'You can either approve or reject the request.</p>' . PHP_EOL;
            echo '<form method="post" action="">' . PHP_EOL;
            echo '<input type="submit" name="approval" value="Approve">' . PHP_EOL;
            echo '<input type="submit" name="approval" value="Deny">' . PHP_EOL;
            echo '<input type="hidden" name="authorize_nonce" value="' . $result['authorize_nonce'] . '">' . PHP_EOL;
            echo '</form>' . PHP_EOL;
            echo '</body></html>' . PHP_EOL;
    } else {

        $app->redirect($result['url']);

    }

});

$app->post('/oauth/authorize', function () use ($app, $oauthStorage, $config) {

    $authMech = $config['oauth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech();
    if($authMech === "SspResourceOwner") {
        $ro = new $authMech();
        $ro->setPath($config['oauthSsp']['sspPath']);
        $ro->setAuthSource($config['oauthSsp']['authSource']);
        $ro->setResourceOwnerIdAttributeName($config['oauthSsp']['resourceOwnerIdAttributeName']);
    }else if($authMech === "DummyResourceOwner") {
        $ro = new $authMech($config['oauthDummy']['resourceOwnerId'], $config['oauthDummy']['resourceOwnerDisplayName']);
    } else {
        $app->halt("unsupported authentication backend");
    }
   
    $resourceOwner = $ro->getResourceOwnerId();

    $o = new AuthorizationServer($oauthStorage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));
    $result = $o->approve($app->request());
    
    // error_log(var_export($result, TRUE));
    $app->redirect($result['url']);
});

$app->get('/groups/:name', function ($name) use ($app, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");

    // FIXME: fix verify to not require instantiation of the AuthorizationServer
    $resourceOwner = NULL;
    $o = new AuthorizationServer($oauthStorage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));

    $result = $o->verify($app->request());

    $g = new Provider($vootStorage);
    $grp_array = $g->isMemberOf($result->resource_owner_id, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);

});

$app->get('/people/:name/:groupId', function ($name, $groupId) use ($app, $oauthStorage, $vootStorage) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");

    // FIXME: fix verify to not require instantiation of the AuthorizationServer
    $resourceOwner = NULL;
    $o = new AuthorizationServer($oauthStorage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));

    $result = $o->verify($app->request());

    $g = new Provider($vootStorage);
    $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);

});

$app->run();

?>
