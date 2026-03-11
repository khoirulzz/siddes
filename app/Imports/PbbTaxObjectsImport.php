<?php

namespace App\Imports;

use App\Models\PbbTaxObject;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PbbTaxObjectsImport implements ToCollection, WithHeadingRow
{
    public int $inserted = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(
        private readonly ?int $yearOverride = null,
        private readonly ?string $sourceFile = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $payload = $this->mapRow($row);
            if (! $this->isValidRow($payload)) {
                $this->skipped++;
                continue;
            }

            $object = PbbTaxObject::query()->firstOrNew([
                'nop' => $payload['nop'],
                'tax_year' => $payload['tax_year'],
            ]);

            $isNew = ! $object->exists;
            $object->fill([
                'nop' => $payload['nop'],
                'tax_year' => $payload['tax_year'],
                'nama_wp_sppt' => $payload['nama_wp_sppt'],
                'jalan_wp_sppt' => $payload['jalan_wp_sppt'],
                'rt_wp_sppt' => $payload['rt_wp_sppt'],
                'rw_wp_sppt' => $payload['rw_wp_sppt'],
                'desa_wp_sppt' => $payload['desa_wp_sppt'],
                'jalan_op_sppt' => $payload['jalan_op_sppt'],
                'rt_op_sppt' => $payload['rt_op_sppt'],
                'rw_op_sppt' => $payload['rw_op_sppt'],
                'luas_tanah_sppt' => $payload['luas_tanah_sppt'],
                'luas_bangunan_sppt' => $payload['luas_bangunan_sppt'],
                'pbb_terhutang' => $payload['pbb_terhutang'],
                'tanggal_pembayaran' => $payload['tanggal_pembayaran'],
                'tax_name' => $payload['nama_wp_sppt'],
                'owner_name' => $payload['nama_wp_sppt'],
                'location' => $payload['jalan_op_sppt'] ?: $payload['jalan_wp_sppt'],
                'tax_address' => $payload['jalan_wp_sppt'],
                'land_area' => $payload['luas_tanah_sppt'],
                'building_area' => $payload['luas_bangunan_sppt'],
                'amount_due' => $payload['pbb_terhutang'],
                'status' => $payload['tanggal_pembayaran'] ? 'Lunas' : 'Belum Lunas',
                'notes' => $this->sourceFile ?: ('import-row-' . ($index + 1)),
            ]);
            $object->save();

            if ($isNew) {
                $this->inserted++;
            } else {
                $this->updated++;
            }
        }
    }

    private function mapRow(Collection $row): array
    {
        $tahun = $this->yearOverride ?: $this->toInt($this->pick($row, [
            'tax_year',
            'tahun',
            'tahun_pajak',
            'tahun_sppt',
        ]));

        $nop = $this->normalizeNop((string) $this->pick($row, ['nop', 'nomor_objek_pajak', 'nomor_op']));

        $namaWp = $this->pick($row, [
            'nama_wp_sppt',
            'nama_wp',
            'nama_wajib_pajak',
            'nama_wajib_pajak_sppt',
            'tax_name',
            'owner_name',
        ]);

        $jalanWp = $this->pick($row, ['jalan_wp_sppt', 'alamat_wp_sppt', 'tax_address', 'alamat_wp']);
        $jalanOp = $this->pick($row, ['jalan_op_sppt', 'alamat_op_sppt', 'location', 'alamat_op']) ?: $jalanWp;

        $luasTanah = $this->toDecimal($this->pick($row, ['luas_tanah_sppt', 'land_area', 'luas_tanah']));
        $luasBangunan = $this->toDecimal($this->pick($row, ['luas_bangunan_sppt', 'building_area', 'luas_bangunan']));
        $terhutang = $this->toDecimal($this->pick($row, ['pbb_terhutang', 'amount_due', 'pajak_terhutang']));

        return [
            'nop' => $nop,
            'tax_year' => $tahun,
            'nama_wp_sppt' => $namaWp,
            'jalan_wp_sppt' => $jalanWp,
            'rt_wp_sppt' => $this->normalizeCode($this->pick($row, ['rt_wp_sppt', 'rt_wp', 'rt'])),
            'rw_wp_sppt' => $this->normalizeCode($this->pick($row, ['rw_wp_sppt', 'rw_wp', 'rw'])),
            'desa_wp_sppt' => $this->pick($row, ['desa_wp_sppt', 'desa_wp', 'desa']) ?: config('village.name', 'Desa Lambanggelun'),
            'jalan_op_sppt' => $jalanOp,
            'rt_op_sppt' => $this->normalizeCode($this->pick($row, ['rt_op_sppt', 'rt_op'])),
            'rw_op_sppt' => $this->normalizeCode($this->pick($row, ['rw_op_sppt', 'rw_op'])),
            'luas_tanah_sppt' => $luasTanah,
            'luas_bangunan_sppt' => $luasBangunan,
            'pbb_terhutang' => $terhutang,
            'tanggal_pembayaran' => $this->toDate($this->pick($row, ['tanggal_pembayaran', 'tgl_pembayaran', 'payment_date'])),
        ];
    }

    private function isValidRow(array $payload): bool
    {
        if (! $payload['nop'] || ! $payload['tax_year'] || ! $payload['nama_wp_sppt']) {
            return false;
        }

        if (! is_numeric((string) $payload['pbb_terhutang'])) {
            return false;
        }

        return true;
    }

    private function pick(Collection $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row->get($key);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function normalizeNop(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return $value;
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

    private function toInt(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';
        return $digits !== '' ? (int) $digits : null;
    }

    private function toDecimal(?string $value): float
    {
        if (! $value) {
            return 0.0;
        }

        $raw = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';
        if ($raw === '' || $raw === '-') {
            return 0.0;
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
            return 0.0;
        }

        return (float) $normalized;
    }

    private function toDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function summary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
        ];
    }
}
