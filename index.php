<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/Config.php';
require_once 'lib/SlimOAuth.php';
require_once 'lib/SlimVoot.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null,
    'debug' => false
));

$oauthConfig = new Config("oauth");
$vootConfig = new Config("voot");

// OAuth
$s = new SlimOAuth($app, $oauthConfig);

// VOOT
$t = new SlimVoot($app, $oauthConfig, $vootConfig);

$app->run();

?>
