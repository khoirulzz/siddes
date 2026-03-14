<?php

namespace App\Services;

use App\Models\LetterServiceRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ServiceArchiveService
{
    public const BASE_DIRECTORY = 'archives';
    public const LETTER_DIRECTORY = self::BASE_DIRECTORY . '/surat';
    public const PBB_DIRECTORY = self::BASE_DIRECTORY . '/pbb';
    public const COMPLAINT_DIRECTORY = self::BASE_DIRECTORY . '/pengaduan';

    public function __construct(private readonly CloudinaryService $cloudinaryService)
    {
    }

    public function ensureDirectories(): void
    {
        if ($this->cloudinaryService->enabled()) {
            return;
        }

        $disk = Storage::disk('public');
        foreach ($this->directories() as $directory) {
            if (! $disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }
        }
    }

    public function baseDirectoryAbsolutePath(): string
    {
        return Storage::disk('public')->path(self::BASE_DIRECTORY);
    }

    public function letterDirectoryAbsolutePath(): string
    {
        return Storage::disk('public')->path(self::LETTER_DIRECTORY);
    }

    public function hasLetterPdfArchive(LetterServiceRequest $letter): bool
    {
        if ($this->cloudinaryService->enabled()) {
            $existingUrl = trim((string) $letter->attachment_url);

            return $existingUrl !== '' && $this->cloudinaryService->isCloudinaryUrl($existingUrl);
        }

        return Storage::disk('public')->exists($this->letterArchiveRelativePath($letter));
    }

    public function deleteLetterPdfArchive(LetterServiceRequest $letter): void
    {
        if ($this->cloudinaryService->enabled()) {
            $deleted = false;
            $existingUrl = trim((string) $letter->attachment_url);
            if ($existingUrl !== '' && $this->cloudinaryService->isCloudinaryUrl($existingUrl)) {
                $deleted = $this->cloudinaryService->destroyByUrl($existingUrl, 'raw');
            }

            if (! $deleted) {
                $this->cloudinaryService->destroy($this->letterArchivePublicId($letter), 'raw');
            }

            return;
        }

        $disk = Storage::disk('public');
        $relativePath = $this->letterArchiveRelativePath($letter);
        if ($disk->exists($relativePath)) {
            $disk->delete($relativePath);
        }
    }

    public function ensureLetterPdfArchive(
        LetterServiceRequest $letter,
        LetterDocumentService $documentService,
        bool $forceRegenerate = false
    ): string {
        if ($this->cloudinaryService->enabled()) {
            $existingUrl = trim((string) $letter->attachment_url);
            if (
                ! $forceRegenerate
                && $existingUrl !== ''
                && $this->cloudinaryService->isCloudinaryUrl($existingUrl)
                && $this->cloudinaryService->urlReachable($existingUrl)
            ) {
                return $existingUrl;
            }
        }

        $this->ensureDirectories();

        if (! $this->cloudinaryService->enabled()) {
            $disk = Storage::disk('public');
            $relativePath = $this->letterArchiveRelativePath($letter);

            if (! $forceRegenerate && $disk->exists($relativePath)) {
                return $disk->path($relativePath);
            }

            $generated = $documentService->buildDownload($letter, 'pdf');
            $tempPath = $generated['path'] ?? null;
            if (! is_string($tempPath) || ! is_file($tempPath)) {
                throw new RuntimeException('Dokumen PDF surat tidak berhasil dibuat.');
            }

            $stream = @fopen($tempPath, 'rb');
            if (! is_resource($stream)) {
                throw new RuntimeException('Dokumen PDF surat tidak berhasil dibaca.');
            }

            try {
                $stored = $disk->put($relativePath, $stream);
            } finally {
                fclose($stream);
                @unlink($tempPath);
            }

            if (! $stored) {
                throw new RuntimeException('Dokumen PDF surat tidak berhasil disimpan ke arsip.');
            }

            return $disk->path($relativePath);
        }

        $generated = $documentService->buildDownload($letter, 'pdf');
        $tempPath = $generated['path'] ?? null;
        if (! is_string($tempPath) || ! is_file($tempPath)) {
            throw new RuntimeException('Dokumen PDF surat tidak berhasil dibuat.');
        }

        try {
            $upload = $this->cloudinaryService->uploadPath(
                $tempPath,
                $this->letterArchiveCloudFolder(),
                'raw',
                $this->letterArchiveCloudFilename($letter)
            );
        } finally {
            @unlink($tempPath);
        }

        $archiveUrl = is_array($upload) ? trim((string) ($upload['secure_url'] ?? '')) : '';
        if ($archiveUrl === '') {
            throw new RuntimeException('Dokumen PDF surat tidak berhasil disimpan ke Cloudinary.');
        }

        try {
            $letter->forceFill([
                'attachment_url' => $archiveUrl,
                'attachment_path' => null,
            ])->save();
        } catch (\Throwable) {
            // no-op: arsip tetap bisa diakses dari URL return walau update kolom gagal.
        }

        return $archiveUrl;
    }

    public function letterPdfDownloadName(LetterServiceRequest $letter): string
    {
        $number = Str::slug((string) ($letter->official_number ?: $letter->ticket_number ?: 'surat-' . $letter->id));
        $type = Str::slug((string) ($letter->letter_type ?: 'surat'));

        if ($number === '') {
            $number = 'surat-' . $letter->id;
        }
        if ($type === '') {
            $type = 'surat';
        }

        return "{$number}-{$type}.pdf";
    }

    private function letterArchiveRelativePath(LetterServiceRequest $letter): string
    {
        return self::LETTER_DIRECTORY . '/' . $this->letterArchiveCode($letter) . '.pdf';
    }

    private function letterArchivePublicId(LetterServiceRequest $letter): string
    {
        return $this->letterArchiveCloudFolder() . '/' . $this->letterArchiveCloudFilename($letter);
    }

    private function letterArchiveCloudFolder(): string
    {
        return trim($this->archiveFolder(), '/') . '/surat';
    }

    private function letterArchiveCloudFilename(LetterServiceRequest $letter): string
    {
        return $this->letterArchiveCode($letter) . '.pdf';
    }

    private function letterArchiveCode(LetterServiceRequest $letter): string
    {
        $code = Str::slug((string) ($letter->ticket_number ?: 'surat-' . $letter->id));

        return $code !== '' ? $code : 'surat-' . $letter->id;
    }

    private function archiveFolder(): string
    {
        return trim((string) config('cloudinary.folders.archives', 'sid/archives'), '/');
    }

    /**
     * @return array<int, string>
     */
    private function directories(): array
    {
        return [
            self::BASE_DIRECTORY,
            self::LETTER_DIRECTORY,
            self::PBB_DIRECTORY,
            self::COMPLAINT_DIRECTORY,
        ];
    }
}
