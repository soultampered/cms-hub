<?php

namespace App\CdnAdapters;

interface CdnAdapter
{
    public function testConnection(): bool;

    /** @return array<int, array{path: string, size: int, url: string, updatedAt: string}> */
    public function listAssets(?string $prefix = null): array;

    /** @return array{path: string, size: int, url: string, updatedAt: string} */
    public function uploadAsset(string $path, string $contents, string $contentType): array;

    public function deleteAsset(string $path): void;

    public function purgeCache(array $paths): void;

    public function getAssetUrl(string $path): string;
}
