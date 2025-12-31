<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class VoucherLog extends Model
{
    use Prunable;

    protected $table = 'voucher_logs';

    protected $fillable = [
        'user_id',
        'voucher_id',
        'router_id',
        'event_type',
        'username',
        'profile',
        'price',
        'validity',
        'router_name',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get the prunable model query.
     * Logs older than 6 months will be automatically deleted.
     */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subMonths(6));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
