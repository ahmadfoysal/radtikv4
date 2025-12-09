<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasBilling;
use App\Models\Traits\HasRouterBilling;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasBilling, HasFactory, HasRoles, HasRouterBilling, Notifiable, TwoFactorAuthenticatable;

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
        'subscription',
        'is_active',
        'last_login_at',
        'is_phone_verified',
        'expiration_date',
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
            'subscription' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_phone_verified' => 'boolean',
            'expiration_date' => 'date',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'commission' => 'decimal:2',
        ];
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

    // Reseller relation
    public function reseller()
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    // Admins relation
    public function admins()
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

    // Radius Servers relation
    public function radiusServers()
    {
        return $this->hasMany(RadiusServer::class);
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
}
