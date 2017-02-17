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

class Uri
{
    private $uriParts;

    public function __construct($inputUri)
    {
        $this->validateUri($inputUri);
        $this->setUriParts($inputUri);
    }

    public function getScheme()
    {
        return array_key_exists('scheme', $this->uriParts) ? $this->uriParts['scheme'] : null;
    }

    public function getUser()
    {
        return array_key_exists('user', $this->uriParts) ? $this->uriParts['user'] : null;
    }

    public function getPass()
    {
        return array_key_exists('pass', $this->uriParts) ? $this->uriParts['pass'] : null;
    }

    public function getHost()
    {
        return array_key_exists('host', $this->uriParts) ? $this->uriParts['host'] : null;
    }

    public function getPort()
    {
        return array_key_exists('port', $this->uriParts) ? $this->uriParts['port'] : null;
    }

    public function getPath()
    {
        return array_key_exists('path', $this->uriParts) ? $this->uriParts['path'] : null;
    }

    public function getQuery()
    {
        return array_key_exists('query', $this->uriParts) ? $this->uriParts['query'] : null;
    }

    public function setQuery($query)
    {
        $this->uriParts['query'] = $query;
    }

    public function appendQuery($query)
    {
        if ($this->getQuery() === null) {
            $this->setQuery($query);
        } else {
            $this->setQuery($this->getQuery().'&'.$query);
        }
    }

    public function getFragment()
    {
        return array_key_exists('fragment', $this->uriParts) ? $this->uriParts['fragment'] : null;
    }

    public function setFragment($fragment)
    {
        $this->uriParts['fragment'] = $fragment;
    }

    public function getUri()
    {
        $uri = $this->constructUriFromParts();
        $this->validateUri($uri);

        return $uri;
    }

    private function validateUri($uri)
    {
        $u = filter_var($uri, FILTER_VALIDATE_URL);
        if ($u === false) {
            throw new UriException('the uri is malformed');
        }
    }

    private function setUriParts($uri)
    {
        $this->uriParts = parse_url($uri);
    }

    private function constructUriFromParts()
    {
        $uri = '';
        if (null !== $this->getScheme()) {
            $uri .= $this->getScheme().'://';
        }
        if (null !== $this->getUser()) {
            $uri .= $this->getUser();
            if (null !== $this->getPass()) {
                $uri .= ':'.$this->getPass();
            }
            $uri .= '@';
        }
        if (null !== $this->getHost()) {
            $uri .= $this->getHost();
        }
        if (null !== $this->getPort()) {
            $uri .= ':'.$this->getPort();
        }
        if (null !== $this->getPath()) {
            $uri .= $this->getPath();
        }
        if (null !== $this->getQuery()) {
            $uri .= '?'.$this->getQuery();
        }
        if (null !== $this->getFragment()) {
            $uri .= '#'.$this->getFragment();
        }

        return $uri;
    }
}
