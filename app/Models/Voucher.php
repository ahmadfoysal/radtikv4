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
        'radius_sync_status',
        'radius_synced_at',
        'radius_sync_error',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'radius_synced_at' => 'datetime',
        'radius_sync_status' => 'string',
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

    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }

    public function logs()
    {
        return $this->hasMany(VoucherLog::class);
    }

    // RADIUS Sync Helper Methods
    
    public function isPendingSync(): bool
    {
        return $this->radius_sync_status === 'pending';
    }

    public function isSynced(): bool
    {
        return $this->radius_sync_status === 'synced';
    }

    public function isSyncFailed(): bool
    {
        return $this->radius_sync_status === 'failed';
    }

    public function markAsSynced(): void
    {
        $this->update([
            'radius_sync_status' => 'synced',
            'radius_synced_at' => now(),
            'radius_sync_error' => null,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'radius_sync_status' => 'failed',
            'radius_sync_error' => $error,
        ]);
    }

    public function resetSyncStatus(): void
    {
        $this->update([
            'radius_sync_status' => 'pending',
            'radius_synced_at' => null,
            'radius_sync_error' => null,
        ]);
    }
}
