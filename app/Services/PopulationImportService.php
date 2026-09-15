<?php

namespace App\Services;

use App\Exceptions\PopulationImportException;
use App\Models\Household;
use App\Models\PopulationRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PopulationImportService
{
    private const HOUSEHOLD_FIELDS = [
        'nama_kepala_keluarga', 'alamat', 'rt', 'rw', 'kode_pos',
        'dusun', 'desa', 'kecamatan', 'kabupaten', 'provinsi',
    ];

    public function __construct(
        private readonly PopulationImportParser $parser,
        private readonly PopulationHouseholdSyncService $householdSync,
    ) {
    }

    /** @return array<string, mixed> */
    public function preview(UploadedFile $file, ?string $hamletOverride = null): array
    {
        PopulationImportMemory::begin();
        $parsed = $this->parser->parse($file);
        $rows = array_map(
            fn (array $row): array => $this->normalizeRow($row, $hamletOverride),
            $parsed['rows'],
        );
        unset($parsed['rows']);
        PopulationImportMemory::check();

        $niks = collect($rows)->pluck('values.nik')->filter()->unique()->values();
        $noKks = collect($rows)->pluck('values.no_kk')->filter()->unique()->values();

        $residents = $this->loadResidents($niks, withMembership: true);
        $households = $this->loadHouseholds($noKks, withMembers: true);

        foreach ($rows as &$row) {
            PopulationImportMemory::check();
            $resident = $row['values']['nik'] ? $residents->get($row['values']['nik']) : null;
            $household = $row['values']['no_kk'] ? $households->get($row['values']['no_kk']) : null;
            $this->validateAndHydrateRow($row, $resident, $household, $file->getClientOriginalName());
        }
        unset($row);

        $this->validateCrossRowRules($rows, $households);

        foreach ($rows as &$row) {
            $row['status'] = $this->hasErrors($row) ? 'invalid' : $row['action'];
        }
        unset($row);

        $summary = $this->buildSummary($rows, $households);
        unset($residents, $households);
        PopulationImportMemory::check();
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

    /** @return array<string, int> */
    public function commit(array $preview, string $sourceFile): array
    {
        if (($preview['summary']['valid'] ?? 0) < 1) {
            throw new PopulationImportException('Tidak ada baris valid yang dapat diimpor.');
        }

        return DB::transaction(function () use ($preview, $sourceFile): array {
            $householdValues = $this->canonicalHouseholdValues($preview['rows']);
            $validRows = collect($preview['rows'])->reject(fn (array $row): bool => ($row['status'] ?? 'invalid') === 'invalid');
            $residents = $this->loadResidents($validRows->pluck('values.nik')->filter()->unique()->values());
            $households = $this->loadHouseholds($validRows->pluck('values.no_kk')->filter()->unique()->values());
            $result = [
                'households_created' => (int) ($preview['summary']['households_created'] ?? 0),
                'residents_created' => 0,
                'residents_updated' => 0,
                'residents_unchanged' => 0,
                'residents_moved' => 0,
                'skipped' => (int) ($preview['summary']['invalid'] ?? 0),
                'warnings' => (int) ($preview['summary']['warnings'] ?? 0),
            ];

            foreach ($preview['rows'] as $row) {
                if (($row['status'] ?? 'invalid') === 'invalid') {
                    continue;
                }

                $action = $row['action'];
                if ($action === 'unchanged') {
                    $result['residents_unchanged']++;
                    continue;
                }

                $resident = $residents->get($row['values']['nik']) ?: new PopulationRecord();
                $resident->fill($row['resident_data']);
                $resident->source_file = $sourceFile;
                $resident->save();
                $residents->put($resident->nik, $resident);

                $syncPayload = array_merge(
                    $row['sync_payload'],
                    $householdValues[$row['values']['no_kk']] ?? [],
                );
                if (array_key_exists('alamat', $syncPayload)) {
                    $syncPayload['address_detail'] = $syncPayload['alamat'];
                }
                $household = $this->householdSync->sync(
                    $resident,
                    $syncPayload,
                    now(),
                    $households->get($row['values']['no_kk']),
                );
                $households->put($household->no_kk, $household);

                match ($action) {
                    'new' => $result['residents_created']++,
                    'move' => $result['residents_moved']++,
                    default => $result['residents_updated']++,
                };
            }

            return $result;
        }, 3);
    }

    /** @return array<string, mixed> */
    public function publicPreview(array $preview): array
    {
        return [
            'sheet' => $preview['sheet'],
            'header_row' => $preview['header_row'],
            'summary' => $preview['summary'],
            'rows' => array_map(static fn (array $row): array => [
                'row' => $row['row'],
                'nik' => $row['values']['nik'],
                'no_kk' => $row['values']['no_kk'],
                'nama_lengkap' => $row['values']['nama_lengkap'],
                'status' => $row['status'],
                'action' => $row['action'],
                'issues' => $row['issues'],
            ], $preview['rows']),
        ];
    }

    private function normalizeRow(array $rawRow, ?string $hamletOverride): array
    {
        $issues = [];
        $cells = $rawRow['_cells'];
        $optionalFields = ['pendidikan', 'nama_ayah', 'nama_ibu', 'golongan_darah', 'kode_pos', 'no_paspor', 'no_kitas_kitap', 'alamat'];
        $text = function (string $field) use ($cells, $optionalFields, &$issues): ?string {
            $value = $this->textValue($cells[$field]['value'] ?? null);
            if ($value !== null && in_array($field, $optionalFields, true)
                && in_array(Str::lower($value), ['-', '--', '—', '–', 'n/a', 'na', 'null', 'tidak tahu', 'tidak diketahui', 'belum diketahui', 'belum diisi', 'tidak ada'], true)) {
                $issues[] = $this->issue('warning', $field, 'optional_ignored', 'Isian belum diketahui dianggap kosong. Data lama, jika ada, tetap dipertahankan.');
                return null;
            }
            return $value;
        };

        $nik = $this->identifierValue('nik', $cells['nik'] ?? null, $issues);
        $noKk = $this->identifierValue('no_kk', $cells['no_kk'] ?? null, $issues);
        $gender = $this->enumValue('jenis_kelamin', $text('jenis_kelamin'), [
            'l' => 'Laki-laki', 'lk' => 'Laki-laki', 'laki laki' => 'Laki-laki', 'laki-laki' => 'Laki-laki',
            'pria' => 'Laki-laki', 'male' => 'Laki-laki', 'p' => 'Perempuan', 'pr' => 'Perempuan',
            'perempuan' => 'Perempuan', 'wanita' => 'Perempuan', 'female' => 'Perempuan',
        ], $issues);
        $relationship = $this->enumValue('status_hubungan', $text('status_hubungan'), [
            'kepala keluarga' => 'Kepala Keluarga', 'kepala_keluarga' => 'Kepala Keluarga',
            'istri' => 'Istri', 'suami' => 'Suami', 'anak' => 'Anak', 'cucu' => 'Cucu',
            'orang tua' => 'Orang Tua', 'orang_tua' => 'Orang Tua', 'mertua' => 'Mertua',
            'famili lain' => 'Famili Lain', 'famili_lain' => 'Famili Lain', 'pembantu' => 'Pembantu',
            'lainnya' => 'Lainnya', 'lain-lain' => 'Lainnya',
        ], $issues);
        $maritalStatus = $this->enumValue('status_perkawinan', $text('status_perkawinan'), [
            'belum kawin' => 'Belum Kawin', 'kawin' => 'Kawin Tercatat', 'kawin tercatat' => 'Kawin Tercatat',
            'kawin belum tercatat' => 'Kawin Belum Tercatat', 'cerai hidup' => 'Cerai Hidup', 'cerai mati' => 'Cerai Mati',
        ], $issues);
        $citizenship = $this->enumValue('kewarganegaraan', $text('kewarganegaraan'), [
            'wni' => 'WNI', 'wna' => 'WNA',
        ], $issues);
        $bloodType = $this->enumValue('golongan_darah', $text('golongan_darah'), [
            'a' => 'A', 'b' => 'B', 'ab' => 'AB', 'o' => 'O',
        ], $issues);

        $hamlet = $hamletOverride ?: $this->enumValue(
            'dusun',
            $text('dusun'),
            collect(PopulationRecord::HAMLETS)->mapWithKeys(fn (string $value): array => [Str::lower($value) => $value])->all(),
            $issues,
        );

        $values = [
            'no_kk' => $noKk,
            'nama_kepala_keluarga' => $text('nama_kepala_keluarga'),
            'alamat' => $text('alamat'),
            'rt' => $this->codeValue('rt', $text('rt'), 3, $issues),
            'rw' => $this->codeValue('rw', $text('rw'), 3, $issues),
            'kode_pos' => $this->codeValue('kode_pos', $text('kode_pos'), 0, $issues, 4, 10),
            'dusun' => $hamlet,
            'desa' => $text('desa'),
            'kecamatan' => $text('kecamatan'),
            'kabupaten' => $text('kabupaten'),
            'provinsi' => $text('provinsi'),
            'no_urut_kk' => $this->integerValue('no_urut_kk', $text('no_urut_kk'), $issues),
            'status_hubungan' => $relationship,
            'nik' => $nik,
            'nama_lengkap' => $text('nama_lengkap'),
            'jenis_kelamin' => $gender,
            'tempat_lahir' => $text('tempat_lahir'),
            'tanggal_lahir' => $this->dateValue($cells['tanggal_lahir'] ?? null, $issues),
            'agama' => $text('agama'),
            'pendidikan' => $text('pendidikan'),
            'jenis_pekerjaan' => $text('jenis_pekerjaan'),
            'status_perkawinan' => $maritalStatus,
            'kewarganegaraan' => $citizenship,
            'no_paspor' => $text('no_paspor'),
            'no_kitas_kitap' => $text('no_kitas_kitap'),
            'nama_ayah' => $text('nama_ayah'),
            'nama_ibu' => $text('nama_ibu'),
            'golongan_darah' => $bloodType,
        ];

        return [
            'row' => (int) $rawRow['_row'],
            'values' => $values,
            'issues' => $issues,
            'action' => 'new',
            'status' => 'new',
        ];
    }

    private function validateAndHydrateRow(array &$row, ?PopulationRecord $resident, ?Household $household, string $sourceFile): void
    {
        $values = $row['values'];
        $providedValues = $values;
        $isNew = $resident === null;

        foreach (['nik' => 'NIK', 'no_kk' => 'Nomor KK'] as $field => $label) {
            if (! $values[$field]) {
                $this->addIssue($row, 'error', $field, strtolower($field).'_required', $label.' wajib diisi.');
            } elseif (strlen($values[$field]) !== 16) {
                $this->addIssue($row, 'error', $field, strtolower($field).'_length', $label.' harus tepat 16 digit.');
            }
        }

        $requiredForNew = [
            'nama_lengkap' => 'Nama lengkap', 'jenis_kelamin' => 'Jenis kelamin',
            'tempat_lahir' => 'Tempat lahir', 'tanggal_lahir' => 'Tanggal lahir',
            'dusun' => 'Dusun', 'status_hubungan' => 'Status hubungan', 'agama' => 'Agama',
            'jenis_pekerjaan' => 'Jenis pekerjaan', 'status_perkawinan' => 'Status perkawinan',
            'kewarganegaraan' => 'Kewarganegaraan',
        ];
        foreach ($requiredForNew as $field => $label) {
            if ($isNew && ! $values[$field]) {
                $this->addIssue($row, 'error', $field, $field.'_required', $label.' wajib diisi untuk penduduk baru.');
            }
        }

        if ($values['tanggal_lahir'] && $values['tanggal_lahir'] > now()->toDateString()) {
            $this->addIssue($row, 'error', 'tanggal_lahir', 'future_date', 'Tanggal lahir tidak boleh melebihi hari ini.');
        }
        if ($values['no_urut_kk'] !== null && ($values['no_urut_kk'] < 1 || $values['no_urut_kk'] > 999)) {
            $this->addIssue($row, 'error', 'no_urut_kk', 'invalid_order', 'Nomor urut KK harus antara 1 sampai 999.');
        }
        foreach ([
            'nama_lengkap' => 255, 'nama_kepala_keluarga' => 255, 'alamat' => 2000,
            'dusun' => 120, 'desa' => 120, 'kecamatan' => 120, 'kabupaten' => 120, 'provinsi' => 120,
            'tempat_lahir' => 255, 'agama' => 255, 'pendidikan' => 255, 'jenis_pekerjaan' => 255,
            'no_paspor' => 80, 'no_kitas_kitap' => 80, 'nama_ayah' => 255, 'nama_ibu' => 255,
        ] as $field => $limit) {
            if ($values[$field] !== null && mb_strlen($values[$field]) > $limit) {
                if (in_array($field, ['pendidikan', 'nama_ayah', 'nama_ibu', 'alamat'], true)) {
                    $this->addIssue($row, 'warning', $field, 'optional_ignored', "Isian melebihi {$limit} karakter dan tidak digunakan. Data lama tetap dipertahankan.");
                    $values[$field] = null;
                    $providedValues[$field] = null;
                    continue;
                }
                $this->addIssue($row, 'error', $field, 'too_long', "Nilai {$field} melebihi {$limit} karakter.");
            }
        }

        $currentMembership = $resident?->currentMembership;
        $currentHousehold = $currentMembership?->household;

        $merged = $values;
        if ($resident) {
            $fallbacks = [
                'nama_lengkap' => $resident->resolvedName(), 'jenis_kelamin' => $resident->resolvedGender(),
                'tempat_lahir' => $resident->resolvedBirthPlace(), 'tanggal_lahir' => $resident->resolvedBirthDate()?->toDateString(),
                'agama' => $resident->resolvedReligion(), 'pendidikan' => $resident->pendidikan,
                'jenis_pekerjaan' => $resident->resolvedOccupation(), 'status_perkawinan' => $resident->status_perkawinan,
                'kewarganegaraan' => $resident->kewarganegaraan, 'no_paspor' => $resident->no_paspor,
                'no_kitas_kitap' => $resident->no_kitas_kitap, 'nama_ayah' => $resident->nama_ayah,
                'nama_ibu' => $resident->nama_ibu, 'golongan_darah' => $resident->golongan_darah,
                'status_hubungan' => $currentMembership?->status_hubungan ?: $resident->status_hubungan,
                'no_urut_kk' => $currentMembership?->no_urut_kk,
            ];
            foreach ($fallbacks as $field => $fallback) {
                if ($merged[$field] === null || $merged[$field] === '') {
                    $merged[$field] = $fallback;
                }
            }
        }

        $targetHousehold = $household ?: ($currentHousehold?->no_kk === $values['no_kk'] ? $currentHousehold : null);
        $householdFallbacks = [
            'nama_kepala_keluarga' => $targetHousehold?->nama_kepala_keluarga,
            'alamat' => $targetHousehold?->alamat,
            'rt' => $targetHousehold?->rt,
            'rw' => $targetHousehold?->rw,
            'kode_pos' => $targetHousehold?->kode_pos ?: PopulationRecord::DEFAULT_POSTAL_CODE,
            'dusun' => $targetHousehold?->dusun,
            'desa' => $targetHousehold?->desa ?: PopulationRecord::DEFAULT_VILLAGE,
            'kecamatan' => $targetHousehold?->kecamatan ?: PopulationRecord::DEFAULT_DISTRICT,
            'kabupaten' => $targetHousehold?->kabupaten ?: PopulationRecord::DEFAULT_REGENCY,
            'provinsi' => $targetHousehold?->provinsi ?: PopulationRecord::DEFAULT_PROVINCE,
        ];
        foreach ($householdFallbacks as $field => $fallback) {
            if ($merged[$field] === null || $merged[$field] === '') {
                $merged[$field] = $fallback;
            }
        }

        if (! $targetHousehold && ! $merged['dusun']) {
            $this->addIssue($row, 'error', 'dusun', 'dusun_required', 'Dusun wajib diisi untuk KK baru atau pilih override dusun saat import.');
        }
        if ($merged['kewarganegaraan'] === 'WNA' && ! $merged['no_paspor'] && ! $merged['no_kitas_kitap']) {
            $this->addIssue($row, 'error', 'no_paspor', 'wna_document_required', 'WNA wajib memiliki nomor paspor atau KITAS/KITAP.');
        }

        if (! $merged['nama_kepala_keluarga'] && $merged['status_hubungan'] === 'Kepala Keluarga') {
            $merged['nama_kepala_keluarga'] = $merged['nama_lengkap'];
        }

        $residentData = [
            'nama_lengkap' => $merged['nama_lengkap'], 'full_name' => $merged['nama_lengkap'],
            'nik' => $merged['nik'], 'no_kk' => $merged['no_kk'], 'nkk' => $merged['no_kk'],
            'jenis_kelamin' => $merged['jenis_kelamin'], 'gender' => $merged['jenis_kelamin'],
            'tempat_lahir' => $merged['tempat_lahir'], 'birth_place' => $merged['tempat_lahir'],
            'tanggal_lahir' => $merged['tanggal_lahir'], 'birth_date' => $merged['tanggal_lahir'],
            'agama' => $merged['agama'], 'religion' => $merged['agama'],
            'pendidikan' => $merged['pendidikan'], 'jenis_pekerjaan' => $merged['jenis_pekerjaan'],
            'pekerjaan' => $merged['jenis_pekerjaan'], 'occupation' => $merged['jenis_pekerjaan'],
            'status_perkawinan' => $merged['status_perkawinan'], 'status_hubungan' => $merged['status_hubungan'],
            'kewarganegaraan' => $merged['kewarganegaraan'], 'no_paspor' => $merged['no_paspor'],
            'no_kitas_kitap' => $merged['no_kitas_kitap'], 'nama_ayah' => $merged['nama_ayah'],
            'nama_ibu' => $merged['nama_ibu'], 'golongan_darah' => $merged['golongan_darah'],
            'rt' => $merged['rt'], 'rw' => $merged['rw'], 'dusun' => $merged['dusun'], 'hamlet' => $merged['dusun'],
            'desa' => $merged['desa'], 'kecamatan' => $merged['kecamatan'], 'kabupaten' => $merged['kabupaten'],
            'provinsi' => $merged['provinsi'], 'kode_pos' => $merged['kode_pos'], 'address_detail' => $merged['alamat'],
        ];

        $row['values'] = $merged;
        $row['provided_values'] = $providedValues;
        $row['resident_data'] = $residentData;
        $row['sync_payload'] = array_merge($merged, ['address_detail' => $merged['alamat'], 'source_file' => $sourceFile]);
        $row['snapshot'] = [
            'resident' => $resident?->updated_at?->format('Y-m-d H:i:s.u'),
            'membership' => $currentMembership?->updated_at?->format('Y-m-d H:i:s.u'),
            'household' => $targetHousehold?->updated_at?->format('Y-m-d H:i:s.u'),
        ];

        if ($resident === null) {
            $row['action'] = 'new';
            return;
        }

        if ($currentHousehold && $currentHousehold->no_kk !== $merged['no_kk']) {
            $row['action'] = 'move';
            $this->addIssue($row, 'warning', 'no_kk', 'household_move', "Penduduk akan dipindahkan dari KK {$currentHousehold->no_kk} ke KK {$merged['no_kk']}.");
            return;
        }

        $residentChanged = $this->modelDiffers($resident, $residentData);
        $householdChanged = $targetHousehold ? $this->modelDiffers($targetHousehold, array_intersect_key($merged, array_flip(self::HOUSEHOLD_FIELDS))) : true;
        $membershipChanged = ! $currentMembership
            || $currentMembership->status_hubungan !== $merged['status_hubungan']
            || (int) ($currentMembership->no_urut_kk ?? 0) !== (int) ($merged['no_urut_kk'] ?? 0);

        $row['action'] = ($residentChanged || $householdChanged || $membershipChanged) ? 'update' : 'unchanged';
    }

    private function validateCrossRowRules(array &$rows, $households): void
    {
        $byNik = [];
        $byHousehold = [];
        foreach ($rows as $index => $row) {
            if ($row['values']['nik']) {
                $byNik[$row['values']['nik']][] = $index;
            }
            if ($row['values']['no_kk']) {
                $byHousehold[$row['values']['no_kk']][] = $index;
            }
        }

        foreach ($byNik as $nik => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            foreach ($indexes as $index) {
                $this->addIssue($rows[$index], 'error', 'nik', 'duplicate_nik', "NIK {$nik} muncul lebih dari sekali di dalam file.");
            }
        }

        foreach ($byHousehold as $noKk => $indexes) {
            $heads = [];
            $orders = [];
            $importedNiks = [];
            foreach ($indexes as $index) {
                $row = $rows[$index];
                if ($row['values']['nik']) {
                    $importedNiks[] = $row['values']['nik'];
                }
                if ($row['values']['status_hubungan'] === 'Kepala Keluarga') {
                    $heads[] = $index;
                }
                if ($row['values']['no_urut_kk'] !== null) {
                    $orders[$row['values']['no_urut_kk']][] = $index;
                }
            }

            if (count($heads) > 1) {
                foreach ($heads as $index) {
                    $this->addIssue($rows[$index], 'error', 'status_hubungan', 'multiple_heads', "KK {$noKk} memiliki lebih dari satu kepala keluarga di dalam file.");
                }
            }

            foreach ($orders as $order => $orderIndexes) {
                if (count($orderIndexes) > 1) {
                    foreach ($orderIndexes as $index) {
                        $this->addIssue($rows[$index], 'error', 'no_urut_kk', 'duplicate_order', "Nomor urut {$order} digunakan lebih dari sekali pada KK {$noKk}.");
                    }
                }
            }

            $existingHousehold = $households->get($noKk);
            if ($existingHousehold) {
                $existingHeads = $existingHousehold->currentMembers
                    ->filter(fn ($member): bool => (bool) $member->is_kepala_keluarga)
                    ->reject(fn ($member): bool => in_array($member->resident?->nik, $importedNiks, true));
                if ($existingHeads->isNotEmpty() && count($heads) > 0) {
                    foreach ($heads as $index) {
                        $this->addIssue(
                            $rows[$index],
                            'error',
                            'status_hubungan',
                            'existing_head_conflict',
                            "KK {$noKk} sudah memiliki kepala keluarga aktif yang tidak tercantum dalam file.",
                        );
                    }
                }

                $occupiedOrders = $existingHousehold->currentMembers
                    ->reject(fn ($member): bool => in_array($member->resident?->nik, $importedNiks, true))
                    ->filter(fn ($member): bool => $member->no_urut_kk !== null)
                    ->keyBy('no_urut_kk');

                foreach ($orders as $order => $orderIndexes) {
                    if (! $occupiedOrders->has($order)) {
                        continue;
                    }
                    foreach ($orderIndexes as $index) {
                        $this->addIssue($rows[$index], 'error', 'no_urut_kk', 'occupied_order', "Nomor urut {$order} sudah dipakai anggota lain pada KK {$noKk}.");
                    }
                }
            }

            foreach (self::HOUSEHOLD_FIELDS as $field) {
                $groups = [];
                foreach ($indexes as $index) {
                    $value = $rows[$index]['provided_values'][$field] ?? null;
                    if ($value !== null && $value !== '') {
                        $groups[(string) $value][] = $index;
                    }
                }
                if (count($groups) < 2) {
                    continue;
                }
                foreach ($indexes as $index) {
                    $this->addIssue($rows[$index], 'error', $field, 'household_conflict', "Kolom {$field} tidak konsisten antaranggota KK {$noKk}.");
                }
            }

            if (! $households->has($noKk)) {
                $validHeads = array_filter(
                    $heads,
                    fn (int $index): bool => ! $this->hasErrors($rows[$index]),
                );
                if (count($validHeads) !== 1) {
                    foreach ($indexes as $index) {
                        $message = count($heads) === 1
                            ? 'Baris kepala keluarga (baris '.$rows[$heads[0]]['row'].') belum lolos pemeriksaan. Perbaiki kesalahan pada baris tersebut agar anggota KK dapat diimpor.'
                            : 'KK baru harus memiliki tepat satu anggota dengan hubungan Kepala Keluarga.';
                        $this->addIssue($rows[$index], 'error', 'status_hubungan', 'head_required', $message);
                    }
                }
            }
        }
    }

    private function buildSummary(array $rows, $households): array
    {
        $validRows = array_filter($rows, fn (array $row): bool => ! $this->hasErrors($row));
        $newHouseholds = collect($validRows)
            ->pluck('values.no_kk')
            ->filter(fn ($noKk): bool => ! $households->has($noKk))
            ->unique()
            ->count();

        $countAction = static fn (string $action): int => count(array_filter(
            $validRows,
            static fn (array $row): bool => $row['action'] === $action,
        ));

        return [
            'total' => count($rows),
            'valid' => count($validRows),
            'invalid' => count($rows) - count($validRows),
            'warnings' => array_sum(array_map(static fn (array $row): int => count(array_filter(
                $row['issues'],
                static fn (array $issue): bool => $issue['severity'] === 'warning',
            )), $rows)),
            'households_created' => $newHouseholds,
            'residents_created' => $countAction('new'),
            'residents_updated' => $countAction('update'),
            'residents_unchanged' => $countAction('unchanged'),
            'residents_moved' => $countAction('move'),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function canonicalHouseholdValues(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            if (($row['status'] ?? 'invalid') === 'invalid' || empty($row['values']['no_kk'])) {
                continue;
            }
            $grouped[$row['values']['no_kk']][] = $row;
        }

        $result = [];
        foreach ($grouped as $noKk => $householdRows) {
            foreach (self::HOUSEHOLD_FIELDS as $field) {
                $provided = collect($householdRows)
                    ->pluck("provided_values.{$field}")
                    ->first(fn ($value): bool => $value !== null && $value !== '');
                $fallback = collect($householdRows)
                    ->pluck("values.{$field}")
                    ->first(fn ($value): bool => $value !== null && $value !== '');
                $result[$noKk][$field] = $provided ?? $fallback;
            }
        }

        return $result;
    }

    private function loadResidents(Collection $niks, bool $withMembership = false): Collection
    {
        $residents = collect();
        foreach ($niks->chunk(500) as $chunk) {
            PopulationImportMemory::check();
            $query = PopulationRecord::query();
            if ($withMembership) {
                $query->with(['currentMembership.household']);
            }
            $query->whereIn('nik', $chunk)->get()->each(
                fn (PopulationRecord $resident) => $residents->put($resident->nik, $resident),
            );
        }

        return $residents;
    }

    private function loadHouseholds(Collection $noKks, bool $withMembers = false): Collection
    {
        $households = collect();
        foreach ($noKks->chunk(500) as $chunk) {
            PopulationImportMemory::check();
            $query = Household::query();
            if ($withMembers) {
                $query->with(['currentMembers.resident']);
            }
            $query->whereIn('no_kk', $chunk)->get()->each(
                fn (Household $household) => $households->put($household->no_kk, $household),
            );
        }

        return $households;
    }

    private function identifierValue(string $field, ?array $cell, array &$issues): ?string
    {
        $value = $cell['value'] ?? null;
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (($cell['type'] ?? null) === DataType::TYPE_NUMERIC || ($cell['type'] ?? null) === DataType::TYPE_FORMULA) {
            $issues[] = $this->issue('error', $field, 'numeric_identifier', ucfirst(str_replace('_', ' ', $field)).' harus diformat sebagai Text di Excel agar 16 digit tidak dibulatkan.');
            return null;
        }

        $text = ltrim(trim((string) $value), "'");
        if (preg_match('/^[+-]?[0-9]+(?:[.,][0-9]+)?e[+-]?[0-9]+$/i', $text)) {
            $issues[] = $this->issue('error', $field, 'scientific_identifier', ucfirst(str_replace('_', ' ', $field)).' terbaca sebagai notasi ilmiah. Ubah kolom menjadi Text dan isi ulang dari sumber asli.');
            return null;
        }

        $digits = preg_replace('/[\s.\-]+/', '', $text) ?? $text;
        if (! ctype_digit($digits)) {
            $issues[] = $this->issue('error', $field, 'invalid_identifier', ucfirst(str_replace('_', ' ', $field)).' hanya boleh berisi angka.');
            return null;
        }

        return $digits;
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

        $issues[] = $this->issue('error', 'tanggal_lahir', 'invalid_date', 'Tanggal lahir harus berupa tanggal Excel atau format dd-mm-yyyy, dd/mm/yyyy, atau yyyy-mm-dd.');
        return null;
    }

    private function enumValue(string $field, ?string $value, array $map, array &$issues): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = Str::lower(trim($value));
        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        if ($field === 'golongan_darah') {
            $issues[] = $this->issue('warning', $field, 'optional_ignored', 'Golongan darah tidak dikenali dan tidak digunakan. Data lama tetap dipertahankan.');
            return null;
        }
        $issues[] = $this->issue('error', $field, 'unknown_value', "Nilai '{$value}' tidak dikenali pada kolom {$field}.");
        return null;
    }

    private function codeValue(string $field, ?string $value, int $pad, array &$issues, int $min = 1, int $max = 3): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = trim($value);
        if ($digits === '' || strlen($digits) < $min || strlen($digits) > $max) {
            $issues[] = $this->issue($field === 'kode_pos' ? 'warning' : 'error', $field, $field === 'kode_pos' ? 'optional_ignored' : 'invalid_code', "Kolom {$field} harus berisi {$min}-{$max} digit. Isian tidak digunakan.");
            return null;
        }
        if (! ctype_digit($digits)) {
            $issues[] = $this->issue($field === 'kode_pos' ? 'warning' : 'error', $field, $field === 'kode_pos' ? 'optional_ignored' : 'invalid_code', 'Isian harus berupa digit angka dan tidak digunakan.');
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
            'optional_ignored' => 'Baris tetap dapat diimpor. Lengkapi data ini nanti melalui formulir edit bila sudah diketahui.',
            'numeric_identifier', 'scientific_identifier' => 'Ubah kolom NIK/No. KK menjadi Text, lalu isi ulang dari sumber asli sebelum menyimpan file.',
            'duplicate_nik' => 'Sisakan satu baris untuk setiap NIK.',
            'duplicate_order', 'occupied_order' => 'Gunakan nomor urut lain yang belum dipakai pada KK tersebut.',
            'multiple_heads', 'existing_head_conflict' => 'Pastikan hanya ada satu kepala keluarga aktif untuk setiap KK.',
            'head_required' => 'Perbaiki atau tambahkan satu baris Kepala Keluarga yang seluruh datanya valid.',
            'household_conflict' => 'Samakan nilai data KK pada seluruh baris anggota keluarga tersebut.',
            'invalid_date' => 'Gunakan tanggal Excel asli atau salah satu format tanggal yang disebutkan.',
            'future_date' => 'Isi tanggal lahir hari ini atau tanggal yang lebih lama.',
            'unknown_value' => 'Gunakan salah satu pilihan yang tersedia pada template Excel.',
            'wna_document_required' => 'Isi minimal nomor paspor atau nomor KITAS/KITAP.',
            default => str_ends_with($code, '_required') ? 'Lengkapi kolom ini lalu jalankan pratinjau ulang.' : 'Perbaiki nilai pada kolom ini lalu jalankan pratinjau ulang.',
        };

        $fieldLabel = match ($field) {
            'nik' => 'NIK', 'no_kk' => 'Nomor KK', 'no_urut_kk' => 'Nomor urut anggota',
            'status_hubungan' => 'Hubungan keluarga', 'no_kitas_kitap' => 'Nomor KITAS/KITAP',
            'rt' => 'RT', 'rw' => 'RW', 'jenis_pekerjaan' => 'Pekerjaan',
            default => Str::ucfirst(str_replace('_', ' ', $field)),
        };
        return [...compact('severity', 'field', 'code', 'message', 'hint'), 'field_label' => $fieldLabel];
    }
}
