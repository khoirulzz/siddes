<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'link_url',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $today = Carbon::today();
        $query
            ->where('is_active', true)
            ->where(function (Builder $inner) use ($today) {
                $inner->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function (Builder $inner) use ($today) {
                $inner->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
    }

    public function getThumbnailUrlAttribute(): string
    {
        return (string) (
            config('village.announcement_thumbnail_icon')
            ?: config('village.announcement_icon_url')
            ?: config('village.logo_url')
        );
    }
}
