<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";

$c1 = new SplClassLoader("RestService", "../extlib/php-rest-service/lib");
$c1->register();
$c2 =  new SplClassLoader("VootProvider", "../lib");
$c2->register();

use \RestService\Utils\Config as Config;
use \RestService\Http\HttpResponse as HttpResponse;
use \RestService\Http\HttpRequest as HttpRequest;
use \RestService\Http\IncomingHttpRequest as IncomingHttpRequest;
use \RestService\Utils\Logger as Logger;
use \VootProvider\VootStorageException as VootStorageException;

$logger = NULL;
$request = NULL;
$response = NULL;

try {

    $config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");
    $logger = new Logger($config->getSectionValue('Log', 'logLevel'), $config->getValue('serviceName'), $config->getSectionValue('Log', 'logFile'), $config->getSectionValue('Log', 'logMail', FALSE));

    $request = HttpRequest::fromIncomingHttpRequest(new IncomingHttpRequest());

    $response = new HttpResponse();
    $response->setHeader("Content-Type", "application/json");

    // verify username and password
    if($request->getBasicAuthUser() !== $config->getValue('basicUser') || $request->getBasicAuthPass() !== $config->getValue('basicPass')) {
        $response->setStatusCode(401);
        $response->setHeader("WWW-Authenticate", 'Basic realm="' . $config->getValue("serviceName") . '"');
        $response->setContent(json_encode(array("error" => "unauthorized", "error_description" => "authentication failed or missing")));
    } else {
        $vootStorageBackend = "\\VootProvider\\" . $config->getValue('storageBackend');
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

} catch (VootStorageException $e) {
    $response = new HttpResponse();
    $response->setStatusCode($e->getResponseCode());
    $response->setContent(json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription())));
    if(NULL !== $logger) {
        $logger->logFatal($e->getLogMessage(TRUE) . PHP_EOL . $request . PHP_EOL . $response);
    }
} catch (Exception $e) {
    // any other error thrown by any of the modules, assume internal server error
    $response = new HttpResponse();
    $response->setStatusCode(500);
    $response->setContent(json_encode(array("error" => "internal_server_error", "error_description" => $e->getMessage())));
    if(NULL !== $logger) {
        $logger->logFatal($e->getMessage() . PHP_EOL . $request . PHP_EOL . $response);
    }
}

if (NULL !== $logger) {
    $logger->logDebug($request);
}
if (NULL !== $logger) {
    $logger->logDebug($response);
}
if (NULL !== $response) {
    $response->sendResponse();
}
