<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasBilling;
use App\Models\Traits\LogsActivity;
use HasinHayder\TyroLogin\Traits\HasTwoFactorAuth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasBilling, HasFactory, HasRoles, HasTwoFactorAuth, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'profile_image',
        'country',
        'balance',
        'commission',
        'admin_id',
        'is_active',
        'last_login_at',
        'is_phone_verified',
        'expiration_date',
        'email_notifications',
        'login_alerts',
        'preferred_language',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_phone_verified' => 'boolean',
            'expiration_date' => 'date',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'commission' => 'decimal:2',
            'email_notifications' => 'boolean',
            'login_alerts' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            // Auto-subscribe admin users to free package after registration
            if ($user->hasRole('admin')) {
                $freePackage = Package::where('name', 'Free')->where('is_active', true)->first();

                if ($freePackage) {
                    try {
                        $user->subscribeToPackage($freePackage, 'monthly');
                    } catch (\Exception $e) {
                        // Log error but don't fail registration
                        \Log::error('Failed to auto-subscribe user to free package: ' . $e->getMessage());
                    }
                }
            }
        });
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // Role helpers
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isReseller(): bool
    {
        return $this->hasRole('reseller');
    }

    //add a method to check if user has role reseller and has specific permission
    public function resellerHasPermission(string $permission): bool
    {
        return $this->isReseller() && $this->hasPermissionTo($permission);
    }

    /**
     * Check if the user is suspended
     */
    public function isSuspended(): bool
    {
        return !is_null($this->suspended_at);
    }

    /**
     * Suspend the user with a reason
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Unsuspend the user
     */
    public function unsuspend(): void
    {
        $this->update([
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    // Reseller relation
    public function reseller()
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    // Admins relation
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Vouchers relation
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    // Router relation

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function routerAssignments()
    {
        return $this->hasMany(ResellerRouter::class, 'reseller_id');
    }

    public function resellerRouters()
    {
        return $this->belongsToMany(Router::class, 'reseller_router', 'reseller_id', 'router_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function getResellerRouters()
    {
        return $this->resellerRouters()->get();
    }

    public function profileAssignments()
    {
        return $this->hasMany(ResellerProfile::class, 'reseller_id');
    }

    public function resellerProfiles()
    {
        return $this->belongsToMany(UserProfile::class, 'reseller_profile', 'reseller_id', 'profile_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function notificationPreferences()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function getResellerProfiles()
    {
        return $this->resellerProfiles()->get();
    }

    /**
     * Get an authorized router by ID based on user role.
     * For admins: returns router from their routers() relation.
     * For resellers: returns router from their resellerRouters() relation.
     * 
     * @param int|string $routerId
     * @return Router
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAuthorizedRouter($routerId): Router
    {
        if ($this->isAdmin()) {
            return $this->routers()->findOrFail($routerId);
        } elseif ($this->isReseller()) {
            return $this->resellerRouters()->findOrFail($routerId);
        }

        throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
            'No router found or user is not authorized to access this router.'
        );
    }

    /**
     * Get all routers accessible by the user based on their role.
     * For admins: returns their routers.
     * For resellers: returns their assigned reseller routers.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleRouters()
    {
        if ($this->isAdmin()) {
            return $this->routers()->orderBy('name')->get();
        } elseif ($this->isReseller()) {
            return $this->resellerRouters()->orderBy('name')->get();
        }

        return collect();
    }

    /**
     * Get all profiles accessible by the user based on their role.
     * For admins: returns their own profiles.
     * For resellers: returns their assigned profiles from the admin.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleProfiles()
    {
        if ($this->isAdmin()) {
            return $this->profiles()->orderBy('name')->get();
        } elseif ($this->isReseller()) {
            // Get profiles assigned to this reseller
            return $this->resellerProfiles()->orderBy('name')->get();
        }

        return collect();
    }

    // Profiles relation
    public function profiles()
    {
        return $this->hasMany(UserProfile::class);
    }

    // Invoices relation
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    //Voucher Logs relation
    public function voucherLogs()
    {
        return $this->hasMany(VoucherLog::class);
    }

    // General Settings relation
    public function generalSettings()
    {
        return $this->hasMany(GeneralSetting::class);
    }

    // Tickets relations
    public function ticketsCreated()
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function ticketsOwned()
    {
        return $this->hasMany(Ticket::class, 'owner_id');
    }

    public function ticketsAssigned()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    // Ticket messages relation
    public function ticketMessages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    // Subscriptions relation
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->latest();
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->first();
    }

    // Subscription Management Methods

    public function subscribeToPackage(
        Package $package,
        string $cycle = 'monthly',
        ?string $promoCode = null
    ): Subscription {
        $amount = $cycle === 'yearly'
            ? ($package->price_yearly ?? $package->price_monthly * 12)
            : $package->price_monthly;

        $startDate = now();
        $endDate = $cycle === 'yearly'
            ? $startDate->copy()->addYear()
            : $startDate->copy()->addMonth();

        $subscription = $this->subscriptions()->create([
            'package_id' => $package->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'original_price' => $amount,
            'next_billing_date' => $endDate,
            'status' => 'active',
            'promo_code' => $promoCode,
        ]);

        // Create invoice for subscription only if amount is greater than 0 (skip for free packages)
        if ($amount > 0) {
            $invoice = $this->debit(
                amount: $amount,
                category: 'subscription',
                description: "Subscription to {$package->name} ({$cycle})",
                meta: ['subscription_id' => $subscription->id, 'package_id' => $package->id, 'billing_cycle' => $cycle]
            );

            // Send subscription notification
            $this->notify(new \App\Notifications\Billing\SubscriptionRenewalNotification(
                $subscription,
                $invoice,
                false // Not auto-renewal
            ));
        }

        return $subscription;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function canAddRouter(): bool
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return false;
        }

        $package = $subscription->package;
        $routerCount = $this->routers()->count();

        return $routerCount < $package->max_routers;
    }

    public function getCurrentSubscriptionCost(): float
    {
        $subscription = $this->activeSubscription();
        return $subscription?->amount ?? 0;
    }

    public function getCurrentPackage(): ?Package
    {
        $subscription = $this->activeSubscription();
        return $subscription?->package;
    }

    public function getRemainingRouterSlots(): int
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return 0;
        }

        $package = $subscription->package;
        $routerCount = $this->routers()->count();

        return max(0, $package->max_routers - $routerCount);
    }

    public function getTotalRouterCosts(): float
    {
        return $this->routers()->sum('monthly_isp_cost');
    }

    public function upgradePackage(Package $newPackage, string $cycle = 'monthly'): Subscription
    {
        $currentSub = $this->activeSubscription();

        if ($currentSub) {
            // Cancel current subscription
            $currentSub->cancel('Upgraded to ' . $newPackage->name);
        }

        // Create new subscription
        return $this->subscribeToPackage($newPackage, $cycle);
    }
}
