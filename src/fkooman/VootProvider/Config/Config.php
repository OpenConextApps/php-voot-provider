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

namespace fkooman\VootProvider\Config;

class Config
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function fromIniFile($configFile)
    {
        $fileContent = @file_get_contents($configFile);
        if (false === $fileContent) {
            throw new ConfigException(sprintf("unable to read configuration file '%s'", $configFile));
        }
        $configData = @parse_ini_string($fileContent, true);
        if (false === $configData) {
            throw new ConfigException(sprintf("unable to parse configuration file '%s'", $configFile));
        }

        return new static($configData);
    }

    public function getSubtree($section, $required = false, array $default = [])
    {
        if (!array_key_exists($section, $this->config)) {
            if ($required) {
                throw new ConfigException(sprintf("subtree '%s' does not exist", $section));
            }

            return new static($default);
        }
        if (!is_array($this->config[$section])) {
            throw new ConfigException(sprintf("'%s' is not a subtree", $section));
        }

        return new static($this->config[$section]);
    }

    public function s($section, $required = false, array $default = [])
    {
        return $this->getSubtree($section, $required, $default);
    }

    public function getSection($section, $required = false, array $default = [])
    {
        return $this->getSubtree($section, $required, $default);
    }

    public function getLeaf($key, $required = false, $default = null)
    {
        if (!array_key_exists($key, $this->config)) {
            if ($required) {
                throw new ConfigException(sprintf("required leaf '%s' does not exist", $key));
            }

            return $default;
        }
        if (is_array($this->config[$key])) {
            throw new ConfigException(sprintf("'%s' is a subtree", $key));
        }

        return $this->config[$key];
    }

    public function getValue($key, $required = false, $default = null)
    {
        return $this->getLeaf($key, $required, $default);
    }

    public function l($key, $required = false, $default = null)
    {
        return $this->getLeaf($key, $required, $default);
    }

    public function toArray()
    {
        return $this->config;
    }
}
