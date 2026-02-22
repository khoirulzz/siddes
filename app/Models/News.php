<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'thumbnail_path',
        'content',
        'author_name',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->whereNotNull('published_at');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->thumbnail_path);
    }
}
