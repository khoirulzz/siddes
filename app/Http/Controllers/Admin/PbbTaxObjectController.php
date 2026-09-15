<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\PbbImportException;
use App\Models\PbbTaxObject;
use App\Services\PbbImportService;
use App\Support\PbbImportSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PbbTaxObjectController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $year = $request->filled('year') ? (int) $request->query('year') : null;

        $taxObjects = PbbTaxObject::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $digits = preg_replace('/\D+/', '', $keyword) ?: $keyword;
                $query->where(function ($builder) use ($keyword, $digits): void {
                    $builder->where('nop', 'like', '%' . $keyword . '%')
                        ->orWhere('nama_wp_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('jalan_wp_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('jalan_op_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('desa_wp_sppt', 'like', '%' . $keyword . '%')
                        ->orWhere('nop_normalized', 'like', '%' . $digits . '%');
                });
            })
            ->when($year, fn ($query) => $query->where('tax_year', $year))
            ->orderByDesc('tax_year')
            ->orderBy('nop')
            ->paginate(25)
            ->withQueryString();

        $availableYears = PbbTaxObject::query()
            ->select('tax_year')
            ->whereNotNull('tax_year')
            ->distinct()
            ->orderByDesc('tax_year')
            ->pluck('tax_year');

        return view('dashboard.pbb-tax-objects.index', [
            'taxObjects' => $taxObjects,
            'availableYears' => $availableYears,
            'filters' => [
                'q' => $keyword,
                'year' => $year,
            ],
        ]);
    }

    public function create()
    {
        return view('dashboard.pbb-tax-objects.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $payload = $this->normalizePayload($validated);

        PbbTaxObject::query()->create($payload);

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        return view('dashboard.pbb-tax-objects.show', compact('taxObject'));
    }

    public function edit(string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        return view('dashboard.pbb-tax-objects.edit', compact('taxObject'));
    }

    public function update(Request $request, string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        $validated = $this->validatePayload($request, $taxObject->id);
        $payload = $this->normalizePayload($validated);

        $taxObject->update($payload);

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $taxObject = PbbTaxObject::query()->findOrFail($id);
        $taxObject->delete();

        return redirect()->route('dashboard.pbb-tax-objects.index')->with('success', 'Data PBB berhasil dihapus.');
    }

    public function previewImport(Request $request, PbbImportService $importService): JsonResponse
    {
        $payload = $this->validateImportRequest($request, false);
        $file = $request->file('file');

        try {
            $yearOverride = isset($payload['year_override']) ? (int) $payload['year_override'] : null;
            $preview = $importService->preview($file, $yearOverride);
            $token = 'pbb_import_' . \Str::uuid()->toString();
            
            Cache::put($token, [
                'filename' => $file->getClientOriginalName(),
                'preview' => $preview,
            ], now()->addMinutes(15));

            return response()->json([
                'token' => $token,
                'preview' => $importService->publicPreview($preview),
            ]);
        } catch (PbbImportException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['file' => [$exception->getMessage()]],
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('PBB import preview failed.', [
                'user_id' => $request->user()?->id,
                'filename' => $file?->getClientOriginalName(),
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan sistem saat memproses file. Pastikan format file sesuai template atau hubungi administrator.',
            ], 500);
        }
    }

    public function commitImport(Request $request, PbbImportService $importService): JsonResponse
    {
        $payload = $this->validateImportRequest($request, true);
        $file = $request->file('file');
        
        $cached = Cache::get($payload['preview_token']);
        if (! $cached || $cached['filename'] !== $file?->getClientOriginalName()) {
            return response()->json([
                'message' => 'Sesi import telah kedaluwarsa atau file tidak cocok. Silakan periksa file kembali.',
                'errors' => ['file' => ['Sesi import telah kedaluwarsa atau file tidak cocok.']],
            ], 422);
        }

        Cache::forget($payload['preview_token']);

        try {
            $result = $importService->commit($cached['preview'], $file->getClientOriginalName());

            $message = sprintf(
                'Import PBB selesai: %d baru, %d diperbarui, %d tidak berubah, dan %d dilewati.',
                $result['inserted'],
                $result['updated'],
                $result['unchanged'],
                $result['skipped'],
            );
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'result' => $result,
                'redirect' => route('dashboard.pbb-tax-objects.index'),
            ]);
        } catch (PbbImportException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['file' => [$exception->getMessage()]],
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('PBB import commit failed.', [
                'user_id' => $request->user()?->id,
                'filename' => $file?->getClientOriginalName(),
                'exception_class' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Import gagal disimpan dan seluruh perubahan dibatalkan. Silakan periksa laporan lalu coba lagi.',
            ], 500);
        }
    }

    private function validateImportRequest(Request $request, bool $requireToken = false): array
    {
        return $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:15360'],
            'year_override' => ['nullable', 'integer', 'min:2026', 'max:' . (date('Y') + 1)],
            'preview_token' => [$requireToken ? 'required' : 'nullable', 'string'],
        ]);
    }

    public function destroyByYear(Request $request)
    {
        $payload = $request->validate([
            'year' => ['required', 'integer', 'min:2025', 'max:' . (date('Y') + 20)],
        ]);

        $year = (int) $payload['year'];
        $deleted = PbbTaxObject::query()->where('tax_year', $year)->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('dashboard.pbb-tax-objects.index')
                ->with('success', "Tidak ada data PBB tahun {$year} yang dihapus.");
        }

        return redirect()
            ->route('dashboard.pbb-tax-objects.index')
            ->with('success', "Berhasil menghapus {$deleted} data PBB tahun {$year}.");
    }

    public function template(Request $request)
    {
        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function (): void {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, PbbImportSchema::COLUMNS, ';', '"', '\\');
                fclose($handle);
            }, 'template-master-pbb.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response()->streamDownload(function (): void {
            $spreadsheet = $this->buildTemplateWorkbook();
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-master-pbb.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function buildTemplateWorkbook(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data');
        $dataSheet->fromArray(PbbImportSchema::COLUMNS, null, 'A1');
        
        $lastColumn = Coordinate::stringFromColumnIndex(count(PbbImportSchema::COLUMNS));
        $dataSheet->freezePane('A2');
        $dataSheet->setAutoFilter("A1:{$lastColumn}10001");
        $dataSheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F4C81']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $dataSheet->getRowDimension(1)->setRowHeight(28);

        $widths = [
            'A' => 24, 'B' => 12, 'C' => 32, 'D' => 32, 'E' => 10, 'F' => 10, 'G' => 24,
            'H' => 32, 'I' => 10, 'J' => 10, 'K' => 18, 'L' => 18, 'M' => 20, 'N' => 20,
        ];
        foreach ($widths as $column => $width) {
            $dataSheet->getColumnDimension($column)->setWidth($width);
        }

        // Format Text for identifiers and areas to prevent automatic scientific notation or weird rounding
        foreach (['A', 'K', 'L'] as $column) {
            $dataSheet->getStyle("{$column}:{$column}")->getNumberFormat()->setFormatCode('@');
        }
        $dataSheet->getStyle('M:M')->getNumberFormat()->setFormatCode('#,##0');
        $dataSheet->getStyle('N:N')->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Petunjuk');
        $instructions->fromArray([
            ['PETUNJUK IMPORT DATA PBB'],
            ['1.', 'Satu baris mewakili satu Nomor Objek Pajak (NOP) per tahun.'],
            ['2.', 'Jangan mengubah nama header pada sheet Data.'],
            ['3.', 'NOP wajib diisi dan dibaca sebagai Text.'],
            ['4.', 'Tahun pajak diisi angka penuh, misalnya 2026.'],
            ['5.', 'Data yang persis sama dengan database tidak akan diubah.'],
            ['6.', 'Batas maksimal unggah adalah 10.000 baris.'],
        ], null, 'A1');
        $instructions->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F4C81');
        $instructions->getColumnDimension('A')->setWidth(8);
        $instructions->getColumnDimension('B')->setWidth(90);
        $instructions->getStyle('A1:B8')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nop' => [
                'required',
                'string',
                'max:40',
                Rule::unique('pbb_tax_objects', 'nop')
                    ->where(fn ($query) => $query->where('tax_year', (int) $request->input('tax_year')))
                    ->ignore($id),
            ],
            'tax_year' => ['required', 'integer', 'min:2026', 'max:' . (date('Y') + 1)],
            'nama_wp_sppt' => ['required', 'string', 'max:255'],
            'jalan_wp_sppt' => ['required', 'string', 'max:255'],
            'rt_wp_sppt' => ['nullable', 'digits_between:1,3'],
            'rw_wp_sppt' => ['nullable', 'digits_between:1,3'],
            'desa_wp_sppt' => ['required', 'string', 'max:150'],
            'jalan_op_sppt' => ['required', 'string', 'max:255'],
            'rt_op_sppt' => ['nullable', 'digits_between:1,3'],
            'rw_op_sppt' => ['nullable', 'digits_between:1,3'],
            'luas_tanah_sppt' => ['nullable', 'numeric', 'min:0'],
            'luas_bangunan_sppt' => ['nullable', 'numeric', 'min:0'],
            'pbb_terhutang' => ['required', 'numeric', 'min:0'],
            'tanggal_pembayaran' => ['nullable', 'date'],
        ]);
    }

    private function normalizePayload(array $validated): array
    {
        $validated['rt_wp_sppt'] = $this->normalizeCode($validated['rt_wp_sppt'] ?? null);
        $validated['rw_wp_sppt'] = $this->normalizeCode($validated['rw_wp_sppt'] ?? null);
        $validated['rt_op_sppt'] = $this->normalizeCode($validated['rt_op_sppt'] ?? null);
        $validated['rw_op_sppt'] = $this->normalizeCode($validated['rw_op_sppt'] ?? null);

        $nopRaw = trim((string) $validated['nop']);
        $nopNormalized = preg_replace('/\D+/', '', $nopRaw) ?: '';

        return [
            'nop' => $nopRaw,
            'nop_normalized' => $nopNormalized,
            'tax_year' => (int) $validated['tax_year'],
            'nama_wp_sppt' => trim((string) $validated['nama_wp_sppt']),
            'jalan_wp_sppt' => trim((string) $validated['jalan_wp_sppt']),
            'rt_wp_sppt' => $validated['rt_wp_sppt'],
            'rw_wp_sppt' => $validated['rw_wp_sppt'],
            'desa_wp_sppt' => trim((string) $validated['desa_wp_sppt']),
            'jalan_op_sppt' => trim((string) $validated['jalan_op_sppt']),
            'rt_op_sppt' => $validated['rt_op_sppt'],
            'rw_op_sppt' => $validated['rw_op_sppt'],
            'luas_tanah_sppt' => $validated['luas_tanah_sppt'] ?? 0,
            'luas_bangunan_sppt' => $validated['luas_bangunan_sppt'] ?? 0,
            'pbb_terhutang' => $validated['pbb_terhutang'],
            'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? null,
            'tax_name' => trim((string) $validated['nama_wp_sppt']),
            'owner_name' => trim((string) $validated['nama_wp_sppt']),
            'location' => trim((string) $validated['jalan_op_sppt']),
            'tax_address' => trim((string) $validated['jalan_wp_sppt']),
            'land_area' => $validated['luas_tanah_sppt'] ?? 0,
            'building_area' => $validated['luas_bangunan_sppt'] ?? 0,
            'amount_due' => $validated['pbb_terhutang'],
            'status' => ! empty($validated['tanggal_pembayaran']) ? 'Lunas' : 'Belum Lunas',
        ];
    }

    private function normalizeCode(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';
        if ($digits === '') {
            return null;
        }

        return str_pad($digits, 3, '0', STR_PAD_LEFT);
    }
}
