<?php

namespace App\Services;

use App\Exceptions\PbbImportException;
use App\Models\PbbTaxObject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Carbon;

class PbbImportService
{
    public function preview(UploadedFile $file, ?int $yearOverride): array
    {
        PbbImportMemory::begin();
        $parser = new PbbImportParser();
        $parsed = $parser->parse($file);
        
        $rows = array_map(
            fn (array $row): array => $this->normalizeRow($row, $yearOverride),
            $parsed['rows'],
        );
        unset($parsed['rows']);
        PbbImportMemory::check();

        $nops = collect($rows)->pluck('values.nop')->filter()->unique()->values();
        $years = collect($rows)->pluck('values.tax_year')->filter()->unique()->values();

        $existingObjects = PbbTaxObject::query()
            ->whereIn('nop', $nops)
            ->whereIn('tax_year', $years)
            ->get()
            ->keyBy(fn ($item) => $item->nop . '|' . $item->tax_year);

        foreach ($rows as &$row) {
            PbbImportMemory::check();
            $key = ($row['values']['nop'] ?? '') . '|' . ($row['values']['tax_year'] ?? '');
            $existing = $existingObjects->get($key);
            $this->validateAndHydrateRow($row, $existing);
        }
        unset($row);

        $this->validateCrossRowRules($rows);

        foreach ($rows as &$row) {
            $row['status'] = $this->hasErrors($row) ? 'invalid' : $row['action'];
        }
        unset($row);

        $summary = $this->buildSummary($rows);
        unset($existingObjects);
        PbbImportMemory::check();
        
        $fingerprint = hash('sha256', json_encode(array_map(static fn (array $row): array => [
            'row' => $row['row'],
            'values' => $row['values'],
            'action' => $row['action'],
            'issues' => $row['issues'],
            'snapshot' => $row['snapshot'] ?? null,
        ], $rows), JSON_THROW_ON_ERROR));

        return [
            'sheet' => $parsed['sheet'],
            'header_row' => $parsed['header_row'],
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'fingerprint' => $fingerprint,
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    public function commit(array $preview, string $sourceFile): array
    {
        if (($preview['summary']['valid'] ?? 0) < 1) {
            throw new PbbImportException('Tidak ada baris valid yang dapat diimpor.');
        }

        return DB::transaction(function () use ($preview, $sourceFile): array {
            $validRows = collect($preview['rows'])->reject(fn (array $row): bool => ($row['status'] ?? 'invalid') === 'invalid');
            
            $upsertData = [];
            $result = [
                'inserted' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'skipped' => (int) ($preview['summary']['invalid'] ?? 0),
                'warnings' => (int) ($preview['summary']['warnings'] ?? 0),
            ];

            foreach ($preview['rows'] as $row) {
                if (($row['status'] ?? 'invalid') === 'invalid') {
                    continue;
                }

                $action = $row['action'];
                if ($action === 'unchanged') {
                    $result['unchanged']++;
                    continue;
                }

                $payload = $row['tax_object_data'];
                $payload['notes'] = $sourceFile;
                $upsertData[] = $payload;

                if ($action === 'new') {
                    $result['inserted']++;
                } else {
                    $result['updated']++;
                }
            }
            
            if (! empty($upsertData)) {
                $uniqueKeys = ['nop', 'tax_year'];
                $updateKeys = [
                    'nop_normalized', 'nama_wp_sppt', 'jalan_wp_sppt', 'rt_wp_sppt', 'rw_wp_sppt', 'desa_wp_sppt',
                    'jalan_op_sppt', 'rt_op_sppt', 'rw_op_sppt', 'luas_tanah_sppt', 'luas_bangunan_sppt',
                    'pbb_terhutang', 'tanggal_pembayaran', 'tax_name', 'owner_name', 'location',
                    'tax_address', 'land_area', 'building_area', 'amount_due', 'status', 'notes',
                ];
                
                // Chunk the upsert manually to avoid very large SQL queries
                foreach (array_chunk($upsertData, 500) as $chunk) {
                    PbbTaxObject::upsert($chunk, $uniqueKeys, $updateKeys);
                }
            }

            return $result;
        }, 3);
    }

    public function publicPreview(array $preview): array
    {
        return [
            'sheet' => $preview['sheet'],
            'header_row' => $preview['header_row'],
            'summary' => $preview['summary'],
            'rows' => array_map(static fn (array $row): array => [
                'row' => $row['row'],
                'nop' => $row['values']['nop'],
                'tax_year' => $row['values']['tax_year'],
                'nama_wp_sppt' => $row['values']['nama_wp_sppt'],
                'status' => $row['status'],
                'action' => $row['action'],
                'issues' => $row['issues'],
            ], $preview['rows']),
        ];
    }

    private function normalizeRow(array $rawRow, ?int $yearOverride): array
    {
        $issues = [];
        $cells = $rawRow['_cells'];
        
        $text = function (string $field) use ($cells): ?string {
            return $this->textValue($cells[$field]['value'] ?? null);
        };
        
        $rawNop = $this->identifierValue('nop', $cells['nop'] ?? null, $issues, false);
        $nop = $rawNop !== null ? trim($rawNop) : null;
        
        $yearValue = $text('tax_year');
        $taxYear = $yearOverride ?: ($yearValue !== null ? $this->integerValue('tax_year', $yearValue, $issues) : null);
        
        $values = [
            'nop' => $nop,
            'tax_year' => $taxYear,
            'nama_wp_sppt' => $text('nama_wp_sppt'),
            'jalan_wp_sppt' => $text('jalan_wp_sppt'),
            'rt_wp_sppt' => $this->codeValue('rt_wp_sppt', $text('rt_wp_sppt'), 3, $issues, 1, 3),
            'rw_wp_sppt' => $this->codeValue('rw_wp_sppt', $text('rw_wp_sppt'), 3, $issues, 1, 3),
            'desa_wp_sppt' => $text('desa_wp_sppt') ?: config('village.name', 'Desa Lambanggelun'),
            'jalan_op_sppt' => $text('jalan_op_sppt') ?: $text('jalan_wp_sppt'),
            'rt_op_sppt' => $this->codeValue('rt_op_sppt', $text('rt_op_sppt'), 3, $issues, 1, 3),
            'rw_op_sppt' => $this->codeValue('rw_op_sppt', $text('rw_op_sppt'), 3, $issues, 1, 3),
            'luas_tanah_sppt' => $this->decimalValue('luas_tanah_sppt', $text('luas_tanah_sppt'), $issues),
            'luas_bangunan_sppt' => $this->decimalValue('luas_bangunan_sppt', $text('luas_bangunan_sppt'), $issues),
            'pbb_terhutang' => $this->decimalValue('pbb_terhutang', $text('pbb_terhutang'), $issues),
            'tanggal_pembayaran' => $this->dateValue($cells['tanggal_pembayaran'] ?? null, $issues),
        ];

        return [
            'row' => $rawRow['_row'],
            'values' => $values,
            'provided_values' => [],
            'action' => 'new',
            'issues' => $issues,
        ];
    }

    private function validateAndHydrateRow(array &$row, ?PbbTaxObject $existing): void
    {
        $values = $row['values'];
        $providedValues = [];
        
        if (! $values['nop']) {
            $this->addIssue($row, 'error', 'nop', 'nop_required', 'NOP wajib diisi.');
        } else {
            // NOP validation
            $nopRaw = $values['nop'];
            $nopNormalized = preg_replace('/\D+/', '', $nopRaw) ?: '';
            if (strlen($nopNormalized) < 10) {
                 $this->addIssue($row, 'error', 'nop', 'invalid_nop', 'NOP terlalu pendek, minimal 10 digit (angka/titik/strip).');
            }
        }
        
        if (! $values['tax_year']) {
            $this->addIssue($row, 'error', 'tax_year', 'tax_year_required', 'Tahun Pajak wajib diisi.');
        } else {
            $currentYear = (int) date('Y');
            if ($values['tax_year'] < 2026 || $values['tax_year'] > ($currentYear + 1)) {
                 $this->addIssue($row, 'error', 'tax_year', 'invalid_year', "Tahun Pajak harus antara 2026 dan " . ($currentYear + 1) . ".");
            }
        }
        
        if (! $values['nama_wp_sppt']) {
            $this->addIssue($row, 'error', 'nama_wp_sppt', 'nama_wp_sppt_required', 'Nama WP SPPT wajib diisi.');
        }

        if ($values['pbb_terhutang'] === null || $values['pbb_terhutang'] < 0) {
            $this->addIssue($row, 'error', 'pbb_terhutang', 'pbb_terhutang_required', 'Nominal PBB Terhutang wajib diisi dengan angka positif.');
        }

        foreach (['nama_wp_sppt' => 255, 'jalan_wp_sppt' => 255, 'desa_wp_sppt' => 150, 'jalan_op_sppt' => 255] as $field => $limit) {
            if ($values[$field] !== null && mb_strlen($values[$field]) > $limit) {
                $this->addIssue($row, 'error', $field, 'too_long', "Nilai {$field} melebihi {$limit} karakter.");
            }
        }

        // Hydration logic
        $merged = $values;
        if ($existing) {
            $fallbacks = [
                'nama_wp_sppt' => $existing->nama_wp_sppt,
                'jalan_wp_sppt' => $existing->jalan_wp_sppt,
                'rt_wp_sppt' => $existing->rt_wp_sppt,
                'rw_wp_sppt' => $existing->rw_wp_sppt,
                'desa_wp_sppt' => $existing->desa_wp_sppt,
                'jalan_op_sppt' => $existing->jalan_op_sppt,
                'rt_op_sppt' => $existing->rt_op_sppt,
                'rw_op_sppt' => $existing->rw_op_sppt,
                'luas_tanah_sppt' => $existing->luas_tanah_sppt,
                'luas_bangunan_sppt' => $existing->luas_bangunan_sppt,
                'pbb_terhutang' => $existing->pbb_terhutang,
                'tanggal_pembayaran' => $existing->tanggal_pembayaran?->toDateString(),
            ];
            foreach ($fallbacks as $field => $fallback) {
                if ($merged[$field] === null || $merged[$field] === '') {
                    $merged[$field] = $fallback;
                }
            }
        }

        $nopNormalized = preg_replace('/\D+/', '', (string) $merged['nop']) ?: '';
        $taxObjectData = [
            'nop' => $merged['nop'],
            'nop_normalized' => $nopNormalized,
            'tax_year' => $merged['tax_year'],
            'nama_wp_sppt' => $merged['nama_wp_sppt'],
            'jalan_wp_sppt' => $merged['jalan_wp_sppt'],
            'rt_wp_sppt' => $merged['rt_wp_sppt'],
            'rw_wp_sppt' => $merged['rw_wp_sppt'],
            'desa_wp_sppt' => $merged['desa_wp_sppt'],
            'jalan_op_sppt' => $merged['jalan_op_sppt'],
            'rt_op_sppt' => $merged['rt_op_sppt'],
            'rw_op_sppt' => $merged['rw_op_sppt'],
            'luas_tanah_sppt' => $merged['luas_tanah_sppt'] ?? 0,
            'luas_bangunan_sppt' => $merged['luas_bangunan_sppt'] ?? 0,
            'pbb_terhutang' => $merged['pbb_terhutang'] ?? 0,
            'tanggal_pembayaran' => $merged['tanggal_pembayaran'],
            'tax_name' => $merged['nama_wp_sppt'],
            'owner_name' => $merged['nama_wp_sppt'],
            'location' => $merged['jalan_op_sppt'] ?: $merged['jalan_wp_sppt'],
            'tax_address' => $merged['jalan_wp_sppt'],
            'land_area' => $merged['luas_tanah_sppt'] ?? 0,
            'building_area' => $merged['luas_bangunan_sppt'] ?? 0,
            'amount_due' => $merged['pbb_terhutang'] ?? 0,
            'status' => ! empty($merged['tanggal_pembayaran']) ? 'Lunas' : 'Belum Lunas',
        ];

        $row['values'] = $merged;
        $row['provided_values'] = $providedValues;
        $row['tax_object_data'] = $taxObjectData;
        $row['snapshot'] = [
            'tax_object' => $existing?->updated_at?->format('Y-m-d H:i:s.u'),
        ];

        if ($existing === null) {
            $row['action'] = 'new';
            return;
        }

        $changed = $this->modelDiffers($existing, $taxObjectData);

        if ($changed) {
            $row['action'] = 'update';
        } else {
            $row['action'] = 'unchanged';
            $this->addIssue($row, 'info', 'nop', 'unchanged', 'Data persis sama dengan database, tidak ada perubahan.');
        }
    }

    private function validateCrossRowRules(array &$rows): void
    {
        $keys = [];
        foreach ($rows as $index => $row) {
            if ($row['values']['nop'] && $row['values']['tax_year']) {
                $key = $row['values']['nop'] . '|' . $row['values']['tax_year'];
                $keys[$key][] = $index;
            }
        }

        foreach ($keys as $key => $indices) {
            if (count($indices) > 1) {
                foreach ($indices as $index) {
                    $this->addIssue($rows[$index], 'error', 'nop', 'duplicate_nop_year', 'NOP dan Tahun Pajak ganda di dalam file ini.');
                }
            }
        }
    }

    private function buildSummary(array $rows): array
    {
        $validRows = array_filter($rows, fn (array $row): bool => ($row['status'] ?? 'invalid') !== 'invalid');
        $validActions = array_column($validRows, 'action');

        return [
            'total' => count($rows),
            'valid' => count($validRows),
            'invalid' => count($rows) - count($validRows),
            'warnings' => count(array_filter($rows, fn (array $row): bool => collect($row['issues'])->contains('severity', 'warning'))),
            'inserted' => count(array_filter($validActions, fn (string $a): bool => $a === 'new')),
            'updated' => count(array_filter($validActions, fn (string $a): bool => $a === 'update')),
            'unchanged' => count(array_filter($validActions, fn (string $a): bool => $a === 'unchanged')),
        ];
    }

    private function identifierValue(string $field, ?array $cell, array &$issues, bool $digitsOnly = true): ?string
    {
        $value = $cell['value'] ?? null;
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (($cell['type'] ?? null) === DataType::TYPE_NUMERIC || ($cell['type'] ?? null) === DataType::TYPE_FORMULA) {
            $issues[] = $this->issue('error', $field, 'numeric_identifier', ucfirst(str_replace('_', ' ', $field)).' disarankan diformat sebagai Text di Excel agar nomor panjang tidak dibulatkan.');
            // Allow processing string cast anyway, but warn if scientific
        }

        $text = ltrim(trim((string) $value), "'");
        if (preg_match('/^[+-]?[0-9]+(?:[.,][0-9]+)?e[+-]?[0-9]+$/i', $text)) {
            $issues[] = $this->issue('error', $field, 'scientific_identifier', ucfirst(str_replace('_', ' ', $field)).' terbaca sebagai notasi ilmiah. Ubah kolom menjadi Text dan isi ulang dari sumber asli.');
            return null;
        }

        if ($digitsOnly) {
            $digits = preg_replace('/[\s.\-]+/', '', $text) ?? $text;
            if (! ctype_digit($digits)) {
                $issues[] = $this->issue('error', $field, 'invalid_identifier', ucfirst(str_replace('_', ' ', $field)).' hanya boleh berisi angka.');
                return null;
            }
            return $digits;
        }

        return $text;
    }

    private function dateValue(?array $cell, array &$issues): ?string
    {
        $value = $cell['value'] ?? null;
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (($cell['type'] ?? null) === DataType::TYPE_NUMERIC && ($cell['is_date'] ?? false)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Handled by the common error below.
            }
        }

        $text = trim((string) $value);
        foreach (['!d-m-Y', '!d/m/Y', '!Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $text);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        $issues[] = $this->issue('error', 'tanggal_pembayaran', 'invalid_date', 'Tanggal harus berupa tanggal Excel atau format dd-mm-yyyy, dd/mm/yyyy, atau yyyy-mm-dd.');
        return null;
    }

    private function codeValue(string $field, ?string $value, int $pad, array &$issues, int $min = 1, int $max = 3): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = trim($value);
        if ($digits === '' || strlen($digits) < $min || strlen($digits) > $max) {
            $issues[] = $this->issue('warning', $field, 'optional_ignored', "Kolom {$field} harus berisi {$min}-{$max} karakter. Isian tidak digunakan.");
            return null;
        }

        return $pad > 0 ? str_pad($digits, $pad, '0', STR_PAD_LEFT) : $digits;
    }

    private function integerValue(string $field, ?string $value, array &$issues): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! ctype_digit($value)) {
            $issues[] = $this->issue('error', $field, 'invalid_integer', "Kolom {$field} harus berupa angka bulat.");
            return null;
        }

        return (int) $value;
    }

    private function decimalValue(string $field, ?string $value, array &$issues): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';
        if ($raw === '' || $raw === '-') {
            return null;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $normalized = str_replace('.', '', $raw);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $raw);
        } else {
            $normalized = $raw;
        }

        if (! is_numeric($normalized)) {
            $issues[] = $this->issue('error', $field, 'invalid_decimal', "Kolom {$field} harus berupa angka/desimal.");
            return null;
        }

        return (float) $normalized;
    }

    private function textValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function modelDiffers($model, array $values): bool
    {
        foreach ($values as $field => $value) {
            $current = $model->getAttribute($field);
            if ($current instanceof Carbon) {
                $current = $current->toDateString();
            }
            if ((string) ($current ?? '') !== (string) ($value ?? '')) {
                return true;
            }
        }

        return false;
    }

    private function hasErrors(array $row): bool
    {
        return collect($row['issues'])->contains('severity', 'error');
    }

    private function addIssue(array &$row, string $severity, string $field, string $code, string $message): void
    {
        $candidate = $this->issue($severity, $field, $code, $message);
        foreach ($row['issues'] as $issue) {
            if ($issue['code'] === $candidate['code'] && $issue['field'] === $candidate['field']) {
                return;
            }
        }
        $row['issues'][] = $candidate;
    }

    private function issue(string $severity, string $field, string $code, string $message): array
    {
        $hint = match ($code) {
            'optional_ignored' => 'Baris tetap dapat diimpor, isian yang salah format diabaikan.',
            'numeric_identifier', 'scientific_identifier' => 'Ubah format kolom NOP menjadi Text, lalu isi ulang dari sumber asli.',
            'duplicate_nop_year' => 'Pastikan satu baris untuk satu NOP per tahun.',
            'invalid_date' => 'Gunakan tanggal Excel asli atau salah satu format tanggal yang disebutkan.',
            'unchanged' => 'Data lama dipertahankan karena isi file sama.',
            default => str_ends_with($code, '_required') ? 'Lengkapi kolom ini lalu jalankan pratinjau ulang.' : 'Perbaiki nilai pada kolom ini lalu jalankan pratinjau ulang.',
        };

        $fieldLabel = match ($field) {
            'nop' => 'NOP', 'tax_year' => 'Tahun Pajak', 'nama_wp_sppt' => 'Nama WP',
            'pbb_terhutang' => 'PBB Terhutang',
            default => Str::ucfirst(str_replace('_', ' ', $field)),
        };
        return [...compact('severity', 'field', 'code', 'message', 'hint'), 'field_label' => $fieldLabel];
    }
}
