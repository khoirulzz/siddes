<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CloudinaryService
{
    public function enabled(): bool
    {
        return (bool) config('cloudinary.enabled')
            && $this->cloudName() !== ''
            && $this->apiKey() !== ''
            && $this->apiSecret() !== '';
    }

    public function uploadBinary(
        string $binary,
        string $filename,
        string $folder,
        string $resourceType = 'image',
        ?string $publicId = null
    ): ?array {
        if (! $this->enabled() || $binary === '') {
            return null;
        }

        $resourceType = $this->normalizeResourceType($resourceType);
        $timestamp = time();
        $params = [
            'folder' => trim($folder, '/'),
            'overwrite' => 'true',
            'unique_filename' => 'false',
            'timestamp' => (string) $timestamp,
        ];

        if ($publicId !== null && trim($publicId) !== '') {
            $params['public_id'] = trim($publicId, '/');
        }

        $payload = [
            ...$params,
            'api_key' => $this->apiKey(),
            'signature' => $this->signature($params),
        ];

        $endpoint = $this->uploadEndpoint($resourceType);

        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->attach('file', $binary, $filename)
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            Log::warning('cloudinary upload failed (exception)', ['message' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('cloudinary upload failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        $json = $response->json();
        if (! is_array($json) || empty($json['secure_url'])) {
            Log::warning('cloudinary upload invalid payload', [
                'response' => is_array($json) ? $json : $response->body(),
            ]);

            return null;
        }

        return $json;
    }

    public function uploadPath(
        string $absolutePath,
        string $folder,
        string $resourceType = 'auto',
        ?string $publicId = null
    ): ?array {
        if (! is_file($absolutePath)) {
            return null;
        }

        $binary = @file_get_contents($absolutePath);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        return $this->uploadBinary(
            $binary,
            basename($absolutePath),
            $folder,
            $resourceType,
            $publicId
        );
    }

    public function destroy(string $publicId, string $resourceType = 'image'): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $publicId = trim($publicId, '/');
        if ($publicId === '') {
            return false;
        }

        $resourceType = $this->normalizeResourceType($resourceType);
        $timestamp = time();

        $params = [
            'public_id' => $publicId,
            'invalidate' => 'true',
            'timestamp' => (string) $timestamp,
        ];

        $payload = [
            ...$params,
            'api_key' => $this->apiKey(),
            'signature' => $this->signature($params),
        ];

        $endpoint = $this->destroyEndpoint($resourceType);

        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->asForm()
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            Log::warning('cloudinary destroy failed (exception)', ['message' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('cloudinary destroy failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return false;
        }

        $json = $response->json();
        $result = is_array($json) ? (string) ($json['result'] ?? '') : '';

        return in_array($result, ['ok', 'not found'], true);
    }

    public function destroyByUrl(string $url, ?string $forcedResourceType = null): bool
    {
        $asset = $this->parseAsset($url);
        if (! $asset) {
            return false;
        }

        return $this->destroy(
            $asset['public_id'],
            $forcedResourceType ?: $asset['resource_type']
        );
    }

    public function isCloudinaryUrl(string $value): bool
    {
        if (preg_match('/^https?:\/\//i', $value) !== 1) {
            return false;
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        if ($host === '' || ! str_contains($host, 'res.cloudinary.com')) {
            return false;
        }

        $cloudName = $this->cloudName();
        if ($cloudName === '') {
            return true;
        }

        $path = trim((string) parse_url($value, PHP_URL_PATH), '/');

        return Str::startsWith($path, trim($cloudName, '/') . '/');
    }

    /**
     * @return array{resource_type:string,public_id:string}|null
     */
    private function parseAsset(string $url): ?array
    {
        if (! $this->isCloudinaryUrl($url)) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        $parts = explode('/', $path);
        $uploadIndex = array_search('upload', $parts, true);
        if ($uploadIndex === false || $uploadIndex < 2) {
            return null;
        }

        $resourceType = $this->normalizeResourceType((string) ($parts[$uploadIndex - 1] ?? 'image'));
        $tail = array_slice($parts, $uploadIndex + 1);
        if ($tail === []) {
            return null;
        }

        $versionIndex = null;
        foreach ($tail as $index => $segment) {
            if (preg_match('/^v\d+$/', $segment) === 1) {
                $versionIndex = $index;
                break;
            }
        }

        if ($versionIndex !== null) {
            $tail = array_slice($tail, $versionIndex + 1);
        }

        if ($tail === []) {
            return null;
        }

        $last = array_pop($tail);
        if (! is_string($last) || trim($last) === '') {
            return null;
        }

        $last = preg_replace('/\.[a-z0-9]{1,8}$/i', '', $last) ?: $last;
        $publicId = implode('/', [...$tail, $last]);
        $publicId = trim($publicId, '/');
        if ($publicId === '') {
            return null;
        }

        return [
            'resource_type' => $resourceType,
            'public_id' => $publicId,
        ];
    }

    private function signature(array $params): string
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            if (
                $value === null
                || $value === ''
                || in_array($key, ['file', 'api_key', 'cloud_name', 'resource_type', 'signature'], true)
            ) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $value = implode(',', $value);
            }

            $filtered[$key] = (string) $value;
        }

        ksort($filtered);

        $pairs = [];
        foreach ($filtered as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return sha1(implode('&', $pairs) . $this->apiSecret());
    }

    private function uploadEndpoint(string $resourceType): string
    {
        return sprintf(
            'https://api.cloudinary.com/v1_1/%s/%s/upload',
            $this->cloudName(),
            $resourceType
        );
    }

    private function destroyEndpoint(string $resourceType): string
    {
        return sprintf(
            'https://api.cloudinary.com/v1_1/%s/%s/destroy',
            $this->cloudName(),
            $resourceType
        );
    }

    private function normalizeResourceType(string $resourceType): string
    {
        $normalized = strtolower(trim($resourceType));

        return in_array($normalized, ['image', 'raw', 'video', 'auto'], true)
            ? $normalized
            : 'image';
    }

    private function cloudName(): string
    {
        return trim((string) config('cloudinary.cloud_name', ''));
    }

    private function apiKey(): string
    {
        return trim((string) config('cloudinary.api_key', ''));
    }

    private function apiSecret(): string
    {
        return trim((string) config('cloudinary.api_secret', ''));
    }

    private function timeoutSeconds(): int
    {
        return max(5, (int) config('cloudinary.timeout_seconds', 20));
    }
}
