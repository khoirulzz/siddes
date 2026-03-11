<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PbbTaxObject extends Model
{
    protected $fillable = [
        'nop',
        'nama_wp_sppt',
        'jalan_wp_sppt',
        'rt_wp_sppt',
        'rw_wp_sppt',
        'desa_wp_sppt',
        'jalan_op_sppt',
        'rt_op_sppt',
        'rw_op_sppt',
        'luas_tanah_sppt',
        'luas_bangunan_sppt',
        'pbb_terhutang',
        'tanggal_pembayaran',
        'tax_name',
        'owner_name',
        'location',
        'land_area',
        'building_area',
        'tax_address',
        'tax_year',
        'amount_due',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tax_year' => 'integer',
            'land_area' => 'decimal:2',
            'building_area' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'luas_tanah_sppt' => 'decimal:2',
            'luas_bangunan_sppt' => 'decimal:2',
            'pbb_terhutang' => 'decimal:2',
            'tanggal_pembayaran' => 'date',
        ];
    }

    public function resolvedTaxName(): string
    {
        return (string) ($this->nama_wp_sppt ?: $this->tax_name ?: '-');
    }

    public function resolvedAddress(): string
    {
        $opAddress = trim(implode(' ', array_filter([
            $this->jalan_op_sppt,
            $this->rt_op_sppt ? 'RT ' . $this->rt_op_sppt : null,
            $this->rw_op_sppt ? 'RW ' . $this->rw_op_sppt : null,
        ])));

        $wpAddress = trim(implode(' ', array_filter([
            $this->jalan_wp_sppt,
            $this->rt_wp_sppt ? 'RT ' . $this->rt_wp_sppt : null,
            $this->rw_wp_sppt ? 'RW ' . $this->rw_wp_sppt : null,
            $this->desa_wp_sppt,
        ])));

        return (string) ($opAddress !== '' ? $opAddress : ($wpAddress !== '' ? $wpAddress : ($this->location ?: $this->tax_address ?: '-')));
    }

    public function resolvedOwner(): ?string
    {
        return $this->nama_wp_sppt ?: $this->owner_name ?: null;
    }

    public function resolvedTaxYear(): int
    {
        return (int) ($this->tax_year ?: date('Y'));
    }

    public function resolvedAmountDue(): float
    {
        return (float) ($this->pbb_terhutang ?? $this->amount_due ?? 0);
    }

    public function resolvedLandArea(): float
    {
        return (float) ($this->luas_tanah_sppt ?? $this->land_area ?? 0);
    }

    public function resolvedBuildingArea(): float
    {
        return (float) ($this->luas_bangunan_sppt ?? $this->building_area ?? 0);
    }

    public function resolvedPaidAt(): ?Carbon
    {
        return $this->tanggal_pembayaran instanceof Carbon ? $this->tanggal_pembayaran : null;
    }
}
