<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'max_routers',
        'max_users',
        'max_zones',
        'max_vouchers_per_router',
        'grace_period_days',
        'early_pay_days',
        'early_pay_discount_percent',
        'auto_renew_allowed',
        'features',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_routers' => 'integer',
            'max_users' => 'integer',
            'max_zones' => 'integer',
            'max_vouchers_per_router' => 'integer',
            'grace_period_days' => 'integer',
            'early_pay_days' => 'integer',
            'early_pay_discount_percent' => 'integer',
            'auto_renew_allowed' => 'boolean',
            'features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscribers(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    // Helper Methods

    public function calculatePrice(string $cycle = 'monthly'): float
    {
        return $cycle === 'yearly' ? ($this->price_yearly ?? $this->price_monthly * 12) : $this->price_monthly;
    }

    public function getFeature(string $key, $default = false)
    {
        $features = $this->features ?? [];
        return $features[$key] ?? $default;
    }

    public function getYearlySavings(): float
    {
        if (!$this->price_yearly) {
            return 0;
        }
        return ($this->price_monthly * 12) - $this->price_yearly;
    }
}
