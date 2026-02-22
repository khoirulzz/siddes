<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbbTaxObject extends Model
{
    protected $fillable = [
        'nop',
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

    public function resolvedAddress(): string
    {
        return (string) ($this->location ?: $this->tax_address ?: '-');
    }

    public function resolvedOwner(): ?string
    {
        return $this->owner_name ?: null;
    }
}
