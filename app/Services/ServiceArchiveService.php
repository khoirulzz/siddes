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

    public function ensureDirectories(): void
    {
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
        return Storage::disk('public')->exists($this->letterArchiveRelativePath($letter));
    }

    public function deleteLetterPdfArchive(LetterServiceRequest $letter): void
    {
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
        $this->ensureDirectories();

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
        $code = Str::slug((string) ($letter->ticket_number ?: 'surat-' . $letter->id));
        if ($code === '') {
            $code = 'surat-' . $letter->id;
        }

        return self::LETTER_DIRECTORY . '/' . $code . '.pdf';
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
