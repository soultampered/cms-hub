<?php

namespace App\DbAdapters;

interface DatabaseAdapter
{
    public function testConnection(): bool;

    /** @return string[] table names for SQL, collection names for Mongo */
    public function listCollections(): array;

    /** @return array<int, array{name: string, type: string, nullable: bool}> best-effort field introspection */
    public function getSchema(string $collection): array;

    /**
     * @param array{filter?: array<string, mixed>, sort?: string, page: int, pageSize: int} $opts
     * @return array{records: array<int, array<string, mixed>>, total: int}
     */
    public function listRecords(string $collection, array $opts): array;

    /** @return array<string, mixed>|null */
    public function getRecord(string $collection, string $id): ?array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createRecord(string $collection, array $data): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateRecord(string $collection, string $id, array $data): array;

    public function deleteRecord(string $collection, string $id): void;
}
