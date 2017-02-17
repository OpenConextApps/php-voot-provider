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

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\VootProvider\Config\Config;
use fkooman\VootProvider\PdoVootStorage;

$config = Config::fromIniFile(dirname(__DIR__) . '/config/voot.ini');

$storage = new PdoVootStorage($config);

$data = file_get_contents(dirname(__DIR__) . '/schema/user_attributes.json');
$d = json_decode($data, true);

foreach ($d as $v) {
    $storage->addUser($v['id'], $v['displayName'], $v['mail']);
}

$data = file_get_contents(dirname(__DIR__) . '/schema/group_membership.json');
$d = json_decode($data, true);

foreach ($d as $v) {
    $storage->addGroup($v['id'], $v['name'], $v['description']);
    foreach ($v['members'] as $m) {
        $storage->addMembership($m['id'], $v['id'], roleToInt($m['role']));
    }
}

function roleToInt($role)
{
    switch ($role) {
        case "member":
            return 10;
        case "admin":
            return 50;
        case "manager":
            return 20;
        default:
            die("invalid role");
    }
}
