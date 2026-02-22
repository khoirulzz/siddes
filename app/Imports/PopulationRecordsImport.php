<?php

namespace App\Imports;

use App\Models\PopulationRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PopulationRecordsImport implements ToCollection, WithHeadingRow
{
    private array $seenNik = [];

    public int $inserted = 0;

    public int $skipped = 0;

    public int $duplicates = 0;

    public function __construct(
        private readonly ?string $hamletOverride = null,
        private readonly ?string $sourceFile = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        $candidateNik = [];
        foreach ($rows as $row) {
            $nik = $this->digits($this->value($row, ['nik']));
            if ($nik) {
                $candidateNik[$nik] = true;
            }
        }

        if ($candidateNik !== []) {
            $existingNik = PopulationRecord::query()
                ->whereIn('nik', array_keys($candidateNik))
                ->pluck('nik')
                ->all();

            foreach ($existingNik as $nik) {
                $this->seenNik[(string) $nik] = true;
            }
        }

        foreach ($rows as $row) {
            $nik = $this->digits($this->value($row, ['nik']));
            $fullName = $this->value($row, ['nama_lengkap', 'full_name', 'nama']);
            $nkk = $this->digits($this->value($row, ['no_kk', 'nkk']));
            $gender = $this->normalizeGender($this->value($row, ['jenis_kelamin', 'gender']));
            $religion = $this->value($row, ['agama', 'religion']) ?: '-';
            $occupation = $this->value($row, ['pekerjaan', 'occupation']) ?: '-';
            $hamlet = $this->hamletOverride ?: $this->normalizeHamlet($this->value($row, ['dusun', 'hamlet', 'alamat_dusun']));
            $rt = $this->digits($this->value($row, ['rt'])) ?: null;
            $rw = $this->digits($this->value($row, ['rw'])) ?: null;
            $village = $this->value($row, ['desa']) ?: PopulationRecord::DEFAULT_VILLAGE;
            $district = $this->value($row, ['kecamatan']) ?: PopulationRecord::DEFAULT_DISTRICT;
            $regency = $this->value($row, ['kabupaten']) ?: PopulationRecord::DEFAULT_REGENCY;
            $province = $this->value($row, ['provinsi']) ?: PopulationRecord::DEFAULT_PROVINCE;
            $postalCode = $this->digits($this->value($row, ['kode_pos'])) ?: PopulationRecord::DEFAULT_POSTAL_CODE;

            [$birthPlace, $birthDate] = $this->resolveBirthData($row);

            if (! $nik || ! $fullName || ! $nkk || ! $gender || ! $hamlet || ! $birthPlace || ! $birthDate) {
                $this->skipped++;
                continue;
            }

            if (isset($this->seenNik[$nik])) {
                $this->duplicates++;
                continue;
            }

            PopulationRecord::create([
                'nama_lengkap' => $fullName,
                'full_name' => $fullName,
                'nik' => $nik,
                'no_kk' => $nkk,
                'nkk' => $nkk,
                'tempat_lahir' => $birthPlace,
                'birth_place' => $birthPlace,
                'tanggal_lahir' => $birthDate,
                'birth_date' => $birthDate,
                'jenis_kelamin' => $gender,
                'gender' => $gender,
                'dusun' => $hamlet,
                'hamlet' => $hamlet,
                'rt' => $rt,
                'rw' => $rw,
                'agama' => $religion,
                'religion' => $religion ?: '-',
                'pekerjaan' => $occupation,
                'occupation' => $occupation ?: '-',
                'pendidikan' => $this->value($row, ['pendidikan']),
                'status_perkawinan' => $this->value($row, ['status_perkawinan']),
                'kewarganegaraan' => $this->value($row, ['kewarganegaraan']) ?: 'WNI',
                'desa' => $village,
                'kecamatan' => $district,
                'kabupaten' => $regency,
                'provinsi' => $province,
                'kode_pos' => $postalCode,
                'address_detail' => $this->value($row, ['alamat_lengkap', 'alamat', 'address_detail']),
                'source_file' => $this->sourceFile,
            ]);

            $this->seenNik[$nik] = true;
            $this->inserted++;
        }
    }

    private function value(Collection $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row->get($key);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function digits(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $clean = preg_replace('/\D+/', '', $value) ?: '';
        return $clean !== '' ? $clean : null;
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

        foreach (PopulationRecord::HAMLETS as $officialHamlet) {
            if (Str::lower($officialHamlet) === Str::lower(trim($hamlet))) {
                return $officialHamlet;
            }
        }

        return trim($hamlet);
    }

    private function resolveBirthData(Collection $row): array
    {
        $birthPlace = $this->value($row, ['tempat_lahir', 'birth_place']);
        $birthDate = $this->parseDate($this->value($row, ['tanggal_lahir', 'birth_date']));

        if ($birthPlace && $birthDate) {
            return [$birthPlace, $birthDate];
        }

        $ttl = $this->value($row, ['ttl']);
        if (! $ttl) {
            return [$birthPlace, $birthDate];
        }

        $parts = array_map('trim', explode(',', $ttl, 2));
        if (! $birthPlace && isset($parts[0])) {
            $birthPlace = $parts[0];
        }

        if (! $birthDate && isset($parts[1])) {
            $birthDate = $this->parseDate($parts[1]);
        }

        return [$birthPlace, $birthDate];
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

    public function summary(): array
    {
        return [
            'inserted' => $this->inserted,
            'duplicates' => $this->duplicates,
            'skipped' => $this->skipped,
        ];
    }
}
