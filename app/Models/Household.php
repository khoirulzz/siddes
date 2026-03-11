<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    protected $fillable = [
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
    ];

    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    public function currentMembers(): HasMany
    {
        return $this->hasMany(HouseholdMember::class)->where('is_current', true);
    }
}
