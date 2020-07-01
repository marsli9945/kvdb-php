<?php


interface DbDrive
{
    const DB_INSERT = 1;
    const DB_REPLACE = 2;
    const DB_STORE = 3;

    const DB_BUCKET_SIZE = 262144;
    const DB_KEY_SIZE = 128;
    const DB_INDEX_SIZE = self::DB_KEY_SIZE + 12;

    const DB_KEY_EXISTS = 1;
    const DB_FAILURE = -1;
    const DB_SUCCESS = 0;

    function open(string $table): int ;

    function close(): bool;

    function insert(string $key, string $data): int;

    function find(string $key): string;

    function delete(string $key): int;
}