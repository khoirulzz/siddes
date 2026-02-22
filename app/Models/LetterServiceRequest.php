<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterServiceRequest extends Model
{
    use HasFactory;

    protected $table = 'letter_service_requests';


    protected $fillable = [
        'ticket_number',
        'official_number',
        'applicant_name',
        'nik',
        'kk_number',
        'phone',
        'address',
        'letter_type',
        'letter_code',
        'letter_sequence',
        'purpose',
        'dynamic_data',
        'email',
        'attachment_path',
        'attachment_url',
        'status',
        'admin_notes',
        'requested_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'submitted_at' => 'datetime',
            'dynamic_data' => 'array',
        ];
    }
}
