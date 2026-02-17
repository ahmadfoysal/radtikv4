<?php

namespace App\Livewire\Radius;

use App\Models\RadiusServer;
use App\Jobs\ConfigureRadiusServerJob;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

class Create extends Component
{
    use AuthorizesRequests, Toast;

    // Manual mode fields - all required
    #[Rule(['required', 'string', 'max:255', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $host = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $auth_port = 1812;

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $acct_port = 1813;

    #[Rule(['required', 'integer', 'min:1', 'max:60'])]
    public int $timeout = 5;

    #[Rule(['required', 'integer', 'min:1', 'max:10'])]
    public int $retries = 3;

    // SSH Configuration
    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $ssh_port = 22;

    #[Rule(['required', 'string', 'max:100'])]
    public string $ssh_username = 'root';

    #[Rule(['required', 'string'])]
    public string $ssh_password = '';

    public function mount(): void
    {
        $this->authorize('add_router');
    }

    public function save(): void
    {
        $this->validate();

        $userId = Auth::id();
        
        // Auto-generate name, secret, and auth token
        $name = 'radius-' . Str::random(8);
        $sharedSecret = Str::random(32); // For MikroTik/NAS devices
        $authToken = Str::random(64); // For Laravel API authentication
        
        $server = RadiusServer::create([
            'name' => $name,
            'host' => $this->host,
            'auth_port' => $this->auth_port,
            'acct_port' => $this->acct_port,
            'secret' => $sharedSecret, // Will be encrypted by model
            'timeout' => $this->timeout,
            'retries' => $this->retries,
            'description' => null,
            'is_active' => true,
            // SSH
            'ssh_port' => $this->ssh_port,
            'ssh_username' => $this->ssh_username,
            'ssh_password' => $this->ssh_password, // Will be encrypted by model
            'ssh_private_key' => null,
            // Linode
            'auto_provision' => false,
            'linode_region' => null,
            'linode_plan' => null,
            'linode_image' => null,
            'linode_label' => null,
            'installation_status' => 'configuring',
            // API Authentication
            'auth_token' => $authToken, // Will be encrypted by model
        ]);

        // Dispatch job to configure secrets on RADIUS server
        ConfigureRadiusServerJob::dispatch($server, $sharedSecret, $authToken);
        
        $this->success('RADIUS server created! Configuration is in progress.', redirectTo: route('radius.index'));
    }

    public function render(): View
    {
        return view('livewire.radius.create');
    }
}
