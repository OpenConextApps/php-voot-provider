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
        $response->setContent(json_encode(array("error" => "unauthorized", "error_description" => "authentication failed or missing")));
    } else {
        $vootStorageBackend = "\\Tuxed\\Voot\\" . $config->getValue('storageBackend');
        $vootStorage = new $vootStorageBackend($config);

        // GROUPS
        $request->matchRest("GET", "/groups/:uid", function($uid) use ($request, $response, $vootStorage) {
            $groups = $vootStorage->isMemberOf($uid, $request->getQueryParameter("startIndex"), $request->getQueryParameter("count"));
            $response->setContent(json_encode($groups));
        });

        // PEOPLE
        $request->matchRest("GET", "/people/:uid", function($uid) use ($request, $response, $vootStorage) {
            $userInfo = $vootStorage->getUserAttributes($uid);
            $response->setContent(json_encode($userInfo));
        });

        // PEOPLE IN GROUP
        $request->matchRest("GET", "/people/:uid/:gid", function($uid, $gid) use ($request, $response, $vootStorage) {
            $users = $vootStorage->getGroupMembers($uid, $gid, $request->getQueryParameter("startIndex"), $request->getQueryParameter("count"));
            $response->setContent(json_encode($users));
        });

        $request->matchRestDefault(function($methodMatch, $patternMatch) use ($request, $response) {
            if(in_array($request->getRequestMethod(), $methodMatch)) {
                if(!$patternMatch) {
                    $response->setStatusCode(404);
                    $response->setContent(json_encode(array("error" => "not_found", "error_description" => "resource not found")));
                }
            } else {
                $response->setStatusCode(405);
                $response->setContent(json_encode(array("error" => "method_not_allowed", "error_description" => "request method not allowed")));
            }
        });
    }
} catch (Exception $e) {
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
}

$response->sendResponse();
