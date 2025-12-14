<?php

namespace App\Livewire\HotspotUsers;

use App\MikroTik\Actions\HotspotUserManager;
use App\Models\Router;
use App\Models\UserProfile;
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

    #[V(['nullable', 'integer', 'exists:user_profiles,id'])]
    public $profile = null;

    #[V(['required', 'string', 'min:3', 'max:64'])]
    public string $username = '';

    #[V(['required', 'string', 'min:3', 'max:64'])]
    public string $password = '';

    public array $available_profiles = [];

    public function mount()
    {
    }

    public function updatedRouterId($value)
    {
        if (!$value) {
            $this->available_profiles = [];
            $this->profile = null;
            return;
        }

        try {
            $router = auth()->user()->routers()->find($value);
            if (!$router) {
                $this->available_profiles = [];
                $this->profile = null;
                return;
            }

            // Load profiles from database instead of MikroTik
            $profiles = auth()->user()->profiles()->orderBy('name')->get();

            $this->available_profiles = $profiles->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name . ($p->rate_limit ? ' (' . $p->rate_limit . ')' : ''),
            ])->toArray();

            // Reset profile selection when router changes
            $this->profile = null;
        } catch (\Throwable $e) {
            $this->error('Failed to load profiles: ' . $e->getMessage());
            $this->available_profiles = [];
            $this->profile = null;
        }
    }

    public function save()
    {
        $this->validate();

        try {
            $router = auth()->user()->routers()->findOrFail($this->router_id);
            $manager = app(HotspotUserManager::class);

            // Get selected profile from database
            $selectedProfile = null;
            if ($this->profile) {
                $selectedProfile = auth()->user()->profiles()->find($this->profile);
                if (!$selectedProfile) {
                    $this->error('Selected profile not found.');
                    return;
                }
            }

            // Get user profile ID - ensure it exists (for voucher record)
            $userProfile = $selectedProfile ?? auth()->user()->profiles()->first();
            if (!$userProfile) {
                $this->error('No bandwidth profile found. Please create a bandwidth profile in the Profile Management section first.');
                return;
            }

            // Create user in MikroTik first - use profile name from database
            $profileName = $selectedProfile ? $selectedProfile->name : null;
            $result = $manager->addUser(
                $router,
                $this->username,
                $this->password,
                $profileName
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
                'bytes_in' => 0,
                'bytes_out' => 0,
                'activated_at' => now(),
            ]);

            $this->success('Hotspot user created successfully in MikroTik and database.');
            
            // Reset form
            $this->reset(['username', 'password']);
            $this->profile = null;
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
            'routers' => auth()->user()->routers()->orderBy('name')->get(['id', 'name', 'address']),
        ]);
    }
}
