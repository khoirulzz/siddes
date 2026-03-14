<?php

namespace App\Services;

use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImageUploadService
{
    public function __construct(private readonly CloudinaryService $cloudinaryService)
    {
    }

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
            $cloudPath = $this->storeImageInCloudinary($file, $directory);
            if ($cloudPath !== null) {
                return $cloudPath;
            }

            return $file->store($directory, 'public');
        }

        try {
            $binary = @file_get_contents($path);
            if (! is_string($binary) || $binary === '') {
                return $this->storeFallback($file, $directory);
            }

            $source = @imagecreatefromstring($binary);
            if (! $source) {
                return $this->storeFallback($file, $directory);
            }

            $originalWidth = imagesx($source);
            $originalHeight = imagesy($source);
            if ($originalWidth < 1 || $originalHeight < 1) {
                imagedestroy($source);

                return $this->storeFallback($file, $directory);
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
                return $this->storeFallback($file, $directory);
            }

            $filename = Str::ulid() . '.' . $extension;
            $cloudPath = $this->storeEncodedImageInCloudinary($encoded, $filename, $directory);
            if ($cloudPath !== null) {
                return $cloudPath;
            }

            $storagePath = $directory . '/' . $filename;
            Storage::disk('public')->put($storagePath, $encoded);

            return $storagePath;
        } catch (Throwable) {
            return $this->storeFallback($file, $directory);
        }
    }

    public function storeFile(UploadedFile $file, string $directory, string $resourceType = 'raw'): string
    {
        $directory = trim($directory, '/');

        if ($this->cloudinaryService->enabled()) {
            $publicId = $this->cloudinaryPublicId($file, $resourceType);
            $cloudUpload = $this->cloudinaryService->uploadPath(
                $file->getRealPath() ?: '',
                $this->dynamicFolder($directory),
                $resourceType,
                $publicId
            );

            if (is_array($cloudUpload) && ! empty($cloudUpload['secure_url'])) {
                return (string) $cloudUpload['secure_url'];
            }
        }

        return $file->store($directory, 'public');
    }

    public function delete(string $value, ?string $resourceType = null): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        if ($this->cloudinaryService->isCloudinaryUrl($value)) {
            $this->cloudinaryService->destroyByUrl($value, $resourceType);

            return;
        }

        $path = PublicMedia::normalizePath($value);
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
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

    private function storeEncodedImageInCloudinary(string $encoded, string $filename, string $directory): ?string
    {
        if (! $this->cloudinaryService->enabled()) {
            return null;
        }

        $upload = $this->cloudinaryService->uploadBinary(
            $encoded,
            $filename,
            $this->dynamicFolder($directory),
            'image'
        );

        return is_array($upload) && ! empty($upload['secure_url'])
            ? (string) $upload['secure_url']
            : null;
    }

    private function storeImageInCloudinary(UploadedFile $file, string $directory): ?string
    {
        if (! $this->cloudinaryService->enabled()) {
            return null;
        }

        $upload = $this->cloudinaryService->uploadPath(
            $file->getRealPath() ?: '',
            $this->dynamicFolder($directory),
            'image'
        );

        return is_array($upload) && ! empty($upload['secure_url'])
            ? (string) $upload['secure_url']
            : null;
    }

    private function dynamicFolder(string $directory): string
    {
        $base = trim((string) config('cloudinary.folders.dynamic', ''), '/');
        $directory = trim($directory, '/');

        if ($base === '') {
            return $directory;
        }

        return $directory === '' ? $base : $base . '/' . $directory;
    }

    private function cloudinaryPublicId(UploadedFile $file, string $resourceType): ?string
    {
        if (strtolower(trim($resourceType)) !== 'raw') {
            return null;
        }

        $name = Str::slug((string) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = $name !== '' ? $name : 'file';
        $extension = strtolower(trim((string) $file->getClientOriginalExtension()));
        $extension = preg_replace('/[^a-z0-9]+/i', '', $extension) ?: '';
        $base = Str::lower((string) Str::ulid()) . '-' . $name;

        return $extension !== '' ? $base . '.' . $extension : $base;
    }

    private function storeFallback(UploadedFile $file, string $directory): string
    {
        $cloudPath = $this->storeImageInCloudinary($file, $directory);
        if ($cloudPath !== null) {
            return $cloudPath;
        }

        return $file->store($directory, 'public');
    }
}
