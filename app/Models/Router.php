<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Router extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'address',
        'login_address',
        'port',
        'ssh_port',
        'use_radius',
        'username',
        'password',
        'router_login',
        'note',
        'user_id',
        'zone_id',
        'radius_id',
        'app_key',
        'monthly_expense',
        'logo',
        'voucher_template_id',
        'package',
    ];

    public function decryptedPassword(): string
    {
        return Crypt::decryptString($this->password);
    }

    // Add any relationships or additional methods as needed

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function voucherTemplate()
    {
        return $this->belongsTo(VoucherTemplate::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    protected $casts = [
        'package' => 'array',
    ];
}
