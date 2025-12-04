<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'price_monthly',
        'price_yearly',
        'user_limit',
        'billing_cycle',
        'early_pay_days',
        'early_pay_discount_percent',
        'auto_renew_allowed',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'user_limit' => 'integer',
            'early_pay_days' => 'integer',
            'early_pay_discount_percent' => 'integer',
            'auto_renew_allowed' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
