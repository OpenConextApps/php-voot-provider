<?php
require_once "lib/Config.php";
require_once "lib/Voot/PdoVootStorage.php";

$config = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "voot.ini");

$storage = new PdoVootStorage($config);
$storage->initDatabase();
$storage->updateDatabase();

$storage->addGroup("guests", "Guests", "This is a group containing Guests.");
$storage->addGroup("employees","Employees","This is a group containing Employees.");
$storage->addGroup("students","Students","This is a group containing Students.");

$storage->addMembership("fkooman", "guests", 10);
$storage->addMembership("fkooman", "employees", 20);
$storage->addMembership("fkooman", "students", 50);
$storage->addMembership("john.doe", "guests", 10);
$storage->addMembership("jane.doe", "guests", 10);
$storage->addMembership("weird.guy", "guests", 10);
$storage->addMembership("the.boss", "employees", 50);
$storage->addMembership("the.house.cat", "employees", 10);
$storage->addMembership("nerdy.guy", "students", 10);

?>
