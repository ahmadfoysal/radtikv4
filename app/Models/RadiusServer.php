<?php

namespace App\Models;

use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class RadiusServer extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'host',
        'ssh_port',
        'username',
        'password',
        'is_active',
        'user_id',

        // Provider / node info
        'provider',
        'provider_server_id',
        'region',
        'plan',
        'status',
        'provisioned_at',
        'last_sync_at',
        'last_error',

        // SSH access
        'ssh_username',
        'ssh_auth_type',
        'ssh_password',
        'ssh_key_name',

        // RADIUS connectivity (file-based, NOT DB)
        'radius_auth_port',
        'radius_acct_port',
        'radius_secret',

        // Subscription package (store JSON/array)
        'package',

        // Billing / behavior flags
        'auto_renew',

        // Monitoring fields
        'last_health_check_at',
        'last_health_status',
        'last_health_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ssh_password' => 'encrypted',
        'radius_secret' => 'encrypted',
        'package' => 'array',
        'provisioned_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'is_active' => 'boolean',
        'auto_renew' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
}
