<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopulationImportRun extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'file_hash',
        'format',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'warning_count',
        'households_created',
        'residents_created',
        'residents_updated',
        'residents_unchanged',
        'residents_moved',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
