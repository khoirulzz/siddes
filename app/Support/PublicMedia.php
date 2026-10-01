<?php

namespace App\Support;

use Illuminate\Support\Str;

class PublicMedia
{
    public static function displayUrl(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $raw) !== 1) {
            return $raw;
        }

        $host = (string) parse_url($raw, PHP_URL_HOST);

        return strcasecmp($host, 'res.cloudinary.com') === 0
            ? (self::brandedCloudinaryUrl($raw) ?? $raw)
            : $raw;
    }

    public static function toUrl(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (Str::startsWith($raw, 'data:')) {
            return $raw;
        }

        if (preg_match('/^https?:\/\//i', $raw) === 1) {
            $incomingHost = (string) parse_url($raw, PHP_URL_HOST);
            if (strcasecmp($incomingHost, 'res.cloudinary.com') === 0) {
                return self::displayUrl($raw);
            }

            $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
            $incomingPath = (string) parse_url($raw, PHP_URL_PATH);
            $normalizedIncomingPath = self::normalizePath($incomingPath);

            $looksLikeLocalStoragePath = $normalizedIncomingPath !== null
                && Str::startsWith(ltrim($incomingPath, '/'), ['storage/', 'public/']);

            if (
                ! $looksLikeLocalStoragePath
                && $incomingHost !== ''
                && $appHost !== ''
                && strcasecmp($incomingHost, $appHost) !== 0
            ) {
                return $raw;
            }

            $raw = $incomingPath;
        }

        $path = self::normalizePath($raw);
        if (! $path) {
            return null;
        }

        if (self::isCloudinaryPath($path)) {
            return self::resolveCloudinaryUrl($path);
        }

        return url('/media/public/' . self::encodePathSegments($path));
    }

    private static function isCloudinaryPath(string $path): bool
    {
        $segments = array_values(array_filter(explode('/', $path), static fn ($segment) => $segment !== ''));
        if (count($segments) < 4) {
            return false;
        }

        $resourceType = $segments[1] ?? '';
        $deliveryType = $segments[2] ?? '';

        return in_array($resourceType, ['image', 'video', 'raw', 'auto'], true)
            && in_array($deliveryType, ['upload', 'private', 'authenticated'], true);
    }

    private static function resolveCloudinaryUrl(string $path): string
    {
        $scheme = config('cloudinary.secure', true) ? 'https' : 'http';
        $url = $scheme . '://res.cloudinary.com/' . ltrim($path, '/');

        return self::brandedCloudinaryUrl($url) ?? $url;
    }

    private static function brandedCloudinaryUrl(string $url): ?string
    {
        $baseUrl = rtrim(trim((string) config('cloudinary.delivery_base_url', '')), '/');
        if ($baseUrl === '' || ! str_starts_with($baseUrl, 'https://')) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = explode('/', ltrim($path, '/'));
        if (
            count($segments) < 4
            || ! in_array($segments[0], ['dcf6mkq3q', 'dzrca841f'], true)
            || $segments[1] !== 'image'
            || $segments[2] !== 'upload'
            || $segments[3] === ''
            || str_contains($path, '..')
            || preg_match('/%2f|%5c|%2e/i', $path) === 1
        ) {
            return null;
        }

        $query = (string) parse_url($url, PHP_URL_QUERY);
        $fragment = (string) parse_url($url, PHP_URL_FRAGMENT);

        return $baseUrl . $path . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : '');
    }

    public static function normalizePath(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $raw) === 1) {
            $raw = (string) parse_url($raw, PHP_URL_PATH);
        }

        $raw = str_replace('\\', '/', $raw);
        $raw = ltrim($raw, '/');

        if (Str::startsWith($raw, 'storage/')) {
            $raw = substr($raw, strlen('storage/'));
        }

        if (Str::startsWith($raw, 'public/')) {
            $raw = substr($raw, strlen('public/'));
        }

        if (Str::startsWith($raw, 'media/public/')) {
            $raw = substr($raw, strlen('media/public/'));
        }

        $raw = trim($raw);
        if ($raw === '' || str_contains($raw, '..')) {
            return null;
        }

        return $raw;
    }

    private static function encodePathSegments(string $path): string
    {
        $segments = array_filter(explode('/', $path), static fn ($segment) => $segment !== '');
        return implode('/', array_map('rawurlencode', $segments));
    }

}
