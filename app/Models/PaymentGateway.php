<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'class',
        'data',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if the gateway is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get all invoices for this gateway
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scope to only get active gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
