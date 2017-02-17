<?php

/**
 * Copyright 2013 François Kooman <fkooman@tuxed.net>.
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

namespace fkooman\VootProvider\Rest\Plugin;

use fkooman\VootProvider\Http\JsonResponse;
use fkooman\VootProvider\Http\Request;
use fkooman\VootProvider\Rest\ServicePluginInterface;

class BasicAuthentication implements ServicePluginInterface
{
    /** @var string */
    private $basicAuthUser;

    /** @var string */
    private $basicAuthPass;

    /** @var string */
    private $basicAuthRealm;

    public function __construct($basicAuthUser, $basicAuthPass, $basicAuthRealm = 'Protected Resource')
    {
        $this->basicAuthUser = $basicAuthUser;
        $this->basicAuthPass = $basicAuthPass;
        $this->basicAuthRealm = $basicAuthRealm;
    }

    /**
     * @return bool|\fkooman\VootProvider\Http\Response
     */
    public function execute(Request $request)
    {
        $requestBasicAuthUser = $request->getBasicAuthUser();
        $requestBasicAuthPass = $request->getBasicAuthPass();

        if ($this->basicAuthUser !== $requestBasicAuthUser || $this->basicAuthPass !== $requestBasicAuthPass) {
            $response = new JsonResponse(401);
            $response->setHeader(
                'WWW-Authenticate',
                sprintf('Basic realm="%s"', $this->basicAuthRealm)
            );
            $response->setContent(
                [
                    'code' => 401,
                    'error' => 'Unauthorized',
                ]
            );

            return $response;
        }

        return true;
    }
}
