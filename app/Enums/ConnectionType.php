<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConnectionType: string implements HasLabel
{
    case Mongo = 'mongo';
    case Postgres = 'postgres';
    case Mysql = 'mysql';
    case Cloudflare = 'cloudflare';
    case S3Cloudfront = 's3-cloudfront';
    case Cloudinary = 'cloudinary';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mongo => 'MongoDB',
            self::Postgres => 'PostgreSQL',
            self::Mysql => 'MySQL',
            self::Cloudflare => 'Cloudflare R2',
            self::S3Cloudfront => 'S3 / CloudFront',
            self::Cloudinary => 'Cloudinary',
        };
    }

    public function kind(): ConnectionKind
    {
        return match ($this) {
            self::Mongo, self::Postgres, self::Mysql => ConnectionKind::Database,
            self::Cloudflare, self::S3Cloudfront, self::Cloudinary => ConnectionKind::Cdn,
        };
    }

    /** @return array<self> */
    public static function forKind(ConnectionKind $kind): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->kind() === $kind));
    }
}
