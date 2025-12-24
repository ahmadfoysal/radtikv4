<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'database_enabled',
        'router_offline',
        'voucher_expiring',
        'low_balance',
        'payment_received',
        'subscription_renewal',
        'invoice_generated',
        'low_balance_threshold',
        'voucher_expiry_days',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'router_offline' => 'boolean',
        'voucher_expiring' => 'boolean',
        'low_balance' => 'boolean',
        'payment_received' => 'boolean',
        'subscription_renewal' => 'boolean',
        'invoice_generated' => 'boolean',
        'low_balance_threshold' => 'decimal:2',
        'voucher_expiry_days' => 'integer',
    ];

    /**
     * Get the user that owns the notification preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create default notification preferences for a user.
     */
    public static function createDefault(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'email_enabled' => true,
            'database_enabled' => true,
            'router_offline' => true,
            'voucher_expiring' => true,
            'low_balance' => true,
            'payment_received' => true,
            'subscription_renewal' => true,
            'invoice_generated' => true,
            'low_balance_threshold' => 100,
            'voucher_expiry_days' => 7,
        ]);
    }
}
