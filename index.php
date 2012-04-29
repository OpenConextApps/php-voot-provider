<?php
require_once 'lib/SlimOAuth.php';
require_once 'lib/SlimStorage.php';

if(substr($_SERVER['REQUEST_URI'], 0, 8) == '/storage') {
    header('Slim: Shady');
    $oauthConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "oauth.ini", TRUE);
    $remoteStorageConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "remoteStorage.ini", TRUE);
    // OAuth
    $s = new SlimOAuth(null, $oauthConfig);

    // Storage
    $t = new SlimStorage(null, $oauthConfig, $remoteStorageConfig, $s);
    // Apache Only!
    $httpHeaders = apache_request_headers();
    if(!array_key_exists("Authorization", $httpHeaders)) {
        $authorizationHeader = '';
    } else {
        $authorizationHeader = $httpHeaders['Authorization'];
    }
    if(!array_key_exists("Origin", $httpHeaders)) {
        $originHeader = '';
    } else {
        $originHeader = $httpHeaders['Origin'];
    }
    if(!array_key_exists("Content-Type", $httpHeaders)) {
        $contentTypeHeader = '';
    } else {
        $contentTypeHeader = $httpHeaders['Content-Type'];
    }
    $data = file_get_contents('php://input');//$app->request()->getBody();
    $t->handleStorageCall($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $originHeader, $authorizationHeader, $contentTypeHeader, $data);
} else {
    require_once 'ext/Slim/Slim/Slim.php';
    $app = new Slim(array(
        // we need to disable Slim's session handling due to incompatibilies with
        // simpleSAMLphp sessions
        'session.handler' => null,
        'debug' => false
    ));

    $oauthConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "oauth.ini", TRUE);
    $remoteStorageConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "remoteStorage.ini", TRUE);

    // OAuth
    $s = new SlimOAuth($app, $oauthConfig);

    // Storage
    $t = new SlimStorage($app, $oauthConfig, $remoteStorageConfig, $s);
    $app->run();
}
?>
