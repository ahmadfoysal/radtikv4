<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerProfile extends Model
{
    protected $table = 'reseller_profile';

    protected $fillable = [
        'profile_id',
        'reseller_id',
        'assigned_by',
    ];

    /**
     * Get the profile that belongs to this reseller assignment.
     */
    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }

    /**
     * Get the reseller user assigned to this profile.
     */
    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    /**
     * Get the user who assigned this profile to the reseller.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
