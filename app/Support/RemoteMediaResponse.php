<?php

namespace App\Support;

use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class RemoteMediaResponse
{
    public static function fromUrl(
        string $url,
        string $filename,
        string $fallbackMime,
        bool $download = false,
        ?CloudinaryService $cloudinaryService = null
    ): Response {
        $resolvedUrl = $cloudinaryService?->deliveryUrl($url) ?: $url;

        try {
            $remoteResponse = Http::timeout(45)->get($resolvedUrl);
        } catch (\Throwable) {
            abort(404);
        }

        abort_unless($remoteResponse->successful(), 404);

        $contentType = trim((string) $remoteResponse->header('Content-Type'));
        $contentType = $contentType !== '' ? $contentType : trim($fallbackMime);
        $contentType = $contentType !== '' ? $contentType : 'application/octet-stream';
        abort_unless(MediaSecurity::isAllowedMime($contentType), 404);

        return response($remoteResponse->body(), 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => ($download ? 'attachment' : MediaSecurity::dispositionForMime($contentType)) . '; filename="' . self::safeFilename($filename) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function safeFilename(string $filename): string
    {
        $clean = trim(str_replace(["\r", "\n", '"'], '', $filename));

        return $clean !== '' ? $clean : 'dokumen';
    }
}
