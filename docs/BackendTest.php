<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

if ($argc < 2) {
    echo "specify userId as a parameter" . PHP_EOL;
    exit(1);
}

use fkooman\Config\Config;

$config = Config::fromIniFile("config" . DIRECTORY_SEPARATOR . "voot.ini");

$vootStorageBackend = "fkooman\\VootProvider\\" . $config->getValue('storageBackend');

try {
    $vootStorage = new $vootStorageBackend($config);
    $userAttributes = $vootStorage->getUserAttributes($argv[1]);
    var_dump($userAttributes);
    $groupMembership = $vootStorage->isMemberOf($argv[1]);
    var_dump($groupMembership);
    if (2 < count($argv)) {
        // second parameter is group identifier we want to query users for
        $groupMembers = $vootStorage->getGroupMembers($argv[1], $argv[2]);
        var_dump($groupMembers);
    }
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
