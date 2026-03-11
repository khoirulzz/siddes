<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PopulationRecord extends Model
{
    public const HAMLETS = [
        'Bojongireng',
        'Panumbangan',
        'Mandelun',
        'Sasak',
        'Simendem',
    ];

    public const DEFAULT_VILLAGE = 'Desa Lambanggelun';
    public const DEFAULT_DISTRICT = 'Kecamatan Paninggaran';
    public const DEFAULT_REGENCY = 'Kabupaten Pekalongan';
    public const DEFAULT_PROVINCE = 'Provinsi Jawa Tengah';
    public const DEFAULT_POSTAL_CODE = '51164';

    public const STATUS_PERKAWINAN_OPTIONS = [
        'Belum Kawin',
        'Kawin Tercatat',
        'Kawin Belum Tercatat',
        'Cerai Hidup',
        'Cerai Mati',
    ];

    public const STATUS_HUBUNGAN_OPTIONS = [
        'Kepala Keluarga',
        'Istri',
        'Suami',
        'Anak',
        'Cucu',
        'Orang Tua',
        'Mertua',
        'Famili Lain',
        'Pembantu',
        'Lainnya',
    ];

    public const GOLONGAN_DARAH_OPTIONS = [
        'A',
        'B',
        'AB',
        'O',
    ];

    protected $fillable = [
        'nama_lengkap',
        'full_name',
        'nik',
        'no_kk',
        'nkk',
        'jenis_kelamin',
        'birth_place',
        'tempat_lahir',
        'birth_date',
        'tanggal_lahir',
        'gender',
        'agama',
        'pekerjaan',
        'hamlet',
        'dusun',
        'rt',
        'rw',
        'desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'pendidikan',
        'status_perkawinan',
        'status_hubungan',
        'kewarganegaraan',
        'no_paspor',
        'no_kitas_kitap',
        'nama_ayah',
        'nama_ibu',
        'golongan_darah',
        'religion',
        'occupation',
        'jenis_pekerjaan',
        'address_detail',
        'source_file',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tanggal_lahir' => 'date',
        ];
    }

    public function scopeInHamlet(Builder $query, ?string $hamlet): void
    {
        if ($hamlet && $hamlet !== 'Semua') {
            $query->where(function (Builder $builder) use ($hamlet): void {
                $builder->where('dusun', $hamlet)->orWhere('hamlet', $hamlet);
            });
        }
    }

    public function householdMemberships(): HasMany
    {
        return $this->hasMany(HouseholdMember::class, 'resident_id');
    }

    public function currentMembership(): HasOne
    {
        return $this->hasOne(HouseholdMember::class, 'resident_id')
            ->where('is_current', true)
            ->latestOfMany('started_at');
    }

    public function getAgeAttribute(): ?int
    {
        $birthDate = $this->resolvedBirthDate();
        if (! $birthDate) {
            return null;
        }

        return $birthDate->age;
    }

    public function resolvedName(): string
    {
        return (string) ($this->nama_lengkap ?: $this->full_name ?: '-');
    }

    public function resolvedKkNumber(): string
    {
        return (string) ($this->no_kk ?: $this->nkk ?: $this->resolvedHouseholdField('no_kk') ?: '-');
    }

    public function resolvedBirthPlace(): string
    {
        return (string) ($this->tempat_lahir ?: $this->birth_place ?: '-');
    }

    public function resolvedBirthDate(): ?Carbon
    {
        if ($this->tanggal_lahir instanceof Carbon) {
            return $this->tanggal_lahir;
        }
        if ($this->birth_date instanceof Carbon) {
            return $this->birth_date;
        }

        $candidate = $this->tanggal_lahir ?: $this->birth_date;
        if (! $candidate) {
            return null;
        }

        try {
            return Carbon::parse($candidate);
        } catch (\Throwable) {
            return null;
        }
    }

    public function resolvedGender(): string
    {
        return (string) ($this->jenis_kelamin ?: $this->gender ?: '-');
    }

    public function resolvedReligion(): string
    {
        return (string) ($this->agama ?: $this->religion ?: '-');
    }

    public function resolvedOccupation(): string
    {
        return (string) ($this->jenis_pekerjaan ?: $this->pekerjaan ?: $this->occupation ?: '-');
    }

    public function resolvedStatusHubungan(): string
    {
        $fromMembership = $this->relationLoaded('currentMembership')
            ? $this->currentMembership?->status_hubungan
            : null;

        return (string) ($this->status_hubungan ?: $fromMembership ?: '-');
    }

    public function resolvedHamlet(): string
    {
        return (string) ($this->dusun ?: $this->hamlet ?: $this->resolvedHouseholdField('dusun') ?: '-');
    }

    public function resolvedRt(): string
    {
        return (string) ($this->rt ?: $this->resolvedHouseholdField('rt') ?: '-');
    }

    public function resolvedRw(): string
    {
        return (string) ($this->rw ?: $this->resolvedHouseholdField('rw') ?: '-');
    }

    public function resolvedVillage(): string
    {
        return (string) ($this->desa ?: $this->resolvedHouseholdField('desa') ?: self::DEFAULT_VILLAGE);
    }

    public function resolvedDistrict(): string
    {
        return (string) ($this->kecamatan ?: $this->resolvedHouseholdField('kecamatan') ?: self::DEFAULT_DISTRICT);
    }

    public function resolvedRegency(): string
    {
        return (string) ($this->kabupaten ?: $this->resolvedHouseholdField('kabupaten') ?: self::DEFAULT_REGENCY);
    }

    public function resolvedProvince(): string
    {
        return (string) ($this->provinsi ?: $this->resolvedHouseholdField('provinsi') ?: self::DEFAULT_PROVINCE);
    }

    public function resolvedPostalCode(): string
    {
        return (string) ($this->kode_pos ?: $this->resolvedHouseholdField('kode_pos') ?: self::DEFAULT_POSTAL_CODE);
    }

    private function resolvedHouseholdField(string $field): ?string
    {
        if ($this->relationLoaded('currentMembership')) {
            return $this->currentMembership?->household?->{$field};
        }

        return $this->currentMembership()->with('household')->first()?->household?->{$field};
    }
}
