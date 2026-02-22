<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ComplaintReport extends Model
{
    protected $fillable = [
        'ticket_code',
        'nik',
        'reporter_name',
        'phone',
        'email',
        'subject',
        'category',
        'description',
        'location',
        'evidence_path',
        'status',
        'response',
        'handled_by',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        $path = $this->resolvedEvidencePath();
        if (! $path) {
            return null;
        }

        return PublicMedia::toUrl($path);
    }

    public function resolvedEvidencePath(): ?string
    {
        $path = trim((string) $this->evidence_path);
        if ($path === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (Str::startsWith($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $path = trim($path);
        return $path !== '' ? $path : null;
    }
}
