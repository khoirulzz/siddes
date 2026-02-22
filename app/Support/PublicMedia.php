<?php

namespace App\Support;

use Illuminate\Support\Str;

class PublicMedia
{
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

        return url('/media/public/' . self::encodePathSegments($path));
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
