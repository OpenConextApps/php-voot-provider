<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/SlimOAuth.php';
require_once 'lib/SlimStorage.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null,
    'debug' => false
));

$oauthConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "oauth.ini", TRUE);
$remoteStorageConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "remoteStorage.ini", TRUE);

//$app->get('/oauth/:uid/authorize', function ($uid) use ($app, $oauthStorage, $oauthConfig) {
//    if($resourceOwner !== $uid) {
//        throw new OAuthException("$uid does not match $resourceOwner");
//    }

//$app->post('/oauth/:uid/authorize', function ($uid) use ($app, $oauthStorage, $oauthConfig) {
//    if($resourceOwner !== $uid) {
//        throw new OAuthException("$uid does not match $resourceOwner");
//    }


// OAuth
$s = new SlimOAuth($app, $oauthConfig);

// Storage
$t = new SlimStorage($app, $oauthConfig, $storageConfig);

$app->run();

?>
