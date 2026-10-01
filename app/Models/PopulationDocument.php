<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopulationDocument extends Model
{
    public const TYPES = [
        'surat_kematian' => 'Surat Kematian',
        'akta_kematian' => 'Akta Kematian',
        'surat_pindah' => 'Surat Pindah',
    ];

    protected $fillable = [
        'jenis', 'status_keberadaan', 'original_name', 'size',
        'cloudinary_asset_id', 'cloudinary_public_id',
    ];

    protected $hidden = ['cloudinary_asset_id', 'cloudinary_public_id'];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(PopulationRecord::class, 'population_record_id');
    }
}
