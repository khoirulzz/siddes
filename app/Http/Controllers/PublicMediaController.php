<?php

namespace App\Http\Controllers;

use App\Support\MediaSecurity;
use App\Support\PublicMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $normalizedPath = PublicMedia::normalizePath($path);
        abort_if(! $normalizedPath, 404);
        abort_unless(MediaSecurity::isAllowedPath($normalizedPath), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($normalizedPath), 404);
        $mime = $disk->mimeType($normalizedPath) ?: 'application/octet-stream';
        abort_unless(MediaSecurity::isAllowedMime($mime), 404);

        return response()->file($disk->path($normalizedPath), [
            'Content-Type' => $mime,
            'Content-Disposition' => MediaSecurity::dispositionForMime($mime) . '; filename="' . basename($normalizedPath) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
