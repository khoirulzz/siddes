<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandTransaction extends Model
{
    use HasFactory;

    public const TYPE_LABELS = [
        'jual_beli' => 'Jual Beli',
        'waris' => 'Waris',
        'hibah' => 'Hibah',
        'tukar_guling' => 'Tukar Guling',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'transaction_type',
        'party_a_name',
        'party_a_identifier',
        'party_a_address',
        'party_a_page',
        'party_b_name',
        'party_b_identifier',
        'party_b_address',
        'party_b_page',
        'land_object',
        'area_m2',
        'document_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'area_m2' => 'decimal:2',
        ];
    }

    public function files(): HasMany
    {
        return $this->hasMany(LandTransactionFile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->transaction_type] ?? ucfirst(str_replace('_', ' ', $this->transaction_type));
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return self::TYPE_LABELS;
    }
}
