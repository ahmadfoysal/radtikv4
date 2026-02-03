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

    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['nullable', 'string', 'max:255', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $host = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $auth_port = 1812;

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $acct_port = 1813;

    #[Rule(['required', 'string', 'min:8'])]
    public string $secret = '';

    #[Rule(['required', 'integer', 'min:1', 'max:60'])]
    public int $timeout = 5;

    #[Rule(['required', 'integer', 'min:1', 'max:10'])]
    public int $retries = 3;

    #[Rule(['nullable', 'string', 'max:500'])]
    public ?string $description = null;

    #[Rule(['boolean'])]
    public bool $is_active = true;

    // SSH Configuration
    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $ssh_port = 22;

    #[Rule(['required', 'string', 'max:100'])]
    public string $ssh_username = 'root';

    #[Rule(['nullable', 'string'])]
    public ?string $ssh_password = null;

    #[Rule(['nullable', 'string'])]
    public ?string $ssh_private_key = null;

    // Linode Configuration
    #[Rule(['boolean'])]
    public bool $auto_provision = true;

    #[Rule(['required', 'string'])]
    public string $linode_region = 'us-east';

    #[Rule(['required', 'string'])]
    public string $linode_plan = 'g6-nanode-1';

    #[Rule(['required', 'string'])]
    public string $linode_image = 'linode/ubuntu22.04';

    public array $regions = [];
    public array $plans = [];
    public array $images = [];

    public function mount(): void
    {
        $this->authorize('add_router');

        // Load Linode options
        $this->regions = [
            'us-east' => 'US East (Newark)',
            'us-west' => 'US West (Fremont)',
            'us-central' => 'US Central (Dallas)',
            'us-southeast' => 'US Southeast (Atlanta)',
            'eu-west' => 'EU West (London)',
            'eu-central' => 'EU Central (Frankfurt)',
            'ap-south' => 'Asia Pacific (Singapore)',
            'ap-northeast' => 'Asia Pacific (Tokyo)',
        ];

        $this->plans = [
            'g6-nanode-1' => 'Nanode 1GB (1 vCPU, 1GB RAM)',
            'g6-standard-1' => 'Linode 2GB (1 vCPU, 2GB RAM)',
            'g6-standard-2' => 'Linode 4GB (2 vCPU, 4GB RAM)',
            'g6-standard-4' => 'Linode 8GB (4 vCPU, 8GB RAM)',
        ];

        $this->images = [
            'linode/ubuntu22.04' => 'Ubuntu 22.04 LTS',
            'linode/ubuntu20.04' => 'Ubuntu 20.04 LTS',
            'linode/debian11' => 'Debian 11',
            'linode/debian12' => 'Debian 12',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $server = RadiusServer::create([
            'name' => $this->name,
            'host' => $this->host,
            'auth_port' => $this->auth_port,
            'acct_port' => $this->acct_port,
            'secret' => $this->secret,
            'timeout' => $this->timeout,
            'retries' => $this->retries,
            'description' => $this->description,
            'is_active' => $this->is_active,
            // SSH
            'ssh_port' => $this->ssh_port,
            'ssh_username' => $this->ssh_username,
            'ssh_password' => $this->ssh_password,
            'ssh_private_key' => $this->ssh_private_key,
            // Linode
            'auto_provision' => $this->auto_provision,
            'linode_region' => $this->linode_region,
            'linode_plan' => $this->linode_plan,
            'linode_image' => $this->linode_image,
            'linode_label' => 'radius-' . strtolower(str_replace(' ', '-', $this->name)),
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
