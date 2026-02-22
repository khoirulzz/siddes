<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageUploadService
{
    public function storeOptimized(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1600,
        int $maxHeight = 1600,
        int $quality = 78
    ): string {
        $directory = trim($directory, '/');
        $quality = max(50, min(90, $quality));
        $path = $file->getRealPath();

        if (! $path || ! extension_loaded('gd')) {
            return $file->store($directory, 'public');
        }

        try {
            $binary = @file_get_contents($path);
            if (! is_string($binary) || $binary === '') {
                return $file->store($directory, 'public');
            }

            $source = @imagecreatefromstring($binary);
            if (! $source) {
                return $file->store($directory, 'public');
            }

            $originalWidth = imagesx($source);
            $originalHeight = imagesy($source);
            if ($originalWidth < 1 || $originalHeight < 1) {
                imagedestroy($source);
                return $file->store($directory, 'public');
            }

            $ratio = min(
                1,
                $maxWidth > 0 ? ($maxWidth / $originalWidth) : 1,
                $maxHeight > 0 ? ($maxHeight / $originalHeight) : 1
            );

            $targetWidth = max(1, (int) round($originalWidth * $ratio));
            $targetHeight = max(1, (int) round($originalHeight * $ratio));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $originalWidth,
                $originalHeight
            );

            [$encoded, $extension] = $this->encodeImage($canvas, $quality);

            imagedestroy($source);
            imagedestroy($canvas);

            if (! is_string($encoded) || $encoded === '') {
                return $file->store($directory, 'public');
            }

            $filename = Str::ulid() . '.' . $extension;
            $storagePath = $directory . '/' . $filename;
            Storage::disk('public')->put($storagePath, $encoded);

            return $storagePath;
        } catch (Throwable) {
            return $file->store($directory, 'public');
        }
    }

    /**
     * @param \GdImage $image
     * @return array{0:string,1:string}
     */
    private function encodeImage(\GdImage $image, int $quality): array
    {
        ob_start();

        if (function_exists('imagewebp') && @imagewebp($image, null, $quality)) {
            $data = (string) ob_get_clean();

            return [$data, 'webp'];
        }

        ob_end_clean();
        ob_start();

        if (@imagejpeg($image, null, $quality)) {
            $data = (string) ob_get_clean();

            return [$data, 'jpg'];
        }

        ob_end_clean();

        return ['', 'jpg'];
    }
}

