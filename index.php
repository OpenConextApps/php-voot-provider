<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/OAuth/AuthorizationServer.php';
require_once 'lib/OAuth/PdoStorage.php';
require_once 'lib/OAuth/DummyResourceOwner.php';
require_once 'lib/Voot/Groups.php';
require_once 'lib/Voot/People.php';

$app = new Slim(array(
    'session.handler' => null
));

$dsn = 'sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'oauth2.sqlite';
$storage = new PdoStorage(new PDO($dsn));

$config = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);

$app->get('/oauth/authorize', function () use ($app, $storage, $config) {

    $authMech = $config['oauth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech();
    if($authMech === "SspResourceOwner") {
        $ro->setPath($config['oauthSsp']['sspPath']);
        $ro->setAuthSource($config['oauthSsp']['authSource']);
        $ro->setResourceOwnerIdAttributeName($config['oauthSsp']['resourceOwnerIdAttributeName']);
    }    
    $resourceOwner = $ro->getResourceOwnerId();

    $o = new AuthorizationServer($storage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));
    $result = $o->authorize($app->request());
    if($result['action'] === 'ask_approval') { 
        // we know that all request parameters we used below are acceptable because they were verified by the authorize method.

        // FIXME: template! Do something with case where no scope is requested!

            echo '<html><head><title>Authorization</title></head><body>' . PHP_EOL;
            echo '<h2>Authorization Requested</h2>' . PHP_EOL;
            echo '<p>The application <strong>' . $app->request()->get('client_id') . '</strong> wants to access your resources.</p>' . PHP_EOL;
            echo '<form method="post" action="">' . PHP_EOL;
            if(NULL !== $app->request()->get('scope')) {
                echo '<p>The following permissions are requested:' . PHP_EOL;
                echo '<ul>' . PHP_EOL;
                foreach($o->getSupportedScopes() as $s) {
                    echo '<li style="list-style: none;"><label><input type="checkbox" name="scope[]" ';
                    if(in_array($s, explode(" ", $app->request()->get('scope')))) {
                        echo ' disabled="disabled" checked="checked" ';
                    } else {
                        echo ' disabled="disabled" ';
                    }
                    echo ' value="' . $s .'">' . $s . '</label></li>' . PHP_EOL;
                }
                echo '</ul>' . PHP_EOL;
                echo 'You can either approve or reject the request.</p>' . PHP_EOL;
            }
            echo '<input type="submit" name="approval" value="Approve">' . PHP_EOL;
            echo '<input type="submit" name="approval" value="Deny">' . PHP_EOL;
            echo '<input type="hidden" name="authorize_nonce" value="' . $result['authorize_nonce'] . '">' . PHP_EOL;
            echo '</form>' . PHP_EOL;
            echo '</body></html>' . PHP_EOL;
    } else {

        $app->redirect($result['url']);

    }

});

$app->post('/oauth/authorize', function () use ($app, $storage, $config) {

    $authMech = $config['oauth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech();
    if($authMech === "SspResourceOwner") {
        $ro->setPath($config['oauthSsp']['sspPath']);
        $ro->setAuthSource($config['oauthSsp']['authSource']);
        $ro->setResourceOwnerIdAttributeName($config['oauthSsp']['resourceOwnerIdAttributeName']);
    }    
    $resourceOwner = $ro->getResourceOwnerId();

    $o = new AuthorizationServer($storage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));
    $result = $o->approve($app->request());
    $app->redirect($result['url']);
});

$app->get('/groups/:name', function ($name) use ($app, $storage, $config) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");

    // FIXME: fix verify to not require instantiation of the AuthorizationServer
    $resourceOwner = NULL;
    $o = new AuthorizationServer($storage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));

    $result = $o->verify($app->request());

    $dsn = 'sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'voot.sqlite';
    $pdo = new PDO($dsn);

    $g = new Groups($pdo);
    $grp_array = $g->isMemberOf($result->resource_owner_id, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);

});

$app->get('/people/:name/:groupId', function ($name, $groupId) use ($app, $storage, $config) {
    // enable CORS (http://enable-cors.org)
    $app->response()->header("Access-Control-Allow-Origin", "*");

    // FIXME: fix verify to not require instantiation of the AuthorizationServer
    $resourceOwner = NULL;
    $o = new AuthorizationServer($storage, $resourceOwner);
    $o->setSupportedScopes(array("read","write"));

    $result = $o->verify($app->request());

    $dsn = 'sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'voot.sqlite';
    $pdo = new PDO($dsn);

    $g = new People($pdo);
    $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $app->request()->get('startIndex'), $app->request()->get('count'));
    $app->response()->header('Content-Type','application/json');
    echo json_encode($grp_array);

});

$app->run();

?>
