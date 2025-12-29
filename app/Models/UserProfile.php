<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'name',
        'rate_limit',
        'validity',
        'mac_binding',
        'price',
        'user_id',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'mac_binding' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'user_profile_id', 'id');
    }

    public function resellerAssignments()
    {
        return $this->hasMany(ResellerProfile::class, 'profile_id');
    }

    public function assignedResellers()
    {
        return $this->belongsToMany(User::class, 'reseller_profile', 'profile_id', 'reseller_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }
}
