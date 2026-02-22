<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class VillageActivity extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'activity_date',
        'location',
        'person_in_charge',
        'status',
        'budget',
        'summary',
        'description',
        'cover_image_path',
        'document_path',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->cover_image_path);
    }

    public function getDocumentUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->document_path);
    }
}
