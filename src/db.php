<?php
require_once "drive/DbDrive.php";


class db
{
    private $drive;

    public function __construct(DbDrive $drive)
    {
        $this->drive = $drive;
    }

    function open(string $table): int
    {
        return $this->drive->open($table);
    }

    function close(): bool
    {
        return $this->drive->close();
    }

    function insert(string $key, string $data): int
    {
        return $this->drive->insert($key, $data);
    }

    function insertArray(string $key, array $data): int
    {
        return $this->drive->insert($key, json_encode($data));
    }

    function find(string $key): string
    {
        return $this->drive->find($key);
    }

    function findArray(string $key): array
    {
        return json_decode($this->drive->find($key), true) ?: [];
    }

    function delete(string $key): int
    {
        return $this->drive->delete($key);
    }
}