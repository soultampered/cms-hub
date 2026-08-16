<?php

namespace App\CdnAdapters;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Http;

class CloudflareAdapter implements CdnAdapter
{
    private S3Client $client;

    /**
     * @param array{
     *     access_key_id: string,
     *     secret_access_key: string,
     *     bucket: string,
     *     endpoint: string,
     *     public_base_url: string,
     *     zone_id: string,
     *     api_token: string,
     * } $config
     */
    public function __construct(private array $config)
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $config['access_key_id'],
                'secret' => $config['secret_access_key'],
            ],
        ]);
    }

    public function testConnection(): bool
    {
        $this->client->headBucket(['Bucket' => $this->config['bucket']]);

        return true;
    }

    public function listAssets(?string $prefix = null): array
    {
        $result = $this->client->listObjectsV2([
            'Bucket' => $this->config['bucket'],
            'Prefix' => $prefix ?? '',
            'MaxKeys' => 100,
        ]);

        return collect($result['Contents'] ?? [])
            ->map(fn ($object) => [
                'path' => $object['Key'],
                'size' => $object['Size'],
                'url' => $this->getAssetUrl($object['Key']),
                'updatedAt' => $object['LastModified']->format(DATE_ATOM),
            ])
            ->all();
    }

    public function uploadAsset(string $path, string $contents, string $contentType): array
    {
        $this->client->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $path,
            'Body' => $contents,
            'ContentType' => $contentType,
        ]);

        $head = $this->client->headObject(['Bucket' => $this->config['bucket'], 'Key' => $path]);

        return [
            'path' => $path,
            'size' => $head['ContentLength'],
            'url' => $this->getAssetUrl($path),
            'updatedAt' => $head['LastModified']->format(DATE_ATOM),
        ];
    }

    public function deleteAsset(string $path): void
    {
        $this->client->deleteObject(['Bucket' => $this->config['bucket'], 'Key' => $path]);
    }

    public function purgeCache(array $paths): void
    {
        $urls = array_map(fn (string $path) => $this->getAssetUrl($path), $paths);

        $response = Http::withToken($this->config['api_token'])
            ->post("https://api.cloudflare.com/client/v4/zones/{$this->config['zone_id']}/purge_cache", [
                'files' => $urls,
            ]);

        $response->throw();
    }

    public function getAssetUrl(string $path): string
    {
        return rtrim($this->config['public_base_url'], '/').'/'.ltrim($path, '/');
    }
}
