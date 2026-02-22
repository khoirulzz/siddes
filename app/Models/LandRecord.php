<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class LandRecord extends Model
{
    protected $fillable = [
        'land_code',
        'location',
        'hamlet',
        'category',
        'area_m2',
        'ownership_status',
        'owner_name',
        'certificate_number',
        'tax_object_number',
        'status',
        'photo_path',
        'document_path',
        'description',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->photo_path);
    }

    public function getDocumentUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->document_path);
    }
}
