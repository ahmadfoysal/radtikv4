<?php

namespace App\Livewire\HotspotUsers;

use App\Models\Router;
use App\Models\UserProfile;
use App\Models\Voucher;
use App\Services\RadiusApiService;
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
        $this->authorize('create_single_user');
    }

    public function updatedRouterId($value)
    {
        if (!$value) {
            $this->available_profiles = [];
            $this->profile = null;
            return;
        }

        try {
            $user = auth()->user();
            try {
                $router = $user->getAuthorizedRouter($value);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $this->available_profiles = [];
                $this->profile = null;
                return;
            }

            $profiles = $user->getAccessibleProfiles();

            $this->available_profiles = $profiles->map(fn($p) => [
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
        $this->authorize('create_single_user');
        $this->validate();

        try {
            $user = auth()->user();
            $router = $user->getAuthorizedRouter($this->router_id);

            // Check if router has RADIUS server configured
            if (!$router->radiusServer || !$router->radiusServer->isReady()) {
                $this->error('This router does not have a RADIUS server configured or it is not ready. Please configure a RADIUS server first.');
                return;
            }

            // Get accessible profiles based on user role
            $accessibleProfiles = $user->getAccessibleProfiles();

            // Get selected profile from database
            $selectedProfile = null;
            if ($this->profile) {
                $selectedProfile = $accessibleProfiles->firstWhere('id', $this->profile);
                if (!$selectedProfile) {
                    $this->error('Selected profile not found or you are not authorized to use it.');
                    return;
                }
            }

            // Get user profile ID - ensure it exists (for voucher record)
            $userProfile = $selectedProfile ?? $accessibleProfiles->first();
            if (!$userProfile) {
                $this->error('No bandwidth profile found. Please create a bandwidth profile in the Profile Management section first.');
                return;
            }

            // Create user in RADIUS server first
            $radiusService = new RadiusApiService($router->radiusServer);
            
            try {
                $result = $radiusService->createSingleVoucher(
                    $this->username,
                    $this->password,
                    $userProfile->rate_limit ?? '10M/10M',
                    $router->nas_identifier
                );

                // Check if RADIUS creation was successful
                if (!isset($result['synced']) || $result['synced'] !== 1) {
                    $errorMsg = isset($result['errors']) ? implode(', ', $result['errors']) : 'Unknown error';
                    $this->error('Failed to create user in RADIUS server: ' . $errorMsg);
                    return;
                }

            } catch (\Exception $e) {
                $this->error('Failed to create user in RADIUS server: ' . $e->getMessage());
                return;
            }

            // Create record in vouchers table only after successful RADIUS creation
            $batch = 'HS' . now()->format('ymdHis') . Str::upper(Str::random(4));

            Voucher::create([
                'name' => $this->username,
                'username' => $this->username,
                'password' => $this->password,
                'batch' => $batch,
                'status' => 'unused',
                'created_by' => auth()->id(),
                'user_id' => auth()->id(),
                'router_id' => $this->router_id,
                'user_profile_id' => $userProfile->id,
                'bytes_in' => 0,
                'bytes_out' => 0,
            ]);

            $this->success('Hotspot user created successfully in RADIUS server and database.');

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
        $user = auth()->user();
        $routers = $user->getAccessibleRouters()->map(fn($router) => [
            'id' => $router->id,
            'name' => $router->name,
            'address' => $router->address,
        ]);

        return view('livewire.hotspot-users.create', [
            'routers' => $routers,
        ]);
    }
}
