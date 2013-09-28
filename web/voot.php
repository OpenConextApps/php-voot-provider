<?php

/**
* Copyright 2013 François Kooman <fkooman@tuxed.net>
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/

require_once dirname(__DIR__) . "/vendor/autoload.php";

use fkooman\Config\Config;
use fkooman\Http\Service;
use fkooman\Http\JsonResponse;
use fkooman\Http\Request;
use fkooman\Http\IncomingRequest;
use fkooman\VootProvider\VootStorageException;

try {
    $config = Config::fromIniFile(
        dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini"
    );

    $vootStorageBackend = "fkooman\\VootProvider\\" . $config->getValue('storageBackend');
    $vootStorage = new $vootStorageBackend($config);

    $request = Request::fromIncomingRequest(
        new IncomingRequest()
    );

    $service = new Service($request);

    // require authorization?
    if (null !== $config->getValue('basicUser')) {
        $configBasicAuthUser = $config->getValue('basicUser');
        $configBasicAuthPass = $config->getValue('basicPass');
        $configBasicAuthRealm = $config->getValue('serviceName');
        $service->requireBasicAuth($configBasicAuthUser, $configBasicAuthPass, $configBasicAuthRealm);
    }

    // GROUPS
    $service->match(
        "GET",
        "/groups/:uid",
        function ($uid) use ($request, $vootStorage) {
            $groups = $vootStorage->isMemberOf(
                $uid,
                $request->getQueryParameter("startIndex"),
                $request->getQueryParameter("count")
            );
            $response = new JsonResponse(200);
            $response->setContent($groups);

            return $response;
        }
    );

    // PEOPLE IN GROUP
    $service->match(
        "GET",
        "/people/:uid/:gid",
        function ($uid, $gid) use ($request, $vootStorage) {
            $users = $vootStorage->getGroupMembers(
                $uid,
                $gid,
                $request->getQueryParameter("startIndex"),
                $request->getQueryParameter("count")
            );
            $response = new JsonResponse(200);
            $response->setContent($users);

            return $response;
        }
    );

    $service->run()->sendResponse();

} catch (VootStorageException $e) {
    $response = new JsonResponse($e->getResponseCode());
    $response->setContent(
        array(
            "error" => $e->getMessage(),
            "error_description" => $e->getDescription()
        )
    );
    $response->sendResponse();
} catch (Exception $e) {
    // any other error thrown by any of the modules, assume internal server error
    $response = new JsonResponse(500);
    $response->setContent(
        array(
            "error" => "internal_server_error",
            "error_description" => $e->getMessage()
        )
    );
    $response->sendResponse();
}
