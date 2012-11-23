<?php

if($argc < 2) {
    echo "specify userId as a parameter" . PHP_EOL;
    exit(1);
}

require_once "lib/SplClassLoader.php";

$c1 = new SplClassLoader("RestService", "extlib/php-rest-service/lib");
$c1->register();

$c2 =  new SplClassLoader("VootProvider", "lib");
$c2->register();

use \VootProvider\VootStorageException as VootStorageException;

$config = new \RestService\Utils\Config("config" . DIRECTORY_SEPARATOR . "voot.ini");

$vootStorageBackend = "\\VootProvider\\" . $config->getValue('storageBackend');

try {
    $vootStorage = new $vootStorageBackend($config);
    $userAttributes = $vootStorage->getUserAttributes($argv[1]);
    var_dump($userAttributes);
    $groupMembership = $vootStorage->isMemberOf($argv[1]);
    var_dump($groupMembership);
    if(2 < count($argv)) {
        // second parameter is group identifier we want to query users for
        $groupMembers = $vootStorage->getGroupMembers($argv[1], $argv[2]);
        var_dump($groupMembers);
    }
} catch (VootStorageException $e) { 
    echo $e->getLogMessage();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
