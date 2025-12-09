<?php

namespace App\Livewire\HotspotUsers;

use App\MikroTik\Actions\HotspotUserManager;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule as V;
use Livewire\Component;
use Mary\Traits\Toast;

class Create extends Component
{
    use Toast;

    #[V(['required', 'exists:routers,id'])]
    public $router_id = null;

    #[V(['nullable', 'string', 'max:255'])]
    public $profile = '';

    #[V(['required', 'string', 'min:3', 'max:64'])]
    public string $username = '';

    #[V(['required', 'string', 'min:3', 'max:64'])]
    public string $password = '';

    public array $available_profiles = [];

    public function mount()
    {
        $this->loadProfiles();
    }

    public function loadProfiles()
    {
        // Will be loaded dynamically when router is selected
        $this->available_profiles = [];
    }

    public function updatedRouterId($value)
    {
        if (!$value) {
            $this->available_profiles = [];
            $this->profile = '';
            return;
        }

        try {
            $router = Router::find($value);
            if (!$router) {
                $this->available_profiles = [];
                $this->profile = '';
                return;
            }

            $manager = app(HotspotUserManager::class);
            $profiles = $manager->getHotspotProfiles($router);

            $this->available_profiles = collect($profiles)
                ->map(fn ($p) => ['id' => $p['name'] ?? '', 'name' => $p['name'] ?? ''])
                ->filter(fn ($p) => !empty($p['id']))
                ->values()
                ->all();

            // Reset profile selection when router changes
            $this->profile = '';
        } catch (\Throwable $e) {
            $this->error('Failed to load profiles: ' . $e->getMessage());
            $this->available_profiles = [];
            $this->profile = '';
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $router = Router::findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);

            // Get user profile ID - ensure it exists
            $userProfile = auth()->user()->profiles()->first();
            if (!$userProfile) {
                $this->error('No user profile found. Please create a user profile first.');
                return;
            }

            // Create user in MikroTik first
            $result = $manager->addUser(
                $router,
                $this->username,
                $this->password,
                $this->profile ?: null
            );

            // Check if MikroTik creation was successful
            if (isset($result['ok']) && $result['ok'] === false) {
                $this->error('Failed to create user in MikroTik: ' . ($result['message'] ?? 'Unknown error'));
                return;
            }

            // Create record in vouchers table only after successful MikroTik creation
            $batch = 'HS' . now()->format('ymdHis') . Str::upper(Str::random(4));
            
            Voucher::create([
                'name' => $this->username,
                'username' => $this->username,
                'password' => $this->password,
                'batch' => $batch,
                'status' => 'active',
                'created_by' => auth()->id(),
                'user_id' => auth()->id(),
                'router_id' => $this->router_id,
                'user_profile_id' => $userProfile->id,
                'is_radius' => false,
                'bytes_in' => 0,
                'bytes_out' => 0,
                'activated_at' => now(),
            ]);

            $this->success('Hotspot user created successfully in MikroTik and database.');
            
            // Reset form
            $this->reset(['username', 'password', 'profile']);
            $this->loadProfiles();
        } catch (\Throwable $e) {
            $this->error('Failed to create hotspot user: ' . $e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.hotspot-users.create', [
            'routers' => Router::orderBy('name')->get(['id', 'name', 'address']),
        ]);
    }
}
