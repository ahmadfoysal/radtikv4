<?php

namespace App\Models;

use App\Services\VoucherLogger;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{

    protected $fillable = [
        'name',
        'user_profile_id',
        'username',
        'password',
        'status',
        'mac_address',
        'activated_at',
        'expires_at',
        'user_id',
        'router_id',
        'created_by',
        'bytes_in',
        'bytes_out',
        'up_time',
        'batch',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted()
    {
        // Log voucher deletion BEFORE it's deleted to avoid FK constraint issues
        static::deleting(function ($voucher) {
            VoucherLogger::log(
                $voucher,
                $voucher->router,
                'deleted',
                [
                    'deleted_by' => auth()->id(),
                    'batch' => $voucher->batch,
                    'status' => $voucher->status,
                ]
            );
        });
    }

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

    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }

    public function logs()
    {
        return $this->hasMany(VoucherLog::class);
    }
}
