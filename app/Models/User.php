<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\UserProfile;
use App\Models\Voucher;
use App\Models\Router;
use App\Models\RadiusServer;
use App\Models\RadiusProfile;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

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

    //Reseller relation
    public function reseller()
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    //Admins relation
    public function admins()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    //Vouchers relation
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    //Router relation

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    //Radius Servers relation
    public function radiusServers()
    {
        return $this->hasMany(RadiusServer::class);
    }

    //Radius Profiles relation
    public function radiusProfiles()
    {
        return $this->hasMany(RadiusProfile::class);
    }

    //Profiles relation
    public function profiles()
    {
        return $this->hasMany(UserProfile::class);
    }
}
