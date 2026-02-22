<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'provider',
        'primary_model',
        'fallback_model',
        'used_model',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }
}

