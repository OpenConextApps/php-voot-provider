<?php
require_once 'lib/Voot/Provider.php';
$config = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);

$vootStorageBackend = $config['voot']['storageBackend'];
require_once "lib/Voot/$vootStorageBackend.php";
$vootStorage = new $vootStorageBackend($config[$vootStorageBackend]);

var_dump($vootStorage->isMemberOf($argv[1]));

?>
