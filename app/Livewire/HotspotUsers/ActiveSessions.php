<?php

namespace App\Livewire\HotspotUsers;

use App\MikroTik\Actions\HotspotUserManager;
use App\Models\Router;
use Livewire\Component;
use Mary\Traits\Toast;

class ActiveSessions extends Component
{
    use Toast;

    public $router_id = null;
    public array $sessions = [];
    public bool $loading = false;

    public function mount()
    {
        // Initialize empty
    }

    public function updatedRouterId($value)
    {
        if (!$value) {
            $this->sessions = [];
            return;
        }

        $this->loadSessions();
    }

    public function loadSessions()
    {
        if (!$this->router_id) {
            $this->sessions = [];
            return;
        }

        $this->loading = true;

        try {
            $router = Router::findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);
            
            $this->sessions = $manager->getActiveSessions($router);
            
            if (empty($this->sessions)) {
                $this->info('No active sessions found.');
            }
        } catch (\Throwable $e) {
            $this->error('Failed to load active sessions: ' . $e->getMessage());
            $this->sessions = [];
        } finally {
            $this->loading = false;
        }
    }

    public function deleteSession(string $sessionId)
    {
        try {
            $router = Router::findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);
            
            $manager->removeActiveUser($router, $sessionId);
            
            $this->success('Session removed successfully.');
            $this->loadSessions();
        } catch (\Throwable $e) {
            $this->error('Failed to remove session: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.hotspot-users.active-sessions', [
            'routers' => auth()->user()->routers()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
