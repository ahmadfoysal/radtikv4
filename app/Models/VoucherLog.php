<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherLog extends Model
{
    protected $table = 'voucher_logs';

    protected $fillable = [
        'voucher_id',
        'user_id',
        'action',
        'details',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
