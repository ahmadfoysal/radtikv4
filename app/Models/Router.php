<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Router extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'address',
        'login_address',
        'port',
        'ssh_port',
        'username',
        'password',
        'router_login',
        'note',
        'user_id',
        'zone_id',
        'app_key',
        'monthly_isp_cost',
        'logo',
        'voucher_template_id',
    ];

    protected $casts = [
        'monthly_isp_cost' => 'decimal:2',
    ];

    public function decryptedPassword(): string
    {
        return Crypt::decryptString($this->password);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return Storage::url($this->logo);
    }

    //is Expired



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

    public function resellerAssignments()
    {
        return $this->hasMany(ResellerRouter::class);
    }

    public function assignedResellers()
    {
        return $this->belongsToMany(User::class, 'reseller_router', 'router_id', 'reseller_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function voucherTemplate()
    {
        return $this->belongsTo(VoucherTemplate::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    //Voucher Logs relation
    public function voucherLogs()
    {
        return $this->hasMany(VoucherLog::class);
    }
}
