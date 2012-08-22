<?php

if($argc < 2) {
	echo "specify userId as a parameter" . PHP_EOL;
	exit(1);
}

require_once "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

$config = parse_ini_file("config" . DIRECTORY_SEPARATOR . "voot.ini", TRUE);

$vootStorageBackend = "\\Tuxed\\Voot\\" . $config['voot']['storageBackend'];

try { 
	$vootStorage = new $vootStorageBackend($config[$vootStorageBackend]);
	$memberShip = $vootStorage->isMemberOf($argv[1]);
	var_dump($memberShip);
} catch (Exception $e) { 
	echo $e->getMessage() . PHP_EOL;
}
?>
