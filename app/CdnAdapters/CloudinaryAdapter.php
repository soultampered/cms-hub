<?php

namespace App\CdnAdapters;

use App\Exceptions\NotImplementedException;

class CloudinaryAdapter implements CdnAdapter
{
    /** @param array{cloud_name: string, api_key: string, api_secret: string} $config */
    public function __construct(private array $config) {}

    public function testConnection(): bool
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function listAssets(?string $prefix = null): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function uploadAsset(string $path, string $contents, string $contentType): array
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function deleteAsset(string $path): void
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function purgeCache(array $paths): void
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }

    public function getAssetUrl(string $path): string
    {
        throw new NotImplementedException(static::class, __FUNCTION__);
    }
}
