<?php

namespace App\CdnAdapters;

use App\Enums\ConnectionType;
use App\Models\Connection;
use InvalidArgumentException;

class CdnAdapterFactory
{
    public static function make(Connection $connection): CdnAdapter
    {
        return match ($connection->type) {
            ConnectionType::Cloudflare => new CloudflareAdapter($connection->config),
            ConnectionType::S3Cloudfront => new S3CloudfrontAdapter($connection->config),
            ConnectionType::Cloudinary => new CloudinaryAdapter($connection->config),
            default => throw new InvalidArgumentException("{$connection->type->value} is not a CDN connection type."),
        };
    }
}
