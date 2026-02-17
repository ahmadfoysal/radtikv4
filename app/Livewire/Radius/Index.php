<?php

namespace App\Livewire\Radius;

use App\Models\RadiusServer;
use App\Services\RadiusServerSshService;
use App\Jobs\ConfigureRadiusServerJob;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

class Index extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    public string $q = '';

    public int $perPage = 12;

    public ?int $deletingId = null;

    protected $queryString = [
        'q' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('view_router');
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    protected function paginatedServers(): LengthAwarePaginator
    {
        return RadiusServer::query()
            ->when($this->q, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->q}%")
                      ->orWhere('host', 'like', "%{$this->q}%")
                      ->orWhere('description', 'like', "%{$this->q}%");
                });
            })
            ->latest()
            ->paginate($this->perPage);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        $server = RadiusServer::find($this->deletingId);

        if (!$server) {
            $this->error('RADIUS server not found.');
            $this->deletingId = null;
            return;
        }

        $serverName = $server->name;
        $server->delete();

        $this->success("RADIUS server '{$serverName}' deleted successfully!");
        $this->deletingId = null;
    }

    public function toggleActive(int $id): void
    {
        $server = RadiusServer::find($id);

        if (!$server) {
            $this->error('RADIUS server not found.');
            return;
        }

        $server->is_active = !$server->is_active;
        $server->save();

        $status = $server->is_active ? 'activated' : 'deactivated';
        $this->success("RADIUS server '{$server->name}' {$status} successfully!");
    }

    public function pingServer(int $id): void
    {
        $server = RadiusServer::find($id);

        if (!$server) {
            $this->error('RADIUS server not found.');
            return;
        }

        try {
            $sshService = new RadiusServerSshService($server);
            $result = $sshService->testConnection();

            if ($result['success']) {
                $this->success("Server '{$server->host}' is reachable!");
            } else {
                $this->error("Cannot connect to server '{$server->host}'");
            }
        } catch (\Exception $e) {
            $this->error("Connection test failed: {$e->getMessage()}");
        }
    }

    public function retryConfiguration(int $id): void
    {
        $server = RadiusServer::find($id);

        if (!$server) {
            $this->error('RADIUS server not found.');
            return;
        }

        try {
            // Generate new secrets
            $sharedSecret = Str::random(32);
            $authToken = Str::random(64);

            // Update server
            $server->update([
                'secret' => $sharedSecret,
                'auth_token' => $authToken,
                'installation_status' => 'configuring',
            ]);

            // Dispatch configuration job
            ConfigureRadiusServerJob::dispatch($server, $sharedSecret, $authToken);

            $this->success("Configuration job dispatched for '{$server->host}'!");
        } catch (\Exception $e) {
            $this->error("Failed to retry configuration: {$e->getMessage()}");
        }
    }

    public function render(): View
    {
        return view('livewire.radius.index', [
            'servers' => $this->paginatedServers(),
        ]);
    }
}
