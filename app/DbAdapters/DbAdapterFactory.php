<?php

namespace App\DbAdapters;

use App\Enums\ConnectionType;
use App\Models\Connection;
use InvalidArgumentException;

class DbAdapterFactory
{
    public static function make(Connection $connection): DatabaseAdapter
    {
        return match ($connection->type) {
            ConnectionType::Mongo => new MongoAdapter($connection->config),
            ConnectionType::Postgres => new PostgresAdapter($connection->config),
            ConnectionType::Mysql => new MysqlAdapter($connection->config),
            default => throw new InvalidArgumentException("{$connection->type->value} is not a database connection type."),
        };
    }
}
