<?php

namespace App\DbAdapters;

use App\Exceptions\NotImplementedException;

class PostgresAdapter implements DatabaseAdapter
{
    /** @param array{host: string, port: string, database: string, username: string, password: string} $config */
    public function __construct(private array $config) {}

    public function testConnection(): bool
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function listCollections(): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function getSchema(string $collection): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function listRecords(string $collection, array $opts): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function getRecord(string $collection, string $id): ?array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function createRecord(string $collection, array $data): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function updateRecord(string $collection, string $id, array $data): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function deleteRecord(string $collection, string $id): void
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }
}
