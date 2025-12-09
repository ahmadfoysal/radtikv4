<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Voucher;
use App\Models\VoucherLog;
use Illuminate\Support\Facades\Auth;

class VoucherLogger
{
    /**
     * Log a voucher event with snapshot data.
     *
     * @param  Voucher|null  $voucher  The voucher to log (can be null if voucher was already deleted)
     * @param  Router|null  $router  The router associated with the event
     * @param  string  $eventType  The type of event (e.g., 'activated', 'deleted', 'expired', 'synced')
     * @param  array  $extra  Additional metadata to store with the log
     * @return VoucherLog The created log entry
     */
    public static function log(?Voucher $voucher, ?Router $router, string $eventType, array $extra = []): VoucherLog
    {
        $userId = Auth::id();

        // Get snapshot data from voucher's profile relationship
        $profile = $voucher?->profile;

        $data = [
            'user_id' => $userId,
            'voucher_id' => $voucher?->id,
            'router_id' => $router?->id,
            'event_type' => $eventType,
            'username' => $voucher?->username,
            'profile' => $profile?->name,
            'price' => $profile?->price,
            'validity_days' => $profile?->validity,
            'router_name' => $router?->name,
            'meta' => $extra,
        ];

        return VoucherLog::create($data);
    }
}
