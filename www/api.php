<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\Http\HttpResponse as HttpResponse;
use \Tuxed\Http\HttpRequest as HttpRequest;
use \Tuxed\Http\IncomingHttpRequest as IncomingHttpRequest;

$response = new HttpResponse();
$response->setHeader("Content-Type", "application/json");

try {
    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    // verify Basic Authentication username and password
    if($request->getBasicAuthUser() !== $config->getValue('basicUser') || $request->getBasicAuthPass() !== $config->getValue('basicPass')) {
        // FIXME: set WWW-Authenticate etc etc, HTTP 401
        throw new Exception("invalid username or password");
    }

    $vootStorageBackend = "\\Tuxed\\Voot\\" . $config->getValue('storageBackend');
    $vootStorage = new $vootStorageBackend($config);

    if($request->matchRest("GET", "groups", TRUE)) {
        $uid = $request->getResource();
        $groups = $vootStorage->isMemberOf($uid, $request->getQueryParameter("startIndex"), $request->getQueryParameter("count"));
        $response->setContent(json_encode($groups));
    } elseif($request->matchRest("GET", "people", TRUE)) {
        $uid = $request->getResource();
        $gid = "foo";   // FIXME: get this from the pattern matching
        $users = $vootStorage->getGroupMembers($uid, $gid, $request->getQueryParameter("startIndex"), $request->getQueryParameter("count"));
        $response->setContent(json_encode($users));
    }
} catch (Exception $e) {
    $response->setStatusCode(401);
    $response->setContent($e->getMessage());
}

$response->sendResponse();

?>
