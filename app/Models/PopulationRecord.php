<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'kewarganegaraan',
        'religion',
        'occupation',
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
        return (string) ($this->no_kk ?: $this->nkk ?: '-');
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
        return (string) ($this->pekerjaan ?: $this->occupation ?: '-');
    }

    public function resolvedHamlet(): string
    {
        return (string) ($this->dusun ?: $this->hamlet ?: '-');
    }

    public function resolvedRt(): string
    {
        return (string) ($this->rt ?: '-');
    }

    public function resolvedRw(): string
    {
        return (string) ($this->rw ?: '-');
    }

    public function resolvedVillage(): string
    {
        return (string) ($this->desa ?: self::DEFAULT_VILLAGE);
    }

    public function resolvedDistrict(): string
    {
        return (string) ($this->kecamatan ?: self::DEFAULT_DISTRICT);
    }

    public function resolvedRegency(): string
    {
        return (string) ($this->kabupaten ?: self::DEFAULT_REGENCY);
    }

    public function resolvedProvince(): string
    {
        return (string) ($this->provinsi ?: self::DEFAULT_PROVINCE);
    }

    public function resolvedPostalCode(): string
    {
        return (string) ($this->kode_pos ?: self::DEFAULT_POSTAL_CODE);
    }
}
