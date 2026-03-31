<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LetterServiceRequest;
use App\Services\CloudinaryService;
use App\Services\LetterDocumentService;
use App\Services\ServiceArchiveService;
use App\Support\RemoteMediaResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class LetterServiceRequestController extends Controller
{
    public function index()
    {
        $items = LetterServiceRequest::query()
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('dashboard.services.letters.index', [
            'items' => $items,
            'statusOptions' => $this->statusOptions(),
            'stats' => [
                'total' => LetterServiceRequest::query()->count(),
                'masuk' => LetterServiceRequest::query()->where('status', 'Diajukan')->count(),
                'proses' => LetterServiceRequest::query()->where('status', 'Diproses')->count(),
                'selesai' => LetterServiceRequest::query()->where('status', 'Selesai')->count(),
                'ditolak' => LetterServiceRequest::query()->where('status', 'Ditolak')->count(),
            ],
        ]);
    }

    public function update(Request $request, LetterServiceRequest $letterServiceRequest)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->statusOptions())],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $letterServiceRequest->update($data);

        return redirect()->route('dashboard.letter-service-requests.index')->with('success', 'Status pengajuan surat berhasil diperbarui.');
    }

    public function destroy(LetterServiceRequest $letterServiceRequest, ServiceArchiveService $archiveService)
    {
        $archiveService->deleteLetterPdfArchive($letterServiceRequest);
        $letterServiceRequest->delete();

        return redirect()->route('dashboard.letter-service-requests.index')->with('success', 'Data pengajuan surat berhasil dihapus.');
    }

    public function download(
        Request $request,
        LetterServiceRequest $letterServiceRequest,
        LetterDocumentService $documentService,
        ServiceArchiveService $archiveService,
        CloudinaryService $cloudinaryService
    ): Response {
        $format = strtolower((string) $request->query('format', 'pdf'));
        if (! in_array($format, ['pdf', 'docx'], true)) {
            abort(404);
        }

        try {
            if ($format === 'pdf') {
                $archivePath = $archiveService->ensureLetterPdfArchive($letterServiceRequest, $documentService);
                $archiveName = $archiveService->letterPdfDownloadName($letterServiceRequest);

                if (preg_match('/^https?:\/\//i', $archivePath) === 1) {
                    return RemoteMediaResponse::fromUrl(
                        $archivePath,
                        $archiveName,
                        'application/pdf',
                        true,
                        $cloudinaryService
                    );
                }

                return response()->download($archivePath, $archiveName, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $archiveName . '"',
                ]);
            }

            $file = $documentService->buildDownload($letterServiceRequest, $format);

            return response()->download($file['path'], $file['name'], [
                'Content-Type' => $file['mime'],
                'Content-Disposition' => 'attachment; filename="' . $file['name'] . '"',
            ])->deleteFileAfterSend(true);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('admin letter download error', [
                'id' => $letterServiceRequest->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Dokumen surat gagal diunduh.');
        }
    }

    private function statusOptions(): array
    {
        return ['Diajukan', 'Diproses', 'Selesai', 'Ditolak'];
    }
}
