<?php

namespace App\Services;

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\PopulationRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PopulationHouseholdSyncService
{
    public function sync(PopulationRecord $resident, array $payload, ?Carbon $startedAt = null): Household
    {
        return DB::transaction(function () use ($resident, $payload, $startedAt): Household {
            $noKk = $this->digits($payload['no_kk'] ?? $resident->no_kk ?? $resident->nkk ?? null);
            if (! $noKk) {
                throw new \InvalidArgumentException('Nomor KK wajib diisi untuk sinkronisasi data keluarga.');
            }

            $household = Household::query()->firstOrNew(['no_kk' => $noKk]);
            $household->fill([
                'nama_kepala_keluarga' => $payload['nama_kepala_keluarga'] ?? $household->nama_kepala_keluarga,
                'alamat' => $payload['alamat'] ?? $payload['address_detail'] ?? $household->alamat,
                'rt' => $this->sanitizeCode($payload['rt'] ?? $household->rt, 3),
                'rw' => $this->sanitizeCode($payload['rw'] ?? $household->rw, 3),
                'kode_pos' => $this->digits($payload['kode_pos'] ?? $household->kode_pos ?? PopulationRecord::DEFAULT_POSTAL_CODE),
                'dusun' => $payload['dusun'] ?? $payload['hamlet'] ?? $household->dusun,
                'desa' => $payload['desa'] ?? $household->desa ?? PopulationRecord::DEFAULT_VILLAGE,
                'kecamatan' => $payload['kecamatan'] ?? $household->kecamatan ?? PopulationRecord::DEFAULT_DISTRICT,
                'kabupaten' => $payload['kabupaten'] ?? $household->kabupaten ?? PopulationRecord::DEFAULT_REGENCY,
                'provinsi' => $payload['provinsi'] ?? $household->provinsi ?? PopulationRecord::DEFAULT_PROVINCE,
            ]);
            $household->save();

            $statusHubungan = $this->normalizeStatusHubungan(
                $payload['status_hubungan'] ?? $resident->status_hubungan ?? 'Kepala Keluarga'
            );
            $isKepala = $statusHubungan === 'Kepala Keluarga';

            $currentMembership = HouseholdMember::query()
                ->where('resident_id', $resident->id)
                ->where('is_current', true)
                ->first();

            if ($currentMembership && (int) $currentMembership->household_id !== (int) $household->id) {
                $currentMembership->forceFill([
                    'is_current' => false,
                    'ended_at' => $startedAt ?: now(),
                ])->save();

                $currentMembership = null;
            }

            if (! $currentMembership) {
                $currentMembership = HouseholdMember::query()->firstOrNew([
                    'resident_id' => $resident->id,
                    'household_id' => $household->id,
                    'is_current' => true,
                ]);
            }

            $currentMembership->status_hubungan = $statusHubungan;
            $currentMembership->is_kepala_keluarga = $isKepala;
            $currentMembership->no_urut_kk = $this->sanitizeNumber($payload['no_urut_kk'] ?? null);
            if (! $currentMembership->exists) {
                $currentMembership->started_at = $startedAt ?: now();
            } elseif (! $currentMembership->started_at) {
                $currentMembership->started_at = $startedAt ?: now();
            }
            $currentMembership->ended_at = null;
            $currentMembership->save();

            if ($isKepala) {
                HouseholdMember::query()
                    ->where('household_id', $household->id)
                    ->where('is_current', true)
                    ->where('id', '!=', $currentMembership->id)
                    ->update(['is_kepala_keluarga' => false]);

                $household->nama_kepala_keluarga = $resident->resolvedName();
                $household->save();
            } elseif (! $household->nama_kepala_keluarga) {
                $household->nama_kepala_keluarga = $payload['nama_kepala_keluarga'] ?? $resident->resolvedName();
                $household->save();
            }

            $resident->forceFill([
                'no_kk' => $household->no_kk,
                'nkk' => $household->no_kk,
                'status_hubungan' => $statusHubungan,
                'rt' => $household->rt,
                'rw' => $household->rw,
                'dusun' => $household->dusun,
                'hamlet' => $household->dusun,
                'desa' => $household->desa,
                'kecamatan' => $household->kecamatan,
                'kabupaten' => $household->kabupaten,
                'provinsi' => $household->provinsi,
                'kode_pos' => $household->kode_pos,
                'address_detail' => $household->alamat ?: ($payload['alamat'] ?? $payload['address_detail'] ?? $resident->address_detail),
            ])->save();

            return $household;
        });
    }

    public function cleanupEmptyHouseholds(array $householdIds): void
    {
        if ($householdIds === []) {
            return;
        }

        Household::query()
            ->whereIn('id', array_values(array_unique($householdIds)))
            ->get()
            ->each(function (Household $household): void {
                if (! $household->members()->exists()) {
                    $household->delete();
                }
            });
    }

    private function sanitizeCode(mixed $value, int $pad): ?string
    {
        $digits = $this->digits($value);
        if ($digits === null) {
            return null;
        }

        return str_pad($digits, $pad, '0', STR_PAD_LEFT);
    }

    private function sanitizeNumber(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = preg_replace('/\D+/', '', (string) $value);
        return $numeric === '' ? null : (int) $numeric;
    }

    private function digits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/\D+/', '', (string) $value) ?: '';
        return $clean !== '' ? $clean : null;
    }

    private function normalizeStatusHubungan(?string $status): string
    {
        if (! $status) {
            return 'Kepala Keluarga';
        }

        $status = trim($status);
        $normalized = Str::lower($status);
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
        ];

        return $map[$normalized] ?? $status;
    }
}
