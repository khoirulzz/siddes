<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopulationDocument;
use App\Models\PopulationRecord;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PopulationDocumentController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}

    public function store(Request $request, PopulationRecord $populationRecord): RedirectResponse
    {
        abort_if($populationRecord->isActiveResident(), 403);

        $validated = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(PopulationDocument::TYPES))],
            'document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120'],
        ]);

        $isMoved = $populationRecord->status_keberadaan === PopulationRecord::PRESENCE_MOVED;
        if (($validated['jenis'] === 'surat_pindah') !== $isMoved) {
            return back()->withErrors(['jenis' => 'Jenis dokumen tidak sesuai dengan status keberadaan warga.']);
        }

        $file = $request->file('document');
        $contents = file_get_contents($file->getRealPath());
        if (! is_string($contents) || ! str_starts_with($contents, '%PDF-')) {
            return back()->withErrors(['document' => 'Berkas harus berupa PDF yang valid.']);
        }

        if (! $this->cloudinaryService->enabled()) {
            return back()->withErrors(['document' => 'Penyimpanan Cloudinary belum dikonfigurasi.']);
        }

        $filename = mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255) ?: 'dokumen.pdf';
        $asset = $this->cloudinaryService->uploadPrivatePdf(
            $contents,
            $filename,
            (string) config('cloudinary.folders.population_documents', 'sid/population-documents')
        );
        if ($asset === null) {
            return back()->withErrors(['document' => 'Gagal mengunggah dokumen ke Cloudinary. Silakan coba lagi.']);
        }

        try {
            $populationRecord->documents()->create([
                'jenis' => $validated['jenis'],
                'status_keberadaan' => $populationRecord->status_keberadaan,
                'original_name' => $filename,
                'size' => strlen($contents),
                'cloudinary_asset_id' => $asset['asset_id'],
                'cloudinary_public_id' => $asset['public_id'],
            ]);
        } catch (\Throwable $e) {
            $this->cloudinaryService->destroyAsset($asset['asset_id']);
            Log::error('population document metadata could not be saved', ['message' => $e->getMessage()]);

            return back()->withErrors(['document' => 'Gagal menyimpan data dokumen. Silakan coba lagi.']);
        }

        return back()->with('success', 'Dokumen warga berhasil diunggah.');
    }

    public function show(PopulationRecord $populationRecord, PopulationDocument $populationDocument)
    {
        abort_unless($populationDocument->population_record_id === $populationRecord->id, 404);
        $contents = $this->cloudinaryService->downloadAsset($populationDocument->cloudinary_asset_id);
        abort_if($contents === null, 503, 'Dokumen belum dapat diunduh. Silakan coba lagi.');

        return response()->streamDownload(static function () use ($contents): void {
            echo $contents;
        }, $populationDocument->original_name, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(PopulationRecord $populationRecord, PopulationDocument $populationDocument): RedirectResponse
    {
        abort_unless($populationDocument->population_record_id === $populationRecord->id, 404);
        if (! $this->cloudinaryService->destroyAsset($populationDocument->cloudinary_asset_id)) {
            return back()->with('error', 'Gagal menghapus dokumen di Cloudinary. Silakan coba lagi.');
        }
        $populationDocument->delete();

        return back()->with('success', 'Dokumen warga berhasil dihapus.');
    }
}
