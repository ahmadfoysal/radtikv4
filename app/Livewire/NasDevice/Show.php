<?php

namespace App\Livewire\NasDevice;

use App\Models\Router;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class Show extends Component
{
    use AuthorizesRequests, Toast;

    public Router $nasDevice;

    public function mount(Router $nasDevice): void
    {
        $this->authorize('view_router');

        // Verify this is actually a NAS device
        if (!$nasDevice->is_nas_device) {
            abort(404, 'Not a NAS device');
        }

        // Ensure user has access to this NAS device
        $accessibleRouters = auth()->user()->getAccessibleRouters();
        if (!$accessibleRouters->contains($nasDevice->id)) {
            abort(403, 'You do not have permission to view this NAS device');
        }

        $this->nasDevice = $nasDevice->load(['parentRouter', 'radiusServer', 'zone', 'user']);
    }

    public function render(): View
    {
        $this->authorize('view_router');
        return view('livewire.nas-device.show');
    }
}
