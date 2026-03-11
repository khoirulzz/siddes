<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class VillageStaff extends Model
{
    protected $table = 'village_staff';

    protected $fillable = [
        'name',
        'position',
        'photo_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->photo_path);
    }
}

