<?php

if($argc < 2) {
    echo "specify userId as a parameter" . PHP_EOL;
    exit(1);
}

require_once "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

$config = new \Tuxed\Config("config" . DIRECTORY_SEPARATOR . "voot.ini");

$vootStorageBackend = "\\Tuxed\\Voot\\" . $config->getValue('storageBackend');

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
