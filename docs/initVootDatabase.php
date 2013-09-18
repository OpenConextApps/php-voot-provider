<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use fkooman\Config\Config;
use fkooman\VootProvider\PdoVootStorage;

$config = Config::fromIniFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");

$storage = new PdoVootStorage($config);
$storage->initDatabase();

$data = file_get_contents("docs/user_attributes.json");
$d = json_decode($data, TRUE);

foreach ($d as $v) {
    $storage->addUser($v['id'], $v['displayName'], $v['mail']);
}

$data = file_get_contents("docs/group_membership.json");
$d = json_decode($data, TRUE);

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
