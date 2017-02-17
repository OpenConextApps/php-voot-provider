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

namespace fkooman\VootProvider\Http;

class Request
{
    protected $uri;
    protected $method;
    protected $headers;

    /** @var null|string */
    protected $content;
    protected $pathInfo;
    protected $basicAuthUser;
    protected $basicAuthPass;

    public function __construct($requestUri, $requestMethod = 'GET')
    {
        $this->setRequestUri(new Uri($requestUri));
        $this->setRequestMethod($requestMethod);
        $this->headers = [];
        $this->content = null;
        $this->pathInfo = null;
        $this->basicAuthUser = null;
        $this->basicAuthPass = null;
    }

    public function __toString()
    {
        $s = PHP_EOL;
        $s .= '*Request*'.PHP_EOL;
        $s .= 'Request Method: '.$this->getRequestMethod().PHP_EOL;
        $s .= 'Request URI: '.$this->getRequestUri()->getUri().PHP_EOL;
        if (null !== $this->getBasicAuthUser()) {
            $s .= 'Basic Authentication: '.$this->getBasicAuthUser().':'.$this->getBasicAuthPass();
        }
        $s .= 'Headers:'.PHP_EOL;
        foreach ($this->getHeaders(true) as $v) {
            $s .= "\t".$v.PHP_EOL;
        }
        $s .= 'Content:'.PHP_EOL;
        $s .= $this->getContent();

        return $s;
    }

    public static function fromIncomingRequest(IncomingRequest $i)
    {
        $request = new static($i->getRequestUri(), $i->getRequestMethod());
        $request->setHeaders($i->getRequestHeaders());
        $request->setContent($i->getContent());
        $request->setPathInfo($i->getPathInfo());
        $request->setBasicAuthUser($i->getBasicAuthUser());
        $request->setBasicAuthPass($i->getBasicAuthPass());

        return $request;
    }

    public function setRequestUri(Uri $u)
    {
        $this->uri = $u;
    }

    public function getRequestUri()
    {
        return $this->uri;
    }

    public function setRequestMethod($method)
    {
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'])) {
            throw new RequestException('invalid or unsupported request method');
        }
        $this->method = $method;
    }

    public function getRequestMethod()
    {
        return $this->method;
    }

    public function setPostParameters(array $parameters)
    {
        if ($this->getRequestMethod() !== 'POST') {
            throw new RequestException('request method should be POST');
        }
        $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->setContent(http_build_query($parameters, '', '&'));
    }

    public function getQueryParameters()
    {
        if ($this->uri->getQuery() === null) {
            return [];
        }
        $parameters = [];
        parse_str($this->uri->getQuery(), $parameters);

        return $parameters;
    }

    public function getQueryParameter($key)
    {
        $parameters = $this->getQueryParameters();

        return (array_key_exists($key, $parameters) && 0 !== strlen($parameters[$key])) ? $parameters[$key] : null;
    }

    public function getPostParameter($key)
    {
        $parameters = $this->getPostParameters();

        return (array_key_exists($key, $parameters) && 0 !== strlen($parameters[$key])) ? $parameters[$key] : null;
    }

    public function getPostParameters()
    {
        if ($this->getRequestMethod() !== 'POST') {
            throw new RequestException('request method should be POST');
        }
        // FIXME: we should check to see if it was a proper FORM post!
        $parameters = [];
        parse_str($this->getContent(), $parameters);

        return $parameters;
    }

    public function setHeaders(array $headers)
    {
        foreach ($headers as $k => $v) {
            $this->setHeader($k, $v);
        }
    }

    public function setHeader($key, $value)
    {
        $k = self::normalizeHeaderKey($key);
        $this->headers[$k] = $value;
    }

    public function getHeader($key)
    {
        $k = self::normalizeHeaderKey($key);

        return array_key_exists($k, $this->headers) ? $this->headers[$k] : null;
    }

    public function getHeaders($formatted = false)
    {
        if (!$formatted) {
            return $this->headers;
        }
        $hdrs = [];
        foreach ($this->headers as $k => $v) {
            array_push($hdrs, $k.': '.$v);
        }

        return $hdrs;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContentType($contentType)
    {
        $this->setHeader('Content-Type', $contentType);
    }

    public function getContentType()
    {
        return $this->getHeader('Content-Type');
    }

    public function setPathInfo($pathInfo)
    {
        $this->pathInfo = $pathInfo;
    }

    public function getPathInfo()
    {
        return $this->pathInfo;
    }

    public function setBasicAuthUser($u)
    {
        $this->basicAuthUser = $u;
    }

    public function setBasicAuthPass($p)
    {
        $this->basicAuthPass = $p;
    }

    public function getBasicAuthUser()
    {
        return $this->basicAuthUser;
    }

    public function getBasicAuthPass()
    {
        return $this->basicAuthPass;
    }

    public static function normalizeHeaderKey($key)
    {
        // strip HTTP_ if needed
        if (0 === strpos($key, 'HTTP_') || 0 === strpos($key, 'HTTP-')) {
            $key = substr($key, 5);
        }
        // convert to capitals and replace '-' with '_'
        return strtoupper(str_replace('-', '_', $key));
    }
}
