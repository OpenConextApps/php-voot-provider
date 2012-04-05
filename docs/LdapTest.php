<?php

if($argc < 2) {
	echo "specify userId as a parameter" . PHP_EOL;
	exit(1);
}

require_once 'lib/Voot/Provider.php';
$config = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);

$vootStorageBackend = $config['voot']['storageBackend'];
require_once "lib/Voot/$vootStorageBackend.php";

try { 
	$vootStorage = new $vootStorageBackend($config[$vootStorageBackend]);
	$memberShip = $vootStorage->isMemberOf($argv[1]);
	var_dump($memberShip);
} catch (Exception $e) { 
	echo $e->getMessage() . PHP_EOL;
}
?>
