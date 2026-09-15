<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PopulationImportException;
use App\Http\Controllers\Controller;
use App\Models\PopulationImportRun;
use App\Models\PopulationRecord;
use App\Services\PopulationImportParser;
use App\Services\PopulationImportService;
use App\Support\PopulationImportSchema;
use App\Support\PopulationImportToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PopulationImportController extends Controller
{
    public function __construct(
        private readonly PopulationImportService $importService,
        private readonly PopulationImportToken $tokenService,
    ) {
    }

    public function preview(Request $request): JsonResponse
    {
        $started = microtime(true);
        $validated = $this->validateRequest($request);
        $file = $request->file('file');

        try {
            $preview = $this->importService->preview($file, $validated['hamlet_override'] ?? null);
            $token = $this->tokenService->issue(
                (int) $request->user()->id,
                (string) $preview['file_hash'],
                $preview['fingerprint'],
                $validated['hamlet_override'] ?? null,
            );

            return response()->json([
                'message' => 'Pratinjau selesai. Periksa hasil sebelum mengimpor.',
                'token' => $token,
                'preview' => $this->importService->publicPreview($preview),
            ]);
        } catch (PopulationImportException $exception) {
            if ($exception->getPrevious() !== null) {
                Log::error('Population import workbook reader failed.', [
                    'exception_class' => $exception->getPrevious()::class,
                    'elapsed_ms' => (int) ((microtime(true) - $started) * 1000),
                    'peak_memory_bytes' => memory_get_peak_usage(true),
                ]);
            }
            if ($exception->getCode() === 507) {
                Log::error('Population import preview stopped at memory budget.', [
                    'elapsed_ms' => (int) ((microtime(true) - $started) * 1000),
                    'peak_memory_bytes' => memory_get_peak_usage(true),
                ]);
            }
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['file' => [$exception->getMessage()]],
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Population import preview failed.', [
                'user_id' => $request->user()?->id,
                'exception_class' => $exception::class,
                'elapsed_ms' => (int) ((microtime(true) - $started) * 1000),
                'peak_memory_bytes' => memory_get_peak_usage(true),
            ]);
            return response()->json([
                'message' => 'File gagal diproses. Pastikan file tidak rusak dan menggunakan template terbaru.',
                'errors' => ['file' => ['File gagal diproses. Pastikan file tidak rusak dan menggunakan template terbaru.']],
            ], 422);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, requireToken: true);
        $file = $request->file('file');

        try {
            $token = $this->tokenService->decode($validated['preview_token'], (int) $request->user()->id);
            if (($validated['hamlet_override'] ?? null) !== $token['hamlet_override']) {
                throw new PopulationImportException('Pilihan dusun berubah setelah pratinjau. Silakan periksa file kembali.');
            }
            $fileHash = (string) hash_file('sha256', $file->getRealPath());
            if (! hash_equals($token['file_hash'], $fileHash)) {
                throw new PopulationImportException('File berubah setelah pratinjau. Silakan lakukan pratinjau ulang.');
            }

            $preview = $this->importService->preview($file, $token['hamlet_override']);
            if (! hash_equals($token['fingerprint'], $preview['fingerprint'])) {
                $replacementToken = $this->tokenService->issue(
                    (int) $request->user()->id,
                    $fileHash,
                    $preview['fingerprint'],
                    $token['hamlet_override'],
                );

                return response()->json([
                    'message' => 'Data berubah sejak pratinjau terakhir. Tinjau kembali hasil terbaru sebelum mengimpor.',
                    'token' => $replacementToken,
                    'preview' => $this->importService->publicPreview($preview),
                ], 409);
            }

            $run = PopulationImportRun::create([
                'user_id' => $request->user()->id,
                'original_filename' => $file->getClientOriginalName(),
                'file_hash' => $fileHash,
                'format' => strtolower($file->getClientOriginalExtension()),
                'status' => 'processing',
                'total_rows' => $preview['summary']['total'],
                'valid_rows' => $preview['summary']['valid'],
                'invalid_rows' => $preview['summary']['invalid'],
                'warning_count' => $preview['summary']['warnings'],
                'households_created' => $preview['summary']['households_created'],
            ]);

            try {
                $result = $this->importService->commit($preview, $file->getClientOriginalName());
                $run->update([
                    'status' => 'completed',
                    'residents_created' => $result['residents_created'],
                    'residents_updated' => $result['residents_updated'],
                    'residents_unchanged' => $result['residents_unchanged'],
                    'residents_moved' => $result['residents_moved'],
                    'invalid_rows' => $result['skipped'],
                    'warning_count' => $result['warnings'],
                    'completed_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                $run->update(['status' => 'failed', 'completed_at' => now()]);
                throw $exception;
            }

            $message = $this->completionMessage($result);
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'result' => $result,
                'redirect' => route('dashboard.population-records.index'),
            ]);
        } catch (PopulationImportException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['file' => [$exception->getMessage()]],
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Population import commit failed.', [
                'user_id' => $request->user()?->id,
                'filename' => $file?->getClientOriginalName(),
                'exception_class' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Import gagal disimpan dan seluruh perubahan dibatalkan. Silakan periksa laporan lalu coba lagi.',
            ], 500);
        }
    }

    public function template(Request $request)
    {
        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function (): void {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, PopulationImportSchema::COLUMNS, ';', '"', '\\');
                fclose($handle);
            }, 'template-kependudukan.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response()->streamDownload(function (): void {
            $spreadsheet = $this->buildTemplateWorkbook();
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-kependudukan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function validateRequest(Request $request, bool $requireToken = false): array
    {
        return $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:15360'],
            'hamlet_override' => ['nullable', Rule::in(PopulationRecord::HAMLETS)],
            'preview_token' => [$requireToken ? 'required' : 'nullable', 'string'],
        ]);
    }

    private function completionMessage(array $result): string
    {
        return sprintf(
            'Import selesai: %d KK baru, %d penduduk baru, %d diperbarui, %d tidak berubah, %d pindah KK, dan %d dilewati.',
            $result['households_created'],
            $result['residents_created'],
            $result['residents_updated'],
            $result['residents_unchanged'],
            $result['residents_moved'],
            $result['skipped'],
        );
    }

    private function buildTemplateWorkbook(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data');
        $dataSheet->fromArray(PopulationImportSchema::COLUMNS, null, 'A1');
        $lastColumn = Coordinate::stringFromColumnIndex(count(PopulationImportSchema::COLUMNS));
        $lastDataRow = PopulationImportParser::MAX_ROWS + 1;
        $dataSheet->freezePane('A2');
        $dataSheet->setAutoFilter("A1:{$lastColumn}{$lastDataRow}");
        $dataSheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F4C81']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $dataSheet->getRowDimension(1)->setRowHeight(28);

        $widths = [
            'A' => 21, 'B' => 28, 'C' => 36, 'D' => 8, 'E' => 8, 'F' => 12, 'G' => 18,
            'H' => 24, 'I' => 24, 'J' => 24, 'K' => 24, 'L' => 12, 'M' => 23, 'N' => 21,
            'O' => 28, 'P' => 17, 'Q' => 22, 'R' => 16, 'S' => 16, 'T' => 22, 'U' => 24,
            'V' => 24, 'W' => 18, 'X' => 18, 'Y' => 20, 'Z' => 24, 'AA' => 24, 'AB' => 15,
        ];
        foreach ($widths as $column => $width) {
            $dataSheet->getColumnDimension($column)->setWidth($width);
        }

        foreach (['A', 'D', 'E', 'F', 'N', 'X', 'Y'] as $column) {
            // Column defaults preserve text entry without creating 70,000 blank cells.
            $dataSheet->getStyle("{$column}:{$column}")->getNumberFormat()->setFormatCode('@');
        }
        $dataSheet->getStyle('R:R')->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        $this->addListValidation($dataSheet, "G2:G{$lastDataRow}", PopulationRecord::HAMLETS);
        $this->addListValidation($dataSheet, "M2:M{$lastDataRow}", PopulationRecord::STATUS_HUBUNGAN_OPTIONS);
        $this->addListValidation($dataSheet, "P2:P{$lastDataRow}", ['Laki-laki', 'Perempuan']);
        $this->addListValidation($dataSheet, "V2:V{$lastDataRow}", PopulationRecord::STATUS_PERKAWINAN_OPTIONS);
        $this->addListValidation($dataSheet, "W2:W{$lastDataRow}", ['WNI', 'WNA']);
        $this->addListValidation($dataSheet, "AB2:AB{$lastDataRow}", [...PopulationRecord::GOLONGAN_DARAH_OPTIONS, 'Tidak Tahu']);

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Petunjuk');
        $instructions->fromArray([
            ['PETUNJUK IMPORT DATA KEPENDUDUKAN'],
            ['1.', 'Satu baris mewakili satu penduduk atau anggota keluarga.'],
            ['2.', 'Jangan mengubah nama header pada sheet Data.'],
            ['3.', 'NIK dan no_kk wajib 16 digit serta harus tetap berformat Text. Jangan menyalin nilai yang sudah dibulatkan Excel.'],
            ['4.', 'tanggal_lahir diisi sebagai tanggal Excel atau dd-mm-yyyy.'],
            ['5.', 'Untuk KK baru, sertakan tepat satu anggota berstatus Kepala Keluarga.'],
            ['6.', 'Sel kosong pada penduduk existing tidak menghapus data lama.'],
            ['7.', 'Import bersifat merge dan tidak menghapus penduduk yang tidak tercantum di file.'],
            ['8.', 'Gunakan pratinjau dan perbaiki baris merah sebelum menekan Import.'],
            ['9.', 'Golongan darah, pendidikan, nama orang tua, alamat, kode pos, dan dokumen boleh diisi Tidak Tahu, N/A, atau tanda - bila belum diketahui; data lama tetap dipertahankan. WNA tetap wajib memiliki paspor atau KITAS/KITAP. Golongan darah di luar A/B/AB/O menjadi catatan.'],
            ['10.', 'Template menyediakan hingga 6.000 baris data. Baris kosong dan format kosong tidak perlu dihapus sebelum pemeriksaan file.'],
        ], null, 'A1');
        $instructions->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F4C81');
        $instructions->getColumnDimension('A')->setWidth(8);
        $instructions->getColumnDimension('B')->setWidth(105);
        $instructions->getStyle('A1:B11')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function addListValidation(Worksheet $sheet, string $range, array $values): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Nilai tidak valid');
        $validation->setError('Pilih salah satu nilai yang tersedia.');
        $validation->setFormula1('"'.implode(',', $values).'"');
        $validation->setSqref($range);
    }
}
