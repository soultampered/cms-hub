<?php

namespace App\DbAdapters;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\Exception as MongoDriverException;

class MongoAdapter implements DatabaseAdapter
{
    private Client $client;

    private Database $database;

    /** @param array{uri: string, database: string} $config */
    public function __construct(array $config)
    {
        $this->client = new Client($config['uri']);
        $this->database = $this->client->selectDatabase($config['database']);
    }

    public function testConnection(): bool
    {
        try {
            $this->database->command(['ping' => 1]);

            return true;
        } catch (MongoDriverException $e) {
            throw new \RuntimeException($e->getMessage(), previous: $e);
        }
    }

    public function listCollections(): array
    {
        $names = [];

        foreach ($this->database->listCollections() as $info) {
            if (! str_starts_with($info->getName(), 'system.')) {
                $names[] = $info->getName();
            }
        }

        sort($names);

        return $names;
    }

    public function getSchema(string $collection): array
    {
        $sample = $this->database->selectCollection($collection)->find([], [
            'limit' => 50,
            'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        ]);

        $fields = [];
        $sampleCount = 0;

        foreach ($sample as $doc) {
            $sampleCount++;
            foreach ($doc as $key => $value) {
                if (! isset($fields[$key])) {
                    $fields[$key] = ['type' => $this->bsonType($value), 'seenIn' => 0];
                }
                $fields[$key]['seenIn']++;
            }
        }

        return collect($fields)
            ->map(fn (array $f, string $name) => [
                'name' => $name,
                'type' => $f['type'],
                'nullable' => $name !== '_id' && $f['seenIn'] < $sampleCount,
            ])
            ->values()
            ->all();
    }

    public function listRecords(string $collection, array $opts): array
    {
        $filter = $this->buildFilter($opts['filter'] ?? []);
        $page = max(1, $opts['page'] ?? 1);
        $pageSize = $opts['pageSize'] ?? 25;

        $options = [
            'skip' => ($page - 1) * $pageSize,
            'limit' => $pageSize,
            'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        ];

        if (! empty($opts['sort'])) {
            [$field, $direction] = array_pad(explode(':', $opts['sort'], 2), 2, 'asc');
            $options['sort'] = [$field => $direction === 'desc' ? -1 : 1];
        }

        $collectionHandle = $this->database->selectCollection($collection);

        $records = [];
        foreach ($collectionHandle->find($filter, $options) as $doc) {
            $records[] = $this->normalize($doc);
        }

        return [
            'records' => $records,
            'total' => $collectionHandle->countDocuments($filter),
        ];
    }

    public function getRecord(string $collection, string $id): ?array
    {
        $doc = $this->database->selectCollection($collection)->findOne(
            ['_id' => $this->toObjectId($id)],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );

        return $doc ? $this->normalize($doc) : null;
    }

    public function createRecord(string $collection, array $data): array
    {
        unset($data['_id']);
        $result = $this->database->selectCollection($collection)->insertOne($data);

        return $this->getRecord($collection, (string) $result->getInsertedId());
    }

    public function updateRecord(string $collection, string $id, array $data): array
    {
        unset($data['_id']);
        $this->database->selectCollection($collection)->updateOne(
            ['_id' => $this->toObjectId($id)],
            ['$set' => $data]
        );

        return $this->getRecord($collection, $id);
    }

    public function deleteRecord(string $collection, string $id): void
    {
        $this->database->selectCollection($collection)->deleteOne(['_id' => $this->toObjectId($id)]);
    }

    private function toObjectId(string $id): ObjectId|string
    {
        return preg_match('/^[a-f0-9]{24}$/i', $id) ? new ObjectId($id) : $id;
    }

    /** @param array<string, mixed> $filter */
    private function buildFilter(array $filter): array
    {
        return collect($filter)
            ->mapWithKeys(fn ($value, $key) => [$key === 'id' ? '_id' : $key => $value])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array<string, mixed>
     */
    private function normalize(array $doc): array
    {
        $normalized = [];

        foreach ($doc as $key => $value) {
            $normalized[$key === '_id' ? 'id' : $key] = match (true) {
                $value instanceof ObjectId => (string) $value,
                $value instanceof UTCDateTime => $value->toDateTime()->format(DATE_ATOM),
                is_array($value) => $this->normalize($value),
                default => $value,
            };
        }

        return $normalized;
    }

    private function bsonType(mixed $value): string
    {
        return match (true) {
            $value instanceof ObjectId => 'id',
            $value instanceof UTCDateTime => 'datetime',
            is_bool($value) => 'boolean',
            is_int($value) || is_float($value) => 'number',
            is_array($value) => 'array',
            $value === null => 'null',
            default => 'string',
        };
    }
}
