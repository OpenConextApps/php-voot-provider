<?php

require_once "lib" . DIRECTORY_SEPARATOR . "SplClassLoader.php";
$c =  new SplClassLoader("Tuxed", "lib");
$c->register();

use \Tuxed\Config as Config;
use \Tuxed\Voot\PdoVootStorage as PdoVootStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");

$storage = new PdoVootStorage($config);
$storage->initDatabase();

$data = file_get_contents("docs/group_membership.json");
$d = json_decode($data, TRUE);

foreach($d as $v) {
	$storage->addGroup($v['id'], $v['name'], $v['description']);
	foreach($v['members'] as $m) {
		$storage->addMembership($m['id'], $v['id'], roleToInt($m['role']));
	}
}

function roleToInt($role) {
        switch($role) {
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

?>
