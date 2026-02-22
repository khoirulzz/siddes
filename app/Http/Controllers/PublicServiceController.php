<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplaintReportRequestForm;
use App\Http\Requests\LetterServiceRequestForm;
use App\Http\Requests\PbbPaymentRequestForm;
use App\Models\ComplaintReport;
use App\Models\LetterServiceRequest;
use App\Models\PbbPaymentRequest;
use App\Models\PbbTaxObject;
use App\Models\PopulationRecord;
use App\Services\ImageUploadService;
use App\Services\LetterDocumentService;
use App\Services\ServiceArchiveService;
use App\Support\LetterSchema;
use App\Support\MediaSecurity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicServiceController extends Controller
{
    // --- FITUR LAYANAN SURAT ---

    public function letterForm()
    {
        return view('public.services.letter', [
            'letterTypes' => LetterSchema::types(),
            'letterSchema' => LetterSchema::definitions(),
        ]);
    }

    public function checkNik(Request $request): JsonResponse
    {
        $nik = trim((string) $request->input('nik', ''));
        if ($nik === '' || strlen($nik) !== 16 || ! ctype_digit($nik)) {
            return response()->json([
                'success' => false,
                'message' => 'NIK harus 16 digit angka.',
            ], 422);
        }

        $citizen = PopulationRecord::query()->where('nik', $nik)->first();
        if (! $citizen) {
            return response()->json([
                'success' => false,
                'message' => 'NIK tidak ditemukan. Silakan hubungi operator desa.',
            ], 404);
        }

        $address = $this->buildCitizenAddress($citizen);

        return response()->json([
            'success' => true,
            'nik' => $citizen->nik,
            'full_name' => $citizen->resolvedName(),
            'address_detail' => $address,
            'citizen' => [
                'nik' => $citizen->nik,
                'full_name' => $citizen->resolvedName(),
                'no_kk' => $citizen->resolvedKkNumber(),
                'birth_place' => $citizen->resolvedBirthPlace(),
                'birth_date' => $citizen->resolvedBirthDate()?->format('d-m-Y'),
                'gender' => $citizen->resolvedGender(),
                'religion' => $citizen->resolvedReligion(),
                'occupation' => $citizen->resolvedOccupation(),
                'hamlet' => $citizen->resolvedHamlet(),
                'rt' => $citizen->resolvedRt(),
                'rw' => $citizen->resolvedRw(),
                'address_detail' => $address,
            ],
        ]);
    }

    public function letterStore(
        LetterServiceRequestForm $request,
        LetterDocumentService $documentService
    ): RedirectResponse {
        try {
            $data = $request->validated();
            $citizen = PopulationRecord::query()->where('nik', $data['nik'])->firstOrFail();

            $documentService->ensureTemplateExists($data['letter_type']);
            $ticket = $documentService->generateTicketNumber();
            $numbering = $documentService->issueLetterNumber($data['letter_type']);
            $dynamicData = $this->normalizeDynamicData($data['dynamic_data'] ?? []);
            $legacyPurpose = $dynamicData['keperluan'] ?? null;
            $payload = [
                'ticket_number' => $ticket,
                'official_number' => $numbering['official_number'],
                'applicant_name' => trim($citizen->resolvedName()),
                'nik' => $data['nik'],
                'kk_number' => $citizen->resolvedKkNumber(),
                'address' => $this->buildCitizenAddress($citizen),
                'phone' => preg_replace('/\s+/', '', (string) $data['phone']) ?: (string) $data['phone'],
                'letter_type' => $data['letter_type'],
                'letter_code' => $numbering['code'],
                'letter_sequence' => $numbering['sequence'],
                'purpose' => $legacyPurpose,
                'dynamic_data' => $dynamicData,
                'email' => $data['email'] ?? null,
                'attachment_path' => null,
                'attachment_url' => null,
                'status' => 'Diajukan',
                'requested_at' => now(),
                'submitted_at' => now(),
            ];

            $payload = $this->filterToExistingLetterColumns($payload);
            LetterServiceRequest::create($payload);

            return redirect()
                ->route('services.letter.success', ['ticket' => $ticket])
                ->with('success', 'Pengajuan surat berhasil dikirim.');
        } catch (ModelNotFoundException $e) {
            return back()
                ->withInput()
                ->with('error', 'NIK tidak ditemukan pada data kependudukan.');
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('letterStore error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan pengajuan surat.');
        }
    }

    public function letterSuccess(string $ticket)
    {
        $letterRequest = LetterServiceRequest::query()
            ->where('ticket_number', $ticket)
            ->firstOrFail();

        return view('public.services.letter-success', [
            'request' => $letterRequest,
        ]);
    }

    public function searchLetterByTicket(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('ticket_number', ''));
        if ($keyword === '') {
            $keyword = trim((string) $request->query('letter_number', ''));
        }
        if ($keyword === '') {
            $keyword = trim((string) $request->query('q', ''));
        }

        if ($keyword === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor surat wajib diisi.',
            ], 422);
        }

        $normalizedKeyword = Str::upper($keyword);

        $letter = LetterServiceRequest::query()
            ->where(function ($query) use ($keyword, $normalizedKeyword): void {
                $query->where('ticket_number', $normalizedKeyword)
                    ->orWhere('official_number', $keyword)
                    ->orWhere('official_number', $normalizedKeyword);
            })
            ->first();

        if (! $letter) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor surat tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'ticket_number' => $letter->ticket_number,
            'official_number' => $letter->official_number,
            'status' => $letter->status,
            'letter_type' => $letter->letter_type,
            'submitted_at' => ($letter->submitted_at ?? $letter->requested_at)?->format('d-m-Y H:i') ?? '-',
            'download_url' => route('services.letter.download', ['ticket' => $letter->ticket_number, 'format' => 'pdf']),
            'download_docx_url' => route('services.letter.download', ['ticket' => $letter->ticket_number, 'format' => 'docx']),
        ]);
    }

    public function downloadLetter(
        Request $request,
        string $ticket,
        LetterDocumentService $documentService,
        ServiceArchiveService $archiveService
    ): BinaryFileResponse|RedirectResponse {
        $letter = LetterServiceRequest::query()->where('ticket_number', $ticket)->firstOrFail();
        $format = strtolower((string) $request->query('format', 'pdf'));

        if (! in_array($format, ['pdf', 'docx'], true)) {
            abort(404);
        }

        try {
            if ($format === 'pdf') {
                $archivePath = $archiveService->ensureLetterPdfArchive($letter, $documentService);
                $archiveName = $archiveService->letterPdfDownloadName($letter);

                return response()->download($archivePath, $archiveName, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $archiveName . '"',
                ]);
            }

            $file = $documentService->buildDownload($letter, $format);

            return response()->download($file['path'], $file['name'], [
                'Content-Type' => $file['mime'],
                'Content-Disposition' => 'attachment; filename="' . $file['name'] . '"',
            ])->deleteFileAfterSend(true);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('downloadLetter error', [
                'ticket' => $ticket,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Surat gagal diunduh. Silakan coba lagi.');
        }
    }

    /**
     * @param array<string, mixed> $dynamicData
     * @return array<string, string>
     */
    private function normalizeDynamicData(array $dynamicData): array
    {
        $normalized = [];

        foreach ($dynamicData as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $clean = trim((string) $value);
            if ($clean === '') {
                continue;
            }

            $normalized[(string) $key] = $clean;
        }

        return $normalized;
    }

    private function buildCitizenAddress(PopulationRecord $citizen): string
    {
        return collect([
            $citizen->address_detail,
            $citizen->resolvedRt() !== '-' ? 'RT ' . $citizen->resolvedRt() : null,
            $citizen->resolvedRw() !== '-' ? 'RW ' . $citizen->resolvedRw() : null,
            $citizen->resolvedHamlet() !== '-' ? 'Dusun ' . $citizen->resolvedHamlet() : null,
            $citizen->resolvedVillage(),
            $citizen->resolvedDistrict(),
            $citizen->resolvedRegency(),
            $citizen->resolvedProvince(),
            $citizen->resolvedPostalCode(),
        ])->filter()->implode(', ');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterToExistingLetterColumns(array $payload): array
    {
        $columns = $this->tableColumns('letter_service_requests');
        if (empty($columns)) {
            return $payload;
        }

        return array_intersect_key($payload, array_flip($columns));
    }

    // --- FITUR LAYANAN PBB ---

    public function pbbForm()
    {
        return view('public.services.pbb');
    }

    public function searchNop(Request $request): JsonResponse
    {
        $request->validate([
            'nop' => ['required', 'string', 'max:40'],
            'tax_year' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
        ]);

        $taxObject = $this->resolvePbbObjectByNop(
            (string) $request->input('nop'),
            $request->filled('tax_year') ? (int) $request->input('tax_year') : null,
        );

        if (! $taxObject) {
            return response()->json([
                'success' => false,
                'message' => 'Data NOP tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'nop' => $taxObject->nop,
            'tax_name' => $taxObject->tax_name,
            'address' => $taxObject->resolvedAddress(),
            'tax_year' => (int) $taxObject->tax_year,
            'amount_due' => (float) $taxObject->amount_due,
            'status' => $taxObject->status,
        ]);
    }

    public function searchPbbByTicket(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('ticket', ''));
        if ($keyword === '') {
            $keyword = trim((string) $request->query('q', ''));
        }

        if ($keyword === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor tiket wajib diisi.',
            ], 422);
        }

        $normalizedKeyword = Str::upper($keyword);

        $query = PbbPaymentRequest::query();
        if ($this->hasPbbTicketCodeColumn()) {
            $query->where('ticket_code', $normalizedKeyword);
        } else {
            $query->where('notes', 'like', '%TIKET:' . $normalizedKeyword . '%');
        }

        $paymentRequest = $query->first();

        if (! $paymentRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor tiket tidak ditemukan.',
            ], 404);
        }

        $requestedNops = $paymentRequest->requestedNopsList();

        return response()->json([
            'success' => true,
            'ticket_code' => $paymentRequest->ticket_code ?: $this->extractTicketFromNotes($paymentRequest->notes),
            'status' => $paymentRequest->status,
            'applicant_name' => $paymentRequest->applicant_name,
            'submitted_at' => $paymentRequest->submitted_at?->format('d-m-Y H:i') ?: '-',
            'total_nop' => count($requestedNops),
            'total_amount' => (float) $paymentRequest->amount_due,
            'nops' => $requestedNops,
        ]);
    }

    public function pbbStore(PbbPaymentRequestForm $request): RedirectResponse
    {
        $data = $request->validated();
        $requestedNops = $this->resolveRequestedNops($data['nops'] ?? []);

        if (count($requestedNops) < 1) {
            return back()
                ->withInput()
                ->withErrors(['nops' => 'NOP tidak valid. Silakan cari ulang NOP yang tersedia.']);
        }

        $ticket = $this->generatePbbTicket();
        $firstNop = $requestedNops[0];
        $totalAmount = collect($requestedNops)->sum(fn (array $item) => (float) ($item['amount_due'] ?? 0));

        $payload = [
            'ticket_code' => $ticket,
            'applicant_name' => trim((string) $data['applicant_name']),
            'nik' => '-',
            'phone' => $this->normalizePhone((string) $data['phone']),
            'email' => $data['email'] ?? null,
            'nop' => (string) ($firstNop['nop'] ?? ''),
            'requested_nops' => $requestedNops,
            'tax_year' => (int) ($firstNop['tax_year'] ?? date('Y')),
            'amount_due' => $totalAmount,
            'status' => 'Diajukan',
            'submitted_at' => now(),
        ];

        $payload = $this->filterPayloadByTableColumns('pbb_payment_requests', $payload);

        if (! array_key_exists('ticket_code', $payload) && array_key_exists('notes', $payload)) {
            $payload['notes'] = trim('TIKET:' . $ticket . PHP_EOL . (string) ($payload['notes'] ?? ''));
        }

        PbbPaymentRequest::create($payload);

        return back()
            ->with('success', 'Permohonan PBB berhasil dikirim.')
            ->with('pbb_ticket', $ticket);
    }

    // --- FITUR PENGADUAN ---

    public function complaintForm()
    {
        return view('public.services.complaint');
    }

    public function complaintStore(ComplaintReportRequestForm $request, ImageUploadService $imageUploadService)
    {
        $ticket = 'PGD-' . date('ymd') . '-' . strtoupper(Str::random(4));

        $data = $request->validated();
        $data['ticket_code'] = $ticket;
        $data['nik'] = preg_replace('/\D+/', '', (string) $data['nik']) ?: (string) $data['nik'];
        $data['phone'] = $this->normalizePhone((string) $data['phone']);
        $data['status'] = 'Diterima';
        $data['submitted_at'] = now();

        if ($request->hasFile('evidence')) {
            $evidenceFile = $request->file('evidence');
            if (str_starts_with((string) $evidenceFile->getMimeType(), 'image/')) {
                $data['evidence_path'] = $imageUploadService->storeOptimized(
                    $evidenceFile,
                    'complaints/evidence',
                    1920,
                    1920,
                    78
                );
            } else {
                $data['evidence_path'] = $evidenceFile->store('complaints/evidence', 'public');
            }
        }

        ComplaintReport::create($data);

        return back()
            ->with('success', 'Pengaduan berhasil dikirim.')
            ->with('complaint_ticket', $ticket);
    }

    public function searchComplaintByTicket(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('ticket', ''));
        if ($keyword === '') {
            $keyword = trim((string) $request->query('q', ''));
        }

        if ($keyword === '') {
            return response()->json([
                'success' => false,
                'message' => 'Nomor tiket wajib diisi.',
            ], 422);
        }

        $normalizedKeyword = Str::upper($keyword);

        $complaint = ComplaintReport::query()
            ->where('ticket_code', $normalizedKeyword)
            ->first();

        if (! $complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor tiket pengaduan tidak ditemukan.',
            ], 404);
        }

        $evidencePath = $complaint->resolvedEvidencePath();
        $hasEvidenceFile = is_string($evidencePath) && $evidencePath !== '' && Storage::disk('public')->exists($evidencePath);

        return response()->json([
            'success' => true,
            'ticket_code' => $complaint->ticket_code,
            'status' => $complaint->status,
            'reporter_name' => $complaint->reporter_name,
            'subject' => $complaint->subject,
            'category' => $complaint->category,
            'submitted_at' => $complaint->submitted_at?->format('d-m-Y H:i') ?: '-',
            'response' => $complaint->response ?: null,
            'evidence_url' => $hasEvidenceFile
                ? route('services.complaint.evidence', ['ticket' => $complaint->ticket_code])
                : null,
        ]);
    }

    public function complaintEvidence(string $ticket): BinaryFileResponse
    {
        $normalizedTicket = Str::upper(trim($ticket));
        $complaint = ComplaintReport::query()
            ->where('ticket_code', $normalizedTicket)
            ->firstOrFail();

        $path = $complaint->resolvedEvidencePath();
        abort_if(! $path, 404);
        abort_unless(MediaSecurity::isAllowedPath($path), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        abort_unless(MediaSecurity::isAllowedMime($mime), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => MediaSecurity::dispositionForMime($mime) . '; filename="' . basename($path) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function resolvePbbObjectByNop(string $nop, ?int $taxYear = null): ?PbbTaxObject
    {
        $cleanNop = trim($nop);
        if ($cleanNop === '') {
            return null;
        }

        $normalizedNop = $this->normalizeNop($cleanNop);

        $query = PbbTaxObject::query()
            ->where(function ($builder) use ($cleanNop, $normalizedNop): void {
                $builder->where('nop', $cleanNop);

                if ($normalizedNop !== '') {
                    $builder->orWhereRaw(
                        'REPLACE(REPLACE(REPLACE(REPLACE(nop, ".", ""), "-", ""), " ", ""), "/", "") = ?',
                        [$normalizedNop]
                    );
                }
            });

        if ($taxYear) {
            $query->where('tax_year', $taxYear);
        }

        return $query->orderByDesc('tax_year')->first();
    }

    /**
     * @param array<int, mixed> $nops
     * @return array<int, array<string, mixed>>
     */
    private function resolveRequestedNops(array $nops): array
    {
        $resolved = [];

        foreach ($nops as $item) {
            if (! is_array($item)) {
                continue;
            }

            $taxObject = $this->resolvePbbObjectByNop(
                (string) ($item['nop'] ?? ''),
                isset($item['tax_year']) ? (int) $item['tax_year'] : null,
            );

            if (! $taxObject) {
                continue;
            }

            $key = strtoupper((string) $taxObject->nop) . '#' . (int) $taxObject->tax_year;
            $resolved[$key] = [
                'nop' => (string) $taxObject->nop,
                'tax_name' => (string) $taxObject->tax_name,
                'address' => $taxObject->resolvedAddress(),
                'tax_year' => (int) $taxObject->tax_year,
                'amount_due' => (float) $taxObject->amount_due,
            ];
        }

        return array_values($resolved);
    }

    private function normalizeNop(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?: trim($phone);
    }

    private function generatePbbTicket(): string
    {
        do {
            $ticket = 'PBB-' . date('ymd') . '-' . strtoupper(Str::random(4));

            if (! $this->hasPbbTicketCodeColumn()) {
                break;
            }
        } while (PbbPaymentRequest::query()->where('ticket_code', $ticket)->exists());

        return $ticket;
    }

    private function extractTicketFromNotes(?string $notes): ?string
    {
        if (! $notes) {
            return null;
        }

        if (preg_match('/TIKET:([A-Z0-9\-]+)/i', $notes, $matches) === 1) {
            return strtoupper((string) $matches[1]);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterPayloadByTableColumns(string $table, array $payload): array
    {
        $columns = $this->tableColumns($table);
        if (empty($columns)) {
            return $payload;
        }

        return array_intersect_key($payload, array_flip($columns));
    }

    private function hasPbbTicketCodeColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('pbb_payment_requests', 'ticket_code');
        }

        return $hasColumn;
    }

    /**
     * @return array<int, string>
     */
    private function tableColumns(string $table): array
    {
        static $cache = [];

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = Schema::getColumnListing($table);
        }

        return $cache[$table];
    }
}
