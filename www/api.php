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

    // verify username and password
    if($request->getBasicAuthUser() !== $config->getValue('basicUser') || $request->getBasicAuthPass() !== $config->getValue('basicPass')) {
        $response->setStatusCode(401);
        $response->setHeader("WWW-Authenticate", 'Basic realm="' . $config->getValue("basicRealm") . '"');
    } else {
        $vootStorageBackend = "\\Tuxed\\Voot\\" . $config->getValue('storageBackend');
        $vootStorage = new $vootStorageBackend($config);

        // GROUPS
        $request->matchRestNice("GET", "/groups/:uid", function($uid) use ($request, $response, $vootStorage) {
            $groups = $vootStorage->isMemberOf($uid, $request->getQueryParameter("startIndex"), $request->getQueryParameter("count"));
            $response->setContent(json_encode($groups));
        });

        // PEOPLE
        $request->matchRestNice("GET", "/people/:uid/:gid", function($uid, $gid) use ($request, $response, $vootStorage) {
            $users = $vootStorage->getGroupMembers($uid, $gid, $request->getQueryParameter("startIndex"), $request->getQueryParameter("count"));
            $response->setContent(json_encode($users));
        });

        $request->matchDefault(function() use ($response) {
            $response->setStatusCode(404);
            $response->setContent(json_encode(array("error" => "not_found", "error_description" => "resource not found")));
        });

    }
} catch (Exception $e) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
}

$response->sendResponse();
