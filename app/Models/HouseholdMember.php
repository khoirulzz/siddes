<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class HouseholdMember extends Model
{
    protected $fillable = [
        'household_id',
        'resident_id',
        'status_hubungan',
        'no_urut_kk',
        'is_kepala_keluarga',
        'is_current',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'is_kepala_keluarga' => 'boolean',
            'is_current' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(PopulationRecord::class, 'resident_id');
    }
}
