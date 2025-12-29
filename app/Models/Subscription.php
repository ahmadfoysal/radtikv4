<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'billing_cycle',
        'cycle_count',
        'amount',
        'original_price',
        'discount_percent',
        'status',
        'last_payment_date',
        'next_billing_date',
        'last_invoice_id',
        'auto_renew',
        'cancelled_at',
        'cancellation_reason',
        'promo_code',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_payment_date' => 'date',
        'next_billing_date' => 'date',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'amount' => 'decimal:2',
        'original_price' => 'decimal:2',
        'cycle_count' => 'integer',
        'discount_percent' => 'integer',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function lastInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'last_invoice_id');
    }

    // Status Checks

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInGracePeriod(): bool
    {
        return $this->status === 'grace_period';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    // Business Logic

    public function hasEnded(): bool
    {
        return Carbon::parse($this->end_date)->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return Carbon::parse($this->end_date)->diffInDays(now(), false);
    }

    public function canAutoRenew(): bool
    {
        return $this->auto_renew &&
            $this->isActive() &&
            !$this->isCancelled();
    }

    public function renew(): self
    {
        $newEndDate = $this->billing_cycle === 'yearly'
            ? $this->end_date->addYear()
            : $this->end_date->addMonth();

        $this->update([
            'end_date' => $newEndDate,
            'next_billing_date' => $newEndDate,
            'cycle_count' => $this->cycle_count + 1,
            'last_payment_date' => now(),
            'status' => 'active',
        ]);

        return $this->fresh();
    }

    public function suspend(string $reason = 'Payment failed'): void
    {
        $this->update([
            'status' => 'suspended',
            'cancellation_reason' => $reason,
        ]);
    }

    public function cancel(string $reason = 'User requested'): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeDueForRenewal($query)
    {
        return $query->where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('next_billing_date', '<=', now()->addDays(3));
    }
}
