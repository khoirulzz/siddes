<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicServiceController;
use App\Http\Requests\ComplaintReportRequestForm;
use App\Http\Requests\LetterServiceRequestForm;
use App\Http\Requests\PbbPaymentRequestForm;
use App\Models\ComplaintReport;
use App\Models\LetterServiceRequest;
use App\Models\PbbPaymentRequest;
use App\Models\PopulationRecord;
use App\Services\ImageUploadService;
use App\Services\LetterDocumentService;
use App\Support\LetterSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MobileAppController extends Controller
{
    protected PublicServiceController $publicServiceController;

    public function __construct(PublicServiceController $publicServiceController)
    {
        $this->publicServiceController = $publicServiceController;
    }

    public function villageInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => config('village.name'),
                'district' => config('village.district'),
                'logo_url' => url(config('village.logo_url')),
                'phone' => config('village.phone'),
                'email' => config('village.email'),
                'address' => config('village.address'),
                'head_name' => config('village.head_name'),
                'developed_by' => config('village.developed_by'),
            ]
        ]);
    }

    public function letterTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => LetterSchema::definitions()
        ]);
    }

    public function checkNik(Request $request): JsonResponse
    {
        return $this->publicServiceController->checkNik($request);
    }

    public function searchNop(Request $request): JsonResponse
    {
        return $this->publicServiceController->searchNop($request);
    }

    public function searchLetterByTicket(Request $request): JsonResponse
    {
        return $this->publicServiceController->searchLetterByTicket($request);
    }

    public function searchPbbByTicket(Request $request): JsonResponse
    {
        return $this->publicServiceController->searchPbbByTicket($request);
    }

    public function searchComplaintByTicket(Request $request): JsonResponse
    {
        return $this->publicServiceController->searchComplaintByTicket($request);
    }

    // --- JSON Equivalents for Submit ---

    public function storeLetter(
        LetterServiceRequestForm $request,
        LetterDocumentService $documentService
    ): JsonResponse {
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

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan surat berhasil dikirim.',
                'ticket_number' => $ticket,
                'official_number' => $numbering['official_number']
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'NIK tidak ditemukan pada data kependudukan.'
            ], 404);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('mobileStoreLetter error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan pengajuan surat.'
            ], 500);
        }
    }

    public function storePbb(PbbPaymentRequestForm $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $requestedNops = $this->resolveRequestedNops($data['nops'] ?? []);

            if (count($requestedNops) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'NOP tidak valid. Silakan cari ulang NOP yang tersedia.',
                    'errors' => ['nops' => ['NOP tidak valid. Silakan cari ulang NOP yang tersedia.']]
                ], 422);
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

            return response()->json([
                'success' => true,
                'message' => 'Permohonan PBB berhasil dikirim.',
                'ticket_code' => $ticket
            ]);
        } catch (\Exception $e) {
            \Log::error('mobileStorePbb error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan pengajuan PBB.'
            ], 500);
        }
    }

    public function storeComplaint(ComplaintReportRequestForm $request, ImageUploadService $imageUploadService): JsonResponse
    {
        try {
            $ticket = 'PGD-' . date('ymd') . '-' . strtoupper(Str::random(4));

            $data = $request->validated();
            $data['ticket_code'] = $ticket;
            $data['nik'] = preg_replace('/\D+/', '', (string) $data['nik']) ?: (string) $data['nik'];
            $data['phone'] = $this->normalizePhone((string) $data['phone']);
            $data['status'] = 'Diterima';
            $data['submitted_at'] = now();

            if ($request->hasFile('evidence')) {
                $evidenceFile = $request->file('evidence');
                $evidenceMime = (string) $evidenceFile->getMimeType();

                if (str_starts_with($evidenceMime, 'image/')) {
                    $data['evidence_path'] = $imageUploadService->storeOptimized(
                        $evidenceFile,
                        'complaints/evidence',
                        1920,
                        1920,
                        78
                    );
                } else {
                    $data['evidence_path'] = $imageUploadService->storeFile(
                        $evidenceFile,
                        'complaints/evidence',
                        str_starts_with($evidenceMime, 'video/') ? 'video' : 'raw'
                    );
                }
            }

            ComplaintReport::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Pengaduan berhasil dikirim.',
                'ticket_code' => $ticket
            ]);
        } catch (\Exception $e) {
            \Log::error('mobileStoreComplaint error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengirim pengaduan.'
            ], 500);
        }
    }

    // --- Helpers from PublicServiceController ---

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

    private function filterToExistingLetterColumns(array $payload): array
    {
        return $this->filterPayloadByTableColumns('letter_service_requests', $payload);
    }

    private function filterPayloadByTableColumns(string $table, array $payload): array
    {
        $columns = $this->tableColumns($table);
        if (empty($columns)) {
            return $payload;
        }

        return array_intersect_key($payload, array_flip($columns));
    }

    private function tableColumns(string $table): array
    {
        static $cache = [];

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = Schema::getColumnListing($table);
        }

        return $cache[$table];
    }

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

            $key = strtoupper((string) $taxObject->nop) . '#' . $taxObject->resolvedTaxYear();
            $resolved[$key] = [
                'nop' => (string) $taxObject->nop,
                'tax_name' => $taxObject->resolvedTaxName(),
                'address' => $taxObject->resolvedAddress(),
                'tax_year' => $taxObject->resolvedTaxYear(),
                'amount_due' => $taxObject->resolvedAmountDue(),
            ];
        }

        return array_values($resolved);
    }

    private function resolvePbbObjectByNop(string $nop, ?int $taxYear = null): ?\App\Models\PbbTaxObject
    {
        $cleanNop = trim($nop);
        if ($cleanNop === '') {
            return null;
        }

        $normalizedNop = $this->normalizeNop($cleanNop);

        $query = \App\Models\PbbTaxObject::query()
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

    private function hasPbbTicketCodeColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('pbb_payment_requests', 'ticket_code');
        }

        return $hasColumn;
    }
}
