<?php

require_once "lib/SplClassLoader.php";

$c1 = new SplClassLoader("RestService", "extlib/php-rest-service/lib");
$c1->register();

$c2 =  new SplClassLoader("VootProvider", "lib");
$c2->register();

use \RestService\Utils\Config as Config;
use \VootProvider\PdoVootStorage as PdoVootStorage;

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");

$storage = new PdoVootStorage($config);
$storage->initDatabase();

$data = file_get_contents("docs/user_attributes.json");
$d = json_decode($data, TRUE);

foreach($d as $v) {
    $storage->addUser($v['id'], $v['displayName'], $v['mail']);
}

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
