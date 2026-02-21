<?php

namespace App\Livewire\Radius;

use App\Models\RadiusServer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use AuthorizesRequests, Toast;

    public RadiusServer $server;

    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:255', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $host = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $auth_port = 1812;

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $acct_port = 1813;

    #[Rule(['required', 'string', 'min:8'])]
    public string $secret = '';

    #[Rule(['required', 'string', 'min:32', 'max:255'])]
    public string $auth_token = '';

    #[Rule(['required', 'integer', 'min:1', 'max:60'])]
    public int $timeout = 5;

    #[Rule(['required', 'integer', 'min:1', 'max:10'])]
    public int $retries = 3;

    #[Rule(['nullable', 'string', 'max:500'])]
    public ?string $description = null;

    #[Rule(['boolean'])]
    public bool $is_active = true;

    public function mount(RadiusServer $server): void
    {
        $this->authorize('add_router');

        $this->server = $server;
        $this->name = $server->name;
        $this->host = $server->host;
        $this->auth_port = $server->auth_port;
        $this->acct_port = $server->acct_port;
        $this->secret = $server->secret;
        $this->auth_token = $server->auth_token;
        $this->timeout = $server->timeout;
        $this->retries = $server->retries;
        $this->description = $server->description;
        $this->is_active = $server->is_active;
    }

    public function save(): void
    {
        $this->validate();

        $this->server->update([
            'name' => $this->name,
            'host' => $this->host,
            'auth_port' => $this->auth_port,
            'acct_port' => $this->acct_port,
            'secret' => $this->secret,
            'auth_token' => $this->auth_token,
            'timeout' => $this->timeout,
            'retries' => $this->retries,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $this->success('RADIUS server updated successfully!', redirectTo: route('radius.index'));
    }

    public function render(): View
    {
        return view('livewire.radius.edit');
    }
}
