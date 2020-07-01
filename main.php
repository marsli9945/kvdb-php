<?php
require_once "src/db.php";
require_once  "src/drive/FileDb.php";
require_once  "src/drive/DbDrive.php";
require_once  "src/drive/MysqlDb.php";
require_once  "src/factory/DriveFactory.php";

function debug($value) {
    print_r($value);
    echo PHP_EOL;
}

$db = new db(DriveFactory::getDB("mysql"));

$db->open("user");

$user = [
    "name" => "Tom",
    "age" => 33,
    "sex" => 2
];

//debug($db->delete($user['name']));
//debug($db->insertArray($user["name"],$user));
debug($db->findArray($user["name"]));
//debug($db->insert($user["name"],json_encode($user)));
//debug($db->find($user["name"]));
//debug($db->delete($user["name"]));
//debug($db->find($user["name"]));
