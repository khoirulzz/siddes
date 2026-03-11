<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplaintReport;
use App\Services\CloudinaryService;
use App\Services\ImageUploadService;
use App\Support\MediaSecurity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintReportController extends Controller
{
    public function __construct(
        private readonly CloudinaryService $cloudinaryService,
        private readonly ImageUploadService $imageUploadService
    ) {
    }

    public function index()
    {
        $items = ComplaintReport::query()
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('dashboard.services.complaints.index', [
            'items' => $items,
            'statusOptions' => $this->statusOptions(),
            'stats' => [
                'total' => ComplaintReport::query()->count(),
                'diterima' => ComplaintReport::query()->where('status', 'Diterima')->count(),
                'proses' => ComplaintReport::query()->where('status', 'Diproses')->count(),
                'selesai' => ComplaintReport::query()->where('status', 'Selesai')->count(),
            ],
        ]);
    }

    public function update(Request $request, ComplaintReport $complaintReport)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->statusOptions())],
            'response' => ['nullable', 'string'],
        ]);

        $data['handled_by'] = auth()->user()->name;
        $complaintReport->update($data);

        return redirect()->route('dashboard.complaint-reports.index')->with('success', 'Status pengaduan berhasil diperbarui.');
    }

    public function show(ComplaintReport $complaintReport)
    {
        return view('dashboard.services.complaints.show', [
            'item' => $complaintReport,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function evidence(ComplaintReport $complaintReport): BinaryFileResponse|RedirectResponse
    {
        $path = $complaintReport->resolvedEvidencePath();
        if (! $path) {
            return back()->with('error', 'Lampiran bukti tidak tersedia.');
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            if (! $this->cloudinaryService->isCloudinaryUrl($path)) {
                return back()->with('error', 'URL file bukti tidak valid.');
            }

            return redirect()->away($path);
        }

        if (! MediaSecurity::isAllowedPath($path)) {
            return back()->with('error', 'File bukti tidak valid.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return back()->with('error', 'File bukti pengaduan tidak ditemukan di server.');
        }
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        if (! MediaSecurity::isAllowedMime($mime)) {
            return back()->with('error', 'Tipe file bukti tidak diizinkan.');
        }

        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => MediaSecurity::dispositionForMime($mime) . '; filename="' . basename($path) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(ComplaintReport $complaintReport)
    {
        $this->imageUploadService->delete((string) $complaintReport->evidence_path);
        $complaintReport->delete();

        return redirect()->route('dashboard.complaint-reports.index')->with('success', 'Data pengaduan berhasil dihapus.');
    }

    private function statusOptions(): array
    {
        return ['Diterima', 'Diproses', 'Selesai', 'Ditolak'];
    }
}
