<?php
require_once "src/drive/FileDb.php";
require_once "src/drive/MysqlDb.php";
require_once "src/drive/DefaultDb.php";
require_once "src/drive/DbDrive.php";

class DriveFactory
{
    public static function getDB(string $type): DbDrive
    {
        switch ($type)
        {
            case "file":
                return new FileDb("source/");
                break;
            case "mysql":
                return new MysqlDb();
                break;
            default:
                return new DefaultDb();
                break;
        }
    }
}