<?php

namespace App\Livewire\HotspotUsers;

use App\MikroTik\Actions\HotspotUserManager;
use App\Models\Router;
use Livewire\Component;
use Mary\Traits\Toast;

class Logs extends Component
{
    use Toast;

    public $router_id = null;
    public array $logs = [];
    public bool $loading = false;

    public function mount()
    {
        // Initialize empty
    }

    public function updatedRouterId($value)
    {
        if (!$value) {
            $this->logs = [];
            return;
        }

        $this->loadLogs();
    }

    public function loadLogs()
    {
        if (!$this->router_id) {
            $this->logs = [];
            return;
        }

        $this->loading = true;

        try {
            $router = auth()->user()->routers()->findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);
            
            $this->logs = $manager->getHotspotLogs($router);
            
            if (empty($this->logs)) {
                $this->info('No hotspot logs found.');
            }
        } catch (\Throwable $e) {
            $this->error('Failed to load hotspot logs: ' . $e->getMessage());
            $this->logs = [];
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.hotspot-users.logs', [
            'routers' => auth()->user()->routers()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
