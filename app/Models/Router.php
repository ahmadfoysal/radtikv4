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
        'nas_identifier',
        'is_nas_device',
        'parent_router_id',
        'radius_server_id',
    ];

    protected $casts = [
        'monthly_isp_cost' => 'decimal:2',
        'is_nas_device' => 'boolean',
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

    // NAS Device relationships
    public function parentRouter()
    {
        return $this->belongsTo(Router::class, 'parent_router_id');
    }

    public function childDevices()
    {
        return $this->hasMany(Router::class, 'parent_router_id');
    }

    public function radiusServer()
    {
        return $this->belongsTo(RadiusServer::class);
    }

    // Helper methods for NAS devices
    public function isNasDevice(): bool
    {
        return $this->is_nas_device ?? false;
    }

    public function isParentRouter(): bool
    {
        return !$this->is_nas_device && $this->childDevices()->exists();
    }

    public function getEffectiveNasIdentifier(): ?string
    {
        if ($this->is_nas_device && $this->parentRouter) {
            return $this->parentRouter->nas_identifier;
        }
        return $this->nas_identifier;
    }

    // Scope queries
    public function scopeNasDevices($query)
    {
        return $query->where('is_nas_device', true);
    }

    public function scopeMainRouters($query)
    {
        return $query->where('is_nas_device', false);
    }
}
