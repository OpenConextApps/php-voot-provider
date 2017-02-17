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
require_once dirname(__DIR__).'/vendor/autoload.php';

if ($argc < 2) {
    echo 'specify userId as a parameter'.PHP_EOL;
    exit(1);
}

use fkooman\VootProvider\Config\Config;

$config = Config::fromIniFile(dirname(__DIR__).'/config/voot.ini');

$vootStorageBackend = 'fkooman\\VootProvider\\'.$config->getValue('storageBackend');

try {
    $vootStorage = new $vootStorageBackend($config);
    $groupMembership = $vootStorage->isMemberOf($argv[1]);
    var_dump($groupMembership);
    if (2 < count($argv)) {
        // second parameter is group identifier we want to query users for
        $groupMembers = $vootStorage->getGroupMembers($argv[1], $argv[2]);
        var_dump($groupMembers);
    }
} catch (Exception $e) {
    echo $e->getMessage().PHP_EOL;
}
