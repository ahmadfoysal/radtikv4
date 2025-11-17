<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Router;
use App\Models\User;
use App\Models\RouterProfile;

class Voucher extends Model
{
    protected $fillable = [
        'name',
        'username',
        'password',
        'router_profile',
        'radius_profile',
        'expires_at',
        'user_id',
        'router_id',
        'created_by',
        'status',
        'mac_address',
        'activated_at',
        'batch',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function radiusProfile()
    {
        return $this->belongsTo(RadiusProfile::class, 'radius_profile_id');
    }

    public function radiusServer()
    {
        return $this->belongsTo(RadiusServer::class, 'radius_server_id');
    }
}
