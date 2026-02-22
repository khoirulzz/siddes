<?php

namespace App\Models;

use App\Support\PublicMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PbbPaymentRequest extends Model
{
    protected $fillable = [
        'ticket_code',
        'applicant_name',
        'nik',
        'nop',
        'requested_nops',
        'tax_year',
        'amount_due',
        'phone',
        'email',
        'payment_method',
        'proof_path',
        'notes',
        'status',
        'admin_notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'amount_due' => 'decimal:2',
            'requested_nops' => 'array',
        ];
    }

    public function getProofUrlAttribute(): ?string
    {
        return PublicMedia::toUrl($this->proof_path);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function requestedNopsList(): array
    {
        $raw = $this->requested_nops;
        if (! is_array($raw) || empty($raw)) {
            if (! $this->nop) {
                return [];
            }

            return [[
                'nop' => (string) $this->nop,
                'tax_year' => $this->tax_year ? (int) $this->tax_year : null,
                'amount_due' => $this->amount_due !== null ? (float) $this->amount_due : 0,
                'tax_name' => null,
                'address' => null,
            ]];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $nop = trim((string) ($item['nop'] ?? ''));
            if ($nop === '') {
                return null;
            }

            return [
                'nop' => $nop,
                'tax_year' => isset($item['tax_year']) ? (int) $item['tax_year'] : null,
                'amount_due' => isset($item['amount_due']) ? (float) $item['amount_due'] : 0,
                'tax_name' => isset($item['tax_name']) ? (string) $item['tax_name'] : null,
                'address' => isset($item['address']) ? (string) $item['address'] : null,
            ];
        }, $raw)));
    }

    public function totalNops(): int
    {
        return count($this->requestedNopsList());
    }

    public function waLink(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->phone) ?: '';
        if ($phone === '') {
            return null;
        }

        if (Str::startsWith($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return 'https://wa.me/' . $phone;
    }
}
