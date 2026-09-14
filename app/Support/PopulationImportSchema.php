<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PopulationImportSchema
{
    public const COLUMNS = [
        'no_kk',
        'nama_kepala_keluarga',
        'alamat',
        'rt',
        'rw',
        'kode_pos',
        'dusun',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'no_urut_kk',
        'status_hubungan',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'pendidikan',
        'jenis_pekerjaan',
        'status_perkawinan',
        'kewarganegaraan',
        'no_paspor',
        'no_kitas_kitap',
        'nama_ayah',
        'nama_ibu',
        'golongan_darah',
    ];

    public const MINIMUM_HEADERS = [
        'no_kk',
        'nik',
        'nama_lengkap',
    ];

    /** @var array<string, array<int, string>> */
    private const ALIASES = [
        'no_kk' => ['no_kk', 'nkk', 'nomor_kk', 'nomor_kartu_keluarga', 'no_kartu_keluarga', 'kk'],
        'nama_kepala_keluarga' => ['nama_kepala_keluarga', 'kepala_keluarga', 'nama_kepala_kk'],
        'alamat' => ['alamat', 'alamat_lengkap', 'address', 'address_detail'],
        'rt' => ['rt', 'nomor_rt', 'no_rt'],
        'rw' => ['rw', 'nomor_rw', 'no_rw'],
        'kode_pos' => ['kode_pos', 'kodepos', 'postal_code'],
        'dusun' => ['dusun', 'hamlet', 'alamat_dusun', 'dukuh'],
        'desa' => ['desa', 'kelurahan', 'desa_kelurahan'],
        'kecamatan' => ['kecamatan'],
        'kabupaten' => ['kabupaten', 'kabupaten_kota', 'kota'],
        'provinsi' => ['provinsi'],
        'no_urut_kk' => ['no_urut_kk', 'nomor_urut_kk', 'urutan_kk', 'no_urut', 'urutan'],
        'status_hubungan' => [
            'status_hubungan',
            'status_hubungan_dalam_keluarga',
            'status_hubungan_keluarga',
            'hubungan_keluarga',
            'shdk',
        ],
        'nik' => ['nik', 'nomor_induk_kependudukan', 'no_induk_kependudukan'],
        'nama_lengkap' => ['nama_lengkap', 'full_name', 'nama', 'nama_anggota_keluarga'],
        'jenis_kelamin' => ['jenis_kelamin', 'gender', 'jk'],
        'tempat_lahir' => ['tempat_lahir', 'birth_place'],
        'tanggal_lahir' => ['tanggal_lahir', 'tgl_lahir', 'birth_date'],
        'agama' => ['agama', 'religion'],
        'pendidikan' => ['pendidikan', 'pendidikan_terakhir', 'education'],
        'jenis_pekerjaan' => ['jenis_pekerjaan', 'pekerjaan', 'occupation'],
        'status_perkawinan' => ['status_perkawinan', 'status_kawin', 'perkawinan'],
        'kewarganegaraan' => ['kewarganegaraan', 'warga_negara', 'wni_wna'],
        'no_paspor' => ['no_paspor', 'nomor_paspor', 'paspor'],
        'no_kitas_kitap' => ['no_kitas_kitap', 'nomor_kitas_kitap', 'kitas_kitap', 'kitas', 'kitap'],
        'nama_ayah' => ['nama_ayah', 'ayah'],
        'nama_ibu' => ['nama_ibu', 'ibu'],
        'golongan_darah' => ['golongan_darah', 'gol_darah', 'goldar'],
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
