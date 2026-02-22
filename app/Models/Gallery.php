<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $fillable = [
        'title',
        'image_url',
        'description',
        'activity_date',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
        ];
    }

    public function getImageUrlAttribute(?string $value): ?string
    {
        return PublicMedia::toUrl($value);
    }
}
