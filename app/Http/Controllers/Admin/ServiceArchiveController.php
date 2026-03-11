<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplaintReport;
use App\Models\LetterServiceRequest;
use App\Models\PbbPaymentRequest;
use App\Services\LetterDocumentService;
use App\Services\ServiceArchiveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServiceArchiveController extends Controller
{
    public function index(Request $request, ServiceArchiveService $archiveService)
    {
        $archiveService->ensureDirectories();

        $type = $this->resolveType((string) $request->query('type', 'surat'));
        $keyword = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        [$items, $statusOptions] = match ($type) {
            'pbb' => [$this->pbbItems($keyword, $status), $this->pbbStatusOptions()],
            'pengaduan' => [$this->complaintItems($keyword, $status), $this->complaintStatusOptions()],
            default => [$this->letterItems($keyword), $this->letterStatusOptions()],
        };

        return view('dashboard.services.archives.index', [
            'type' => $type,
            'items' => $items,
            'statusOptions' => $statusOptions,
            'filters' => [
                'q' => $keyword,
                'status' => $status,
            ],
            'stats' => [
                'surat' => LetterServiceRequest::query()->where('status', 'Selesai')->count(),
                'pbb' => PbbPaymentRequest::count(),
                'pengaduan' => ComplaintReport::count(),
            ],
        ]);
    }

    public function letterPdf(
        Request $request,
        LetterServiceRequest $letterServiceRequest,
        ServiceArchiveService $archiveService,
        LetterDocumentService $documentService
    ): BinaryFileResponse|RedirectResponse {
        try {
            if ($letterServiceRequest->status !== 'Selesai') {
                return back()->with('error', 'File arsip surat hanya tersedia untuk status Selesai.');
            }

            $path = $archiveService->ensureLetterPdfArchive(
                $letterServiceRequest,
                $documentService,
                $request->boolean('refresh')
            );

            if (preg_match('/^https?:\/\//i', $path) === 1) {
                return redirect()->away($path);
            }

            if ($request->query('mode') === 'view') {
                return response()->file($path, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $archiveService->letterPdfDownloadName($letterServiceRequest) . '"',
                ]);
            }

            return response()->download(
                $path,
                $archiveService->letterPdfDownloadName($letterServiceRequest),
                ['Content-Type' => 'application/pdf']
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('archive letter pdf download failed', [
                'id' => $letterServiceRequest->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menyiapkan PDF arsip surat.');
        }
    }

    private function resolveType(string $type): string
    {
        return in_array($type, ['surat', 'pbb', 'pengaduan'], true) ? $type : 'surat';
    }

    /**
     * @return array<int, string>
     */
    private function letterStatusOptions(): array
    {
        return ['Selesai'];
    }

    /**
     * @return array<int, string>
     */
    private function pbbStatusOptions(): array
    {
        return ['Diajukan', 'Diproses', 'Selesai', 'Ditolak'];
    }

    /**
     * @return array<int, string>
     */
    private function complaintStatusOptions(): array
    {
        return ['Diterima', 'Diproses', 'Selesai', 'Ditolak'];
    }

    private function letterItems(string $keyword): LengthAwarePaginator
    {
        $query = LetterServiceRequest::query()->where('status', 'Selesai');

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder->where('ticket_number', 'like', '%' . $keyword . '%')
                    ->orWhere('official_number', 'like', '%' . $keyword . '%')
                    ->orWhere('applicant_name', 'like', '%' . $keyword . '%')
                    ->orWhere('nik', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%');
            });
        }

        return $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    private function pbbItems(string $keyword, string $status): LengthAwarePaginator
    {
        $query = PbbPaymentRequest::query();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder->where('applicant_name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('nop', 'like', '%' . $keyword . '%')
                    ->orWhere('ticket_code', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    private function complaintItems(string $keyword, string $status): LengthAwarePaginator
    {
        $query = ComplaintReport::query();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword): void {
                $builder->where('ticket_code', 'like', '%' . $keyword . '%')
                    ->orWhere('reporter_name', 'like', '%' . $keyword . '%')
                    ->orWhere('nik', 'like', '%' . $keyword . '%')
                    ->orWhere('subject', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20)->withQueryString();
    }
}
