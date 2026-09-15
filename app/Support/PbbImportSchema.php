<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PbbImportSchema
{
    public const COLUMNS = [
        'nop',
        'tax_year',
        'nama_wp_sppt',
        'jalan_wp_sppt',
        'rt_wp_sppt',
        'rw_wp_sppt',
        'desa_wp_sppt',
        'jalan_op_sppt',
        'rt_op_sppt',
        'rw_op_sppt',
        'luas_tanah_sppt',
        'luas_bangunan_sppt',
        'pbb_terhutang',
        'tanggal_pembayaran',
    ];

    public const MINIMUM_HEADERS = [
        'nop',
        'tax_year',
        'nama_wp_sppt',
    ];

    /** @var array<string, array<int, string>> */
    private const ALIASES = [
        'nop' => ['nop', 'nomor_objek_pajak', 'nomor_op'],
        'tax_year' => ['tax_year', 'tahun', 'tahun_pajak', 'tahun_sppt'],
        'nama_wp_sppt' => ['nama_wp_sppt', 'nama_wp', 'nama_wajib_pajak', 'nama_wajib_pajak_sppt', 'tax_name', 'owner_name'],
        'jalan_wp_sppt' => ['jalan_wp_sppt', 'alamat_wp_sppt', 'tax_address', 'alamat_wp'],
        'rt_wp_sppt' => ['rt_wp_sppt', 'rt_wp', 'rt'],
        'rw_wp_sppt' => ['rw_wp_sppt', 'rw_wp', 'rw'],
        'desa_wp_sppt' => ['desa_wp_sppt', 'desa_wp', 'desa'],
        'jalan_op_sppt' => ['jalan_op_sppt', 'alamat_op_sppt', 'location', 'alamat_op'],
        'rt_op_sppt' => ['rt_op_sppt', 'rt_op'],
        'rw_op_sppt' => ['rw_op_sppt', 'rw_op'],
        'luas_tanah_sppt' => ['luas_tanah_sppt', 'land_area', 'luas_tanah'],
        'luas_bangunan_sppt' => ['luas_bangunan_sppt', 'building_area', 'luas_bangunan'],
        'pbb_terhutang' => ['pbb_terhutang', 'amount_due', 'pajak_terhutang', 'terhutang'],
        'tanggal_pembayaran' => ['tanggal_pembayaran', 'tgl_pembayaran', 'payment_date'],
    ];

    /** @var array<string, string>|null */
    private static ?array $headerLookup = null;

    public static function normalizeHeader(mixed $value): string
    {
        $normalized = Str::ascii(trim((string) $value));
        $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
        $normalized = Str::lower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    public static function canonicalHeader(mixed $value): ?string
    {
        $normalized = self::normalizeHeader($value);
        if ($normalized === '') {
            return null;
        }

        if (self::$headerLookup === null) {
            self::$headerLookup = [];
            foreach (self::ALIASES as $canonical => $aliases) {
                foreach ($aliases as $alias) {
                    self::$headerLookup[self::normalizeHeader($alias)] = $canonical;
                }
            }
        }

        return self::$headerLookup[$normalized] ?? null;
    }

    /** @return array<int, string> */
    public static function missingMinimumHeaders(array $headers): array
    {
        return array_values(array_diff(self::MINIMUM_HEADERS, $headers));
    }
}
