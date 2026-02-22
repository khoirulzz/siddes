<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterNumberCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'letter_code',
        'last_number',
    ];
}
