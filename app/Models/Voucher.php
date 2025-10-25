<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
