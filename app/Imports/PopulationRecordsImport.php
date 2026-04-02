<?php

namespace App\Imports;

use App\Models\PopulationRecord;
use App\Services\PopulationHouseholdSyncService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PopulationRecordsImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings, WithChunkReading
{
    public int $inserted = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public function __construct(
        private readonly PopulationHouseholdSyncService $householdSync,
        private readonly ?string $hamletOverride = null,
        private readonly ?string $sourceFile = null,
        private readonly string $csvDelimiter = ',',
    ) {
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            foreach ($rows as $index => $row) {
                $payload = $this->mapRow($row);

                if (! $this->isValidRow($payload)) {
                    $this->skipped++;
                    continue;
                }

                $resident = PopulationRecord::query()->firstOrNew([
                    'nik' => $payload['nik'],
                ]);

                $isNewResident = ! $resident->exists;

                $resident->fill([
                    'nama_lengkap' => $payload['nama_lengkap'],
                    'full_name' => $payload['nama_lengkap'],
                    'nik' => $payload['nik'],
                    'no_kk' => $payload['no_kk'],
                    'nkk' => $payload['no_kk'],
                    'jenis_kelamin' => $payload['jenis_kelamin'],
                    'gender' => $payload['jenis_kelamin'],
                    'tempat_lahir' => $payload['tempat_lahir'],
                    'birth_place' => $payload['tempat_lahir'],
                    'tanggal_lahir' => $payload['tanggal_lahir'],
                    'birth_date' => $payload['tanggal_lahir'],
                    'agama' => $payload['agama'],
                    'religion' => $payload['agama'],
                    'pendidikan' => $payload['pendidikan'],
                    'jenis_pekerjaan' => $payload['jenis_pekerjaan'],
                    'pekerjaan' => $payload['jenis_pekerjaan'],
                    'occupation' => $payload['jenis_pekerjaan'],
                    'status_perkawinan' => $payload['status_perkawinan'],
                    'status_hubungan' => $payload['status_hubungan'],
                    'kewarganegaraan' => $payload['kewarganegaraan'],
                    'no_paspor' => $payload['no_paspor'],
                    'no_kitas_kitap' => $payload['no_kitas_kitap'],
                    'nama_ayah' => $payload['nama_ayah'],
                    'nama_ibu' => $payload['nama_ibu'],
                    'golongan_darah' => $payload['golongan_darah'],
                    'rt' => $payload['rt'],
                    'rw' => $payload['rw'],
                    'dusun' => $payload['dusun'],
                    'hamlet' => $payload['dusun'],
                    'desa' => $payload['desa'],
                    'kecamatan' => $payload['kecamatan'],
                    'kabupaten' => $payload['kabupaten'],
                    'provinsi' => $payload['provinsi'],
                    'kode_pos' => $payload['kode_pos'],
                    'address_detail' => $payload['alamat'],
                    'source_file' => $this->sourceFile ?: ('import-row-' . ($index + 1)),
                ]);

                $resident->save();
                $this->householdSync->sync($resident, $payload, Carbon::now());

                if ($isNewResident) {
                    $this->inserted++;
                } else {
                    $this->updated++;
                }
            }
        });
    }

    private function mapRow(Collection $row): array
    {
        $noKk = $this->digits($this->pick($row, [
            'no_kk',
            'nkk',
            'nomor_kk',
            'nomor_kartu_keluarga',
            'kk',
        ]));

        $dusunRaw = $this->pick($row, ['dusun', 'hamlet', 'alamat_dusun', 'dukuh']);
        $dusun = $this->hamletOverride ?: $this->normalizeHamlet($dusunRaw) ?: PopulationRecord::HAMLETS[0];
        $statusHubungan = $this->normalizeStatusHubungan($this->pick($row, [
            'status_hubungan',
            'status_hubungan_dalam_keluarga',
            'status_hubungan_keluarga',
            'hubungan_keluarga',
        ]));

        return [
            'nik' => $this->digits($this->pick($row, ['nik', 'nomor_induk_kependudukan'])),
            'no_kk' => $noKk,
            'nama_lengkap' => $this->pick($row, [
                'nama_lengkap',
                'full_name',
                'nama',
                'nama_anggota_keluarga',
            ]),
            'nama_kepala_keluarga' => $this->pick($row, [
                'nama_kepala_keluarga',
                'kepala_keluarga',
                'nama_kepala_kk',
            ]),
            'alamat' => $this->pick($row, ['alamat', 'alamat_lengkap', 'address', 'address_detail']),
            'rt' => $this->sanitizeCode($this->pick($row, ['rt']), 3),
            'rw' => $this->sanitizeCode($this->pick($row, ['rw']), 3),
            'kode_pos' => $this->digits($this->pick($row, ['kode_pos', 'postal_code'])) ?: PopulationRecord::DEFAULT_POSTAL_CODE,
            'dusun' => $dusun,
            'desa' => $this->pick($row, ['desa', 'kelurahan']) ?: PopulationRecord::DEFAULT_VILLAGE,
            'kecamatan' => $this->pick($row, ['kecamatan']) ?: PopulationRecord::DEFAULT_DISTRICT,
            'kabupaten' => $this->pick($row, ['kabupaten', 'kabupaten_kota', 'kota']) ?: PopulationRecord::DEFAULT_REGENCY,
            'provinsi' => $this->pick($row, ['provinsi']) ?: PopulationRecord::DEFAULT_PROVINCE,
            'no_urut_kk' => $this->sanitizeInt($this->pick($row, ['no_urut_kk', 'no', 'urutan'])),
            'status_hubungan' => $statusHubungan ?: 'Kepala Keluarga',
            'jenis_kelamin' => $this->normalizeGender($this->pick($row, ['jenis_kelamin', 'gender', 'jk'])),
            'tempat_lahir' => $this->resolveBirthPlace($row),
            'tanggal_lahir' => $this->resolveBirthDate($row),
            'agama' => $this->pick($row, ['agama', 'religion']) ?: '-',
            'pendidikan' => $this->pick($row, ['pendidikan', 'education']),
            'jenis_pekerjaan' => $this->pick($row, ['jenis_pekerjaan', 'pekerjaan', 'occupation']) ?: '-',
            'status_perkawinan' => $this->normalizeStatusPerkawinan($this->pick($row, [
                'status_perkawinan',
                'status_kawin',
                'perkawinan',
            ])),
            'kewarganegaraan' => $this->normalizeKewarganegaraan($this->pick($row, [
                'kewarganegaraan',
                'warga_negara',
            ])),
            'no_paspor' => $this->pick($row, ['no_paspor', 'paspor']),
            'no_kitas_kitap' => $this->pick($row, ['no_kitas_kitap', 'kitas', 'kitap']),
            'nama_ayah' => $this->pick($row, ['nama_ayah', 'ayah']),
            'nama_ibu' => $this->pick($row, ['nama_ibu', 'ibu']),
            'golongan_darah' => $this->normalizeGolonganDarah($this->pick($row, ['golongan_darah', 'gol_darah'])),
        ];
    }

    private function isValidRow(array $payload): bool
    {
        if (! $payload['nik'] || ! $payload['no_kk'] || ! $payload['nama_lengkap']) {
            return false;
        }
        if (strlen((string) $payload['nik']) !== 16 || strlen((string) $payload['no_kk']) !== 16) {
            return false;
        }
        if (! $payload['jenis_kelamin'] || ! $payload['tempat_lahir'] || ! $payload['tanggal_lahir']) {
            return false;
        }
        if ($payload['tanggal_lahir'] > Carbon::now()->format('Y-m-d')) {
            return false;
        }
        if (($payload['kewarganegaraan'] === 'WNA') && ! $payload['no_paspor'] && ! $payload['no_kitas_kitap']) {
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

    private function resolveBirthPlace(Collection $row): ?string
    {
        $birthPlace = $this->pick($row, ['tempat_lahir', 'birth_place']);
        if ($birthPlace) {
            return $birthPlace;
        }

        $ttl = $this->pick($row, ['ttl']);
        if (! $ttl) {
            return null;
        }

        $parts = array_map('trim', explode(',', $ttl, 2));
        return $parts[0] ?? null;
    }

    private function resolveBirthDate(Collection $row): ?string
    {
        $birthDate = $this->parseDate($this->pick($row, ['tanggal_lahir', 'birth_date']));
        if ($birthDate) {
            return $birthDate;
        }

        $ttl = $this->pick($row, ['ttl']);
        if (! $ttl) {
            return null;
        }

        $parts = array_map('trim', explode(',', $ttl, 2));
        return isset($parts[1]) ? $this->parseDate($parts[1]) : null;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
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
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function digits(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $clean = preg_replace('/\D+/', '', $value) ?: '';
        return $clean !== '' ? $clean : null;
    }

    private function sanitizeCode(?string $value, int $pad): ?string
    {
        $digits = $this->digits($value);
        return $digits ? str_pad($digits, $pad, '0', STR_PAD_LEFT) : null;
    }

    private function sanitizeInt(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        $numeric = preg_replace('/\D+/', '', $value) ?: '';
        return $numeric !== '' ? (int) $numeric : null;
    }

    private function normalizeGender(?string $gender): ?string
    {
        if (! $gender) {
            return null;
        }

        $value = Str::lower($gender);
        if (in_array($value, ['l', 'lk', 'laki laki', 'laki-laki', 'pria', 'male'], true)) {
            return 'Laki-laki';
        }
        if (in_array($value, ['p', 'pr', 'perempuan', 'wanita', 'female'], true)) {
            return 'Perempuan';
        }

        return null;
    }

    private function normalizeHamlet(?string $hamlet): ?string
    {
        if (! $hamlet) {
            return null;
        }

        $value = trim($hamlet);
        foreach (PopulationRecord::HAMLETS as $officialHamlet) {
            if (Str::lower($officialHamlet) === Str::lower($value)) {
                return $officialHamlet;
            }
        }

        return $value;
    }

    private function normalizeStatusPerkawinan(?string $status): string
    {
        if (! $status) {
            return 'Belum Kawin';
        }

        $value = Str::lower(trim($status));
        $map = [
            'belum kawin' => 'Belum Kawin',
            'kawin' => 'Kawin Tercatat',
            'kawin tercatat' => 'Kawin Tercatat',
            'kawin belum tercatat' => 'Kawin Belum Tercatat',
            'cerai hidup' => 'Cerai Hidup',
            'cerai mati' => 'Cerai Mati',
        ];

        return $map[$value] ?? 'Belum Kawin';
    }

    private function normalizeStatusHubungan(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        $value = Str::lower(trim($status));
        $map = [
            'kepala keluarga' => 'Kepala Keluarga',
            'kepala_keluarga' => 'Kepala Keluarga',
            'istri' => 'Istri',
            'suami' => 'Suami',
            'anak' => 'Anak',
            'cucu' => 'Cucu',
            'orang tua' => 'Orang Tua',
            'orang_tua' => 'Orang Tua',
            'mertua' => 'Mertua',
            'famili lain' => 'Famili Lain',
            'famili_lain' => 'Famili Lain',
            'pembantu' => 'Pembantu',
            'lainnya' => 'Lainnya',
            'lain-lain' => 'Lainnya',
        ];

        return $map[$value] ?? $status;
    }

    private function normalizeKewarganegaraan(?string $value): string
    {
        if (! $value) {
            return 'WNI';
        }

        $normalized = Str::upper(trim($value));
        return $normalized === 'WNA' ? 'WNA' : 'WNI';
    }

    private function normalizeGolonganDarah(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $normalized = Str::upper(trim($value));
        return in_array($normalized, PopulationRecord::GOLONGAN_DARAH_OPTIONS, true) ? $normalized : null;
    }

    public function summary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->csvDelimiter,
            'enclosure' => '"',
            'escape_character' => '\\',
            'input_encoding' => 'UTF-8',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
