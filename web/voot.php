<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use fkooman\Config\Config;
use fkooman\Http\Response;
use fkooman\Http\Request;
use fkooman\Http\IncomingRequest;

use fkooman\VootProvider\VootStorageException;

$request = null;
$response = null;

try {
    $config = Config::fromIniFile(
        dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini"
    );
    $request = Request::fromIncomingRequest(
        new IncomingRequest()
    );

    $response = new Response(200, "application/json");

    // verify username and password
    if ($request->getBasicAuthUser() !== $config->getValue('basicUser') ||
        $request->getBasicAuthPass() !== $config->getValue('basicPass')
    ) {
        $response->setStatusCode(401);
        $response->setHeader(
            "WWW-Authenticate",
            sprintf('Basic realm="%s"', $config->getValue("serviceName"))
        );
        $response->setContent(
            json_encode(
                array(
                    "error" => "unauthorized",
                    "error_description" => "authentication failed or missing"
                )
            )
        );
    } else {
        $vootStorageBackend = "fkooman\\VootProvider\\" . $config->getValue('storageBackend');
        $vootStorage = new $vootStorageBackend($config);

        // GROUPS
        $request->matchRest(
            "GET",
            "/groups/:uid",
            function ($uid) use ($request, $response, $vootStorage) {
                $groups = $vootStorage->isMemberOf(
                    $uid,
                    $request->getQueryParameter("startIndex"),
                    $request->getQueryParameter("count")
                );
                $response->setContent(json_encode($groups));
            }
        );

        // PEOPLE
        $request->matchRest(
            "GET",
            "/people/:uid",
            function ($uid) use ($request, $response, $vootStorage) {
                $userInfo = $vootStorage->getUserAttributes($uid);
                $response->setContent(json_encode($userInfo));
            }
        );

        // PEOPLE IN GROUP
        $request->matchRest(
            "GET",
            "/people/:uid/:gid",
            function ($uid, $gid) use ($request, $response, $vootStorage) {
                $users = $vootStorage->getGroupMembers(
                    $uid,
                    $gid,
                    $request->getQueryParameter("startIndex"),
                    $request->getQueryParameter("count")
                );
                $response->setContent(json_encode($users));
            }
        );

        $request->matchRestDefault(
            function ($methodMatch, $patternMatch) use ($request, $response) {
                if (in_array($request->getRequestMethod(), $methodMatch)) {
                    if (!$patternMatch) {
                        $response->setStatusCode(404);
                        $response->setContent(
                            json_encode(
                                array(
                                    "error" => "not_found",
                                    "error_description" => "resource not found"
                                )
                            )
                        );
                    }
                } else {
                    $response->setStatusCode(405);
                    $response->setContent(
                        json_encode(
                            array(
                                "error" => "method_not_allowed",
                                "error_description" => "request method not allowed"
                            )
                        )
                    );
                }
            }
        );
    }

} catch (VootStorageException $e) {
    $response = new Response($e->getResponseCode(), "application/json");
    $response->setContent(
        json_encode(
            array(
                "error" => $e->getMessage(),
                "error_description" => $e->getDescription()
            )
        )
    );
} catch (Exception $e) {
    // any other error thrown by any of the modules, assume internal server error
    $response = new Response(500, "application/json");
    $response->setContent(
        json_encode(
            array(
                "error" => "internal_server_error",
                "error_description" => $e->getMessage()
            )
        )
    );
}

if (null !== $response) {
    $response->sendResponse();
}
