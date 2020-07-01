<?php
require_once "DbDrive.php";

class MysqlDb implements DbDrive
{
    private $table;
    private $dbh;

    public function __construct()
    {
        $this->dbh = new PDO("mysql:host=192.168.10.143;dbname=grow_analytics", "root", "tuyou@123game");
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    function open(string $table): int
    {
        $this->table = $table;
        return self::DB_SUCCESS;
    }

    function close(): bool
    {
        if ($this->dbh) {
            $this->table = "";
            $this->dbh = null;
        }
        return true;
    }

    private function select(string $table,string $key) {
        $sql = "select * from cache where `table`='{$table}' and `key`='{$key}'";
        $query = $this->dbh->query($sql);
        $list = [];
        while ($row = $query->fetch(PDO::FETCH_OBJ)){
            $list[] = (array)$row;
        }
        return $list;
    }

    function insert(string $key, string $data): int
    {
        if (!empty($this->select($this->table,$key))){
            return self::DB_KEY_EXISTS;
        }

        try
        {
            $sql = "insert into cache (`table`, `key`, `value`) values ('{$this->table}','{$key}','{$data}')";
            $this->dbh->exec($sql);
            return self::DB_SUCCESS;
        } catch (\mysql_xdevapi\Exception $exception) {
            echo $exception->getMessage();
            return self::DB_FAILURE;
        }

    }

    function find(string $key): string
    {
        return $this->select($this->table,$key)[0]['value'];
    }

    function delete(string $key): int
    {
        try
        {
            $sql = "delete from cache where `table`='{$this->table}' and `key`='{$key}'";
            $this->dbh->exec($sql);
            return self::DB_SUCCESS;
        } catch (\mysql_xdevapi\Exception $exception) {
            echo $exception->getMessage();
            return self::DB_FAILURE;
        }

    }
}