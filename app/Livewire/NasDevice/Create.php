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

class Create extends Component
{
    use AuthorizesRequests, Toast;

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

    public function mount(): void
    {
        $this->authorize('add_router');

        $user = Auth::user();

        if ($user->isReseller() && !$user->admin) {
            abort(403, 'Reseller must be assigned to an admin to create NAS devices.');
        }

        // Check subscription status for admins
        if ($user->isAdmin()) {
            $subscription = $user->activeSubscription();

            if (!$subscription) {
                $this->error('You need an active subscription to add NAS devices. Please subscribe to a package first.', redirectTo: route('subscription.index'));
                return;
            }

            $now = now();
            $endDate = $subscription->end_date;

            if ($now->gt($endDate)) {
                $gracePeriodDays = $subscription->package->grace_period_days ?? 0;
                $gracePeriodEndDate = $endDate->copy()->addDays($gracePeriodDays);
                $daysRemaining = max(0, (int) $now->diffInDays($gracePeriodEndDate));

                $this->error("Your subscription has expired. You cannot add NAS devices during the grace period. Please renew within {$daysRemaining} day(s) to restore access.", redirectTo: route('subscription.index'));
                return;
            }
        }
    }

    public function save()
    {
        $this->authorize('add_router');
        $this->validate();

        $user = Auth::user();
        $routerOwner = $user->isReseller() ? $user->admin : $user;

        if (!$routerOwner) {
            $this->error(title: 'Error', description: 'Reseller must be assigned to an admin to create NAS devices.');
            return;
        }

        // Verify parent router exists and belongs to user's scope
        $parentRouter = Router::find($this->parent_router_id);
        if (!$parentRouter || $parentRouter->is_nas_device) {
            $this->error(title: 'Error', description: 'Invalid parent router selected.');
            return;
        }

        // Check subscription
        $billingUser = $user->isReseller() ? $user->admin : $user;
        if (!$billingUser->hasActiveSubscription()) {
            $this->error(title: 'No Active Subscription', description: 'You need an active subscription to add NAS devices.');
            return;
        }

        $subscription = $billingUser->activeSubscription();
        if ($subscription->hasEnded()) {
            $this->error(
                title: 'Subscription Expired',
                description: 'Your subscription has expired on ' . $subscription->end_date->format('M d, Y') . '. Please renew your subscription to add NAS devices.'
            );
            return;
        }

        // Check router limit
        if (!$billingUser->canAddRouter()) {
            $package = $billingUser->getCurrentPackage();
            $maxRouters = $package ? $package->max_routers : 0;
            $currentRouters = $billingUser->routers()->count();
            $this->error(
                title: 'Router Limit Reached',
                description: "You have {$currentRouters} of {$maxRouters} routers/NAS devices allowed by your {$package->name} package. Upgrade your package to add more."
            );
            return;
        }

        try {
            // Create NAS device - it will inherit NAS identifier and RADIUS server from parent
            $nasDevice = Router::create([
                'name' => $this->name,
                'address' => $this->address,
                'login_address' => $this->login_address,
                'port' => $this->port,
                'username' => $this->username,
                'password' => Crypt::encryptString($this->password),
                'app_key' => bin2hex(random_bytes(16)),
                'user_id' => $routerOwner->id,
                'is_nas_device' => true,
                'parent_router_id' => $this->parent_router_id,
                'radius_server_id' => $parentRouter->radius_server_id, // Inherit from parent
                'note' => $this->note,
                'nas_identifier' => null, // Will use parent's identifier
            ]);

            // If reseller created the NAS device, automatically assign it to them
            if ($user->isReseller() && $nasDevice) {
                \App\Models\ResellerRouter::create([
                    'router_id' => $nasDevice->id,
                    'reseller_id' => $user->id,
                    'assigned_by' => $routerOwner->id,
                ]);
            }
        } catch (\Exception $e) {
            $this->error(title: 'Error', description: 'Failed to create NAS device: ' . $e->getMessage());
            return;
        }

        // Reset form
        $this->reset([
            'name',
            'address',
            'login_address',
            'username',
            'password',
            'parent_router_id',
            'note',
        ]);
        $this->port = 8728;

        $this->success(title: 'Success', description: 'NAS device added successfully.');

        return $this->redirect(route('nas-devices.index'), navigate: true);
    }

    public function cancel()
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
                // Resellers can only see routers assigned to them
                $query->whereHas('resellerAssignments', function ($q) use ($user) {
                    $q->where('reseller_id', $user->id);
                });
            })
            ->when($user->isAdmin(), function ($query) use ($user) {
                // Admins see their own routers
                $query->where('user_id', $user->id);
            })
            ->orderBy('name')
            ->get()
            ->map(fn($r) => ['id' => $r->id, 'name' => $r->name . ' (' . $r->address . ')'])
            ->toArray();

        return view('livewire.nas-device.create', [
            'parentRouters' => $parentRouters,
        ])
            ->title(__('Add NAS Device'));
    }
}
