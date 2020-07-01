<?php
require_once "DbDrive.php";

class DefaultDb implements DbDrive
{

    function open(string $table): int
    {
        // TODO: Implement open() method.
    }

    function close(): bool
    {
        // TODO: Implement close() method.
    }

    function insert(string $key, string $data): int
    {
        // TODO: Implement insert() method.
    }

    function find(string $key): string
    {
        // TODO: Implement find() method.
    }

    function delete(string $key): int
    {
        // TODO: Implement delete() method.
    }
}