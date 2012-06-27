<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'ext/Slim-Extras/Log Writers/TimestampLogFileWriter.php';
require_once 'ext/Slim-Extras/Middleware/HttpBasicAuth.php';
require_once 'lib/Config.php';
require_once 'lib/SlimVoot.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null,
    'mode' => 'production',
    //'mode' => 'development',
    'debug' => false,
    'log.writer' => new TimestampLogFileWriter(array('path' => 'data' . DIRECTORY_SEPARATOR . 'logs')),
));

$vootConfig = new Config(__DIR__ . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");

$authBackend = $vootConfig->getValue('authBackend');
// FIXME: just give all configuration parameters (vootConfig->getSection(...)) to the constructor
$app->add(new $authBackend($vootConfig->getSectionValue('HttpBasicAuth','username'), $vootConfig->getSectionValue('HttpBasicAuth','password')));

// VOOT
$t = new SlimVoot($app, $vootConfig);

$app->run();

?>
