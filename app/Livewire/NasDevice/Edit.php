<?php

namespace App\Livewire\NasDevice;

use App\Models\RadiusServer;
use App\Models\Router;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use AuthorizesRequests, Toast;

    public Router $nasDevice;

    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:191', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])]
    public string $address = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $port = 8728;

    #[Rule(['required', 'string', 'max:100'])]
    public string $username = '';

    #[Rule(['required', 'string', 'max:191'])]
    public string $password = '';

    #[Rule(['nullable', 'string', 'max:191'])]
    public string $login_address = '';

    #[Rule(['required', 'integer', 'exists:routers,id'])]
    public ?int $parent_router_id = null;

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $note = '';

    public function mount(Router $nasDevice): void
    {
        $this->authorize('edit_router');

        // Ensure it's actually a NAS device
        if (!$nasDevice->is_nas_device) {
            abort(404, 'Not a NAS device');
        }

        $this->nasDevice = $nasDevice;

        $this->name = $nasDevice->name;
        $this->address = $nasDevice->address;
        $this->login_address = $nasDevice->login_address ?? '';
        $this->port = $nasDevice->port;
        $this->username = $nasDevice->username;
        $this->password = Crypt::decryptString($nasDevice->password);
        $this->parent_router_id = $nasDevice->parent_router_id;
        $this->note = $nasDevice->note ?? '';
    }

    public function update(): void
    {
        $this->authorize('edit_router');
        $this->validate();

        // Verify parent router exists and is not a NAS device
        $parentRouter = Router::find($this->parent_router_id);
        if (!$parentRouter || $parentRouter->is_nas_device) {
            $this->error(title: 'Error', description: 'Invalid parent router selected.');
            return;
        }

        // NAS device inherits RADIUS server from parent
        $this->nasDevice->update([
            'name' => $this->name,
            'address' => $this->address,
            'login_address' => $this->login_address,
            'port' => $this->port,
            'username' => $this->username,
            'password' => Crypt::encryptString($this->password),
            'parent_router_id' => $this->parent_router_id,
            'radius_server_id' => $parentRouter->radius_server_id, // Inherit from parent
            'note' => $this->note,
        ]);

        $this->success(title: 'Success', description: 'NAS device updated successfully.');

        $this->redirect(route('nas-devices.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('nas-devices.index'), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        // Get parent routers (non-NAS devices) accessible to user
        $parentRouters = Router::query()
            ->where('is_nas_device', false)
            ->when($user->isReseller(), function ($query) use ($user) {
                $query->whereHas('resellerAssignments', function ($q) use ($user) {
                    $q->where('reseller_id', $user->id);
                });
            })
            ->when($user->isAdmin(), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('name')
            ->get()
            ->map(fn($r) => ['id' => $r->id, 'name' => $r->name . ' (' . $r->address . ')'])
            ->toArray();

        return view('livewire.nas-device.edit', [
            'parentRouters' => $parentRouters,
        ])
            ->title(__('Edit NAS Device'));
    }
}
