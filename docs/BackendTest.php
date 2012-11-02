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

$config = new \RestService\Utils\Config("config" . DIRECTORY_SEPARATOR . "voot.ini");

$vootStorageBackend = "\\VootProvider\\" . $config->getValue('storageBackend');

try { 
    $vootStorage = new $vootStorageBackend($config);
    $userAttributes = $vootStorage->getUserAttributes($argv[1]);
    var_dump($userAttributes);
    $groupMembership = $vootStorage->isMemberOf($argv[1]);
    var_dump($groupMembership);
} catch (Exception $e) { 
    echo $e->getMessage() . PHP_EOL;
}
?>
