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

namespace fkooman\VootProvider\Rest;

use fkooman\VootProvider\Http\JsonResponse;
use fkooman\VootProvider\Http\Request;
use fkooman\VootProvider\Http\RequestException;
use fkooman\VootProvider\Http\Response;
use UnexpectedValueException;

class Service
{
    /** @var \fkooman\VootProvider\Http\Request */
    private $request;

    /** @var array */
    private $match;

    /** @var array */
    private $supportedMethods;

    /** @var array */
    private $beforeMatchingPlugins;

    /** @var array */
    private $beforeEachMatchPlugins;

    /**
     * Create a new Service object.
     *
     * @param \fkooman\VootProvider\Http\Request $request the HTTP request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->match = [];
        $this->supportedMethods = [];

        $this->beforeMatchingPlugins = [];
        $this->beforeEachMatchPlugins = [];
    }

    /**
     * Register a plugin that is always run before the matching starts.
     *
     * @param \fkooman\VootProvider\Http\ServicePluginInterface $servicePlugin the plugin to
     *                                                                         register
     */
    public function registerBeforeMatchingPlugin(ServicePluginInterface $servicePlugin)
    {
        $this->beforeMatchingPlugins[] = $servicePlugin;
    }

    /**
     * Register a plugin that is run for every match, allowing you to skip it
     * for particular matches.
     *
     * @param \fkooman\VootProvider\Http\ServicePluginInterface the plugin to register
     */
    public function registerBeforeEachMatchPlugin(ServicePluginInterface $servicePlugin)
    {
        $this->beforeEachMatchPlugins[] = $servicePlugin;
    }

    /**
     * Register a method/pattern match.
     *
     * @param string   $requestMethod  the request method, e.g. 'GET', 'POST'
     * @param string   $requestPattern the pattern to match
     * @param callback $callback       the callback to execute when this pattern
     *                                 matches
     * @param array    $skipPlugin     the full namespaced names of the plugin classes
     *                                 to skip
     */
    public function match($requestMethod, $requestPattern, $callback, array $skipPlugin = [])
    {
        $this->match[] = [
            'requestMethod' => $requestMethod,
            'requestPattern' => $requestPattern,
            'callback' => $callback,
            'skipPlugin' => $skipPlugin,
        ];
        if (!in_array($requestMethod, $this->supportedMethods)) {
            $this->supportedMethods[] = $requestMethod;
        }
    }

    /**
     * Run the Service.
     *
     * @return \fkooman\VootProvider\Http\Response the HTTP response object after mathing
     *                                             is done and the appropriate callback was
     *                                             executed. If nothing matches either 404
     *                                             or 405 response is returned.
     */
    public function run()
    {
        // run the beforeMatchingPlugins
        foreach ($this->beforeMatchingPlugins as $plugin) {
            $response = $plugin->execute($this->request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        foreach ($this->match as $m) {
            // run the beforeEachMatchPlugins
            foreach ($this->beforeEachMatchPlugins as $plugin) {
                // only run when plugin should not be skipped
                if (in_array(get_class($plugin), $m['skipPlugin'])) {
                    continue;
                }
                $response = $plugin->execute($this->request);
                if ($response instanceof Response) {
                    return $response;
                }
            }

            $response = $this->matchRest(
                $m['requestMethod'],
                $m['requestPattern'],
                $m['callback']
            );

            // false indicates not a match
            if (false !== $response) {
                if ($response instanceof Response) {
                    return $response;
                }
                if (!is_string($response)) {
                    throw new UnexpectedValueException('callback MUST return Response object or string');
                }
                $responseObj = new Response(200, 'text/html');
                $responseObj->setContent($response);

                return $responseObj;
            }
        }

        // handle non matching patterns
        if (in_array($this->request->getRequestMethod(), $this->supportedMethods)) {
            $response = new JsonResponse(404);
            $response->setContent(
                [
                    'code' => 404,
                    'error' => 'Not Found',
                ]
            );

            return $response;
        }

        $response = new JsonResponse(405);
        $response->setHeader('Allow', implode(',', $this->supportedMethods));
        $response->setContent(
            [
                'code' => 405,
                'error' => 'Method Not Allowed',
            ]
        );

        return $response;
    }

    private function matchRest($requestMethod, $requestPattern, $callback)
    {
        if ($requestMethod !== $this->request->getRequestMethod()) {
            return false;
        }
        // if no pattern is defined, all paths are valid
        if (null === $requestPattern) {
            return call_user_func_array($callback, []);
        }
        // both the pattern and request path should start with a "/"
        if (0 !== strpos($this->request->getPathInfo(), '/') || 0 !== strpos($requestPattern, '/')) {
            return false;
        }

        // handle optional parameters
        $requestPattern = str_replace(')', ')?', $requestPattern);

        // check for variables in the requestPattern
        $pma = preg_match_all('#:([\w]+)\+?#', $requestPattern, $matches);
        if (false === $pma) {
            throw new RequestException('regex for variable search failed');
        }
        if (0 === $pma) {
            // no variables in the pattern, pattern and request must be identical
            if ($this->request->getPathInfo() === $requestPattern) {
                return call_user_func_array($callback, []);
            }
            // FIXME?!
            //return false;
        }
        // replace all the variables with a regex so the actual value in the request
        // can be captured
        foreach ($matches[0] as $m) {
            // determine pattern based on whether variable is wildcard or not
            $mm = str_replace([':', '+'], '', $m);
            $pattern = (strpos($m, '+') === strlen($m) - 1) ? '(?P<'.$mm.'>(.+?[^/]))' : '(?P<'.$mm.'>([^/]+))';
            $requestPattern = str_replace($m, $pattern, $requestPattern);
        }
        $pm = preg_match('#^'.$requestPattern.'$#', $this->request->getPathInfo(), $parameters);
        if (false === $pm) {
            throw new RequestException('regex for path matching failed');
        }
        if (0 === $pm) {
            // request path does not match pattern
            return false;
        }
        foreach ($parameters as $k => $v) {
            if (!is_string($k)) {
                unset($parameters[$k]);
            }
        }
        // request path matches pattern!
        return call_user_func_array($callback, array_values($parameters));
    }
}
