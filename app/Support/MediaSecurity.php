<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaSecurity
{
    public static function isAllowedPath(string $path): bool
    {
        $prefixes = config('security.media.allowed_prefixes', []);
        if (! is_array($prefixes) || $prefixes === []) {
            return true;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        foreach ($prefixes as $prefix) {
            $normalizedPrefix = trim((string) $prefix);
            if ($normalizedPrefix === '') {
                continue;
            }

            if (Str::startsWith($normalizedPath, ltrim($normalizedPrefix, '/'))) {
                return true;
            }
        }

        return false;
    }

    public static function isAllowedMime(string $mime): bool
    {
        $allowed = config('security.media.allowed_mimes', []);
        if (! is_array($allowed) || $allowed === []) {
            return true;
        }

        return in_array($mime, $allowed, true);
    }

    public static function dispositionForMime(string $mime): string
    {
        $inlineMimes = config('security.media.inline_mimes', []);
        if (is_array($inlineMimes) && in_array($mime, $inlineMimes, true)) {
            return 'inline';
        }

        return 'attachment';
    }
}
