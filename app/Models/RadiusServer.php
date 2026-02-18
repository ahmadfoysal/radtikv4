<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class RadiusServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'auth_port',
        'acct_port',
        'secret',
        'timeout',
        'retries',
        'is_active',
        'description',
        // SSH
        'ssh_port',
        'ssh_username',
        'ssh_password',
        'ssh_private_key',
        // Linode
        // 'linode_node_id',
        // 'linode_region',
        // 'linode_plan',
        // 'linode_image',
        // 'linode_label',
        // 'linode_ipv4',
        // 'linode_ipv6',
        // Installation
        'installation_status',
        'installation_log',
        'installed_at',
        // 'auto_provision',
        // API Authentication
        'auth_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_provision' => 'boolean',
        'auth_port' => 'integer',
        'acct_port' => 'integer',
        'timeout' => 'integer',
        'retries' => 'integer',
        'ssh_port' => 'integer',
        'installed_at' => 'datetime',
    ];

    /**
     * Get the encrypted secret
     */
    public function getSecretAttribute($value): string
    {
        return $value ? Crypt::decrypt($value) : '';
    }

    /**
     * Set the encrypted secret
     */
    public function setSecretAttribute($value): void
    {
        $this->attributes['secret'] = Crypt::encrypt($value);
    }

    /**
     * Get the encrypted SSH password
     */
    public function getSshPasswordAttribute($value): ?string
    {
        return $value ? Crypt::decrypt($value) : null;
    }

    /**
     * Set the encrypted SSH password
     */
    public function setSshPasswordAttribute($value): void
    {
        $this->attributes['ssh_password'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the encrypted SSH private key
     */
    public function getSshPrivateKeyAttribute($value): ?string
    {
        return $value ? Crypt::decrypt($value) : null;
    }

    /**
     * Set the encrypted SSH private key
     */
    public function setSshPrivateKeyAttribute($value): void
    {
        $this->attributes['ssh_private_key'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the encrypted auth token
     */
    public function getAuthTokenAttribute($value): ?string
    {
        return $value ? Crypt::decrypt($value) : null;
    }

    /**
     * Set the encrypted auth token
     */
    public function setAuthTokenAttribute($value): void
    {
        $this->attributes['auth_token'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the API base URL (uses host:5000 by default)
     */
    public function getApiUrlAttribute(): string
    {
        $host = $this->host ?? $this->linode_ipv4;
        return $host ? "http://{$host}:5000" : '';
    }

    /**
     * Get API endpoint URL for voucher sync
     */
    public function getSyncEndpointAttribute(): string
    {
        return $this->api_url . '/sync/vouchers';
    }

    /**
     * Check if server is ready to use
     */
    public function isReady(): bool
    {
        return $this->installation_status === 'completed' && $this->is_active;
    }

    /**
     * Check if server is being provisioned
     */
    public function isProvisioning(): bool
    {
        return in_array($this->installation_status, ['pending', 'creating', 'installing']);
    }

    /**
     * Check if installation failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->installation_status, ['failed', 'error']);
    }
}
