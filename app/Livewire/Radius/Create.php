<?php

namespace App\Livewire\Radius;

use App\Models\RadiusServer;
use App\Services\LinodeService;
use App\Jobs\ProvisionRadiusServer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class Create extends Component
{
    use AuthorizesRequests, Toast;

    // Auto-provision toggle
    #[Rule(['boolean'])]
    public bool $auto_provision = true;

    // Manual mode fields - only required when auto_provision is false
    #[Rule(['required_if:auto_provision,false', 'nullable', 'string', 'max:255', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $host = '';

    #[Rule(['required_if:auto_provision,false', 'nullable', 'string', 'min:8'])]
    public string $secret = '';

    #[Rule(['required_if:auto_provision,false', 'nullable', 'integer', 'between:1,65535'])]
    public int $auth_port = 1812;

    #[Rule(['required_if:auto_provision,false', 'nullable', 'integer', 'between:1,65535'])]
    public int $acct_port = 1813;

    #[Rule(['required_if:auto_provision,false', 'nullable', 'integer', 'min:1', 'max:60'])]
    public int $timeout = 5;

    #[Rule(['required_if:auto_provision,false', 'nullable', 'integer', 'min:1', 'max:10'])]
    public int $retries = 3;

    // SSH Configuration - only for manual mode
    #[Rule(['required_if:auto_provision,false', 'nullable', 'integer', 'between:1,65535'])]
    public int $ssh_port = 22;

    #[Rule(['required_if:auto_provision,false', 'nullable', 'string', 'max:100'])]
    public string $ssh_username = 'root';

    #[Rule(['required_if:auto_provision,false', 'nullable', 'string'])]
    public ?string $ssh_password = null;

    // Fixed Linode configuration (not user-editable)
    public string $linode_region = 'ap-south';
    public string $linode_plan = 'g6-nanode-1';
    public string $linode_image = 'linode/ubuntu22.04';

    public function mount(): void
    {
        $this->authorize('add_router');
        // Linode configuration is set to defaults (ap-south, g6-nanode-1, ubuntu22.04)
    }

    public function save(): void
    {
        $this->validate();

        $userId = auth()->id();
        
        // Auto-generate data for auto-provision mode
        if ($this->auto_provision) {
            $name = 'radius-user-' . $userId . '-' . time();
            $secret = bin2hex(random_bytes(16)); // 32 character hex string
            $sshPassword = bin2hex(random_bytes(8)); // 16 character hex string
            $host = null; // Will be set after Linode creation
            $authPort = 1812;
            $acctPort = 1813;
            $timeout = 5;
            $retries = 3;
            $sshPort = 22;
            $sshUsername = 'root';
        } else {
            $name = 'radius-user-' . $userId . '-manual-' . time();
            $secret = $this->secret;
            $sshPassword = $this->ssh_password;
            $host = $this->host;
            $authPort = $this->auth_port;
            $acctPort = $this->acct_port;
            $timeout = $this->timeout;
            $retries = $this->retries;
            $sshPort = $this->ssh_port;
            $sshUsername = $this->ssh_username;
        }

        $server = RadiusServer::create([
            'name' => $name,
            'host' => $host,
            'auth_port' => $authPort,
            'acct_port' => $acctPort,
            'secret' => $secret,
            'timeout' => $timeout,
            'retries' => $retries,
            'description' => null,
            'is_active' => true,
            // SSH
            'ssh_port' => $sshPort,
            'ssh_username' => $sshUsername,
            'ssh_password' => $sshPassword,
            'ssh_private_key' => null,
            // Linode
            'auto_provision' => $this->auto_provision,
            'linode_region' => $this->linode_region,
            'linode_plan' => $this->linode_plan,
            'linode_image' => $this->linode_image,
            'linode_label' => $name,
            'installation_status' => $this->auto_provision ? 'pending' : 'completed',
        ]);

        // If auto-provision is enabled, dispatch job to create Linode node
        if ($this->auto_provision) {
            // Dispatch the provisioning job
            ProvisionRadiusServer::dispatch($server);
            
            $this->success('RADIUS server created! Auto-provisioning started in background.', redirectTo: route('radius.index'));
        } else {
            $this->success('RADIUS server created successfully!', redirectTo: route('radius.index'));
        }
    }

    public function render(): View
    {
        return view('livewire.radius.create');
    }
}
