<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\Config\Config;
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
