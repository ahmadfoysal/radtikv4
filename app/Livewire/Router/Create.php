<?php

namespace App\Livewire\Router;

use App\Models\Package;
use App\Models\ResellerRouter;
use App\Models\Router;
use App\Models\VoucherTemplate;
use App\Models\Zone;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class Create extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;

    #[Rule(['required', 'string', 'max:100'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:191', 'regex:/^(([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}|(\d{1,3}\.){3}\d{1,3})$/'])] // IP or hostname
    public string $address = '';

    #[Rule(['required', 'integer', 'between:1,65535'])]
    public int $port = 8728;

    #[Rule(['required', 'string', 'max:100'])]
    public string $username = '';

    #[Rule(['required', 'string', 'max:191'])]
    public string $password = '';

    #[Rule(['nullable', 'string', 'max:191'])]
    public string $login_address = '';

    #[Rule(['nullable', 'integer', 'exists:voucher_templates,id'])]
    public ?int $voucher_template_id = null;

    #[Rule(['nullable', 'numeric', 'min:0'])]
    public float $monthly_isp_cost = 0.0;

    #[Rule(['nullable', 'integer', 'exists:zones,id'])]
    public ?int $zone_id = null;

    #[Rule(['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg,webp'])]
    public $logo = null;

    public function mount(): void
    {
        $this->authorize('add_router');

        $user = Auth::user();

        if ($user->isReseller() && !$user->admin) {
            abort(403, 'Reseller must be assigned to an admin to create routers.');
        }

        $this->voucher_template_id = VoucherTemplate::query()
            ->where('is_active', true)
            ->value('id') ?? VoucherTemplate::query()->value('id');
    }

    public function save()
    {
        $this->authorize('add_router');
        $this->validate();

        $user = Auth::user();

        // Determine who will be billed and own the router
        $billingUser = $user->isReseller() ? $user->admin : $user;
        $routerOwner = $user->isReseller() ? $user->admin : $user;

        if (!$billingUser) {
            $this->error(title: 'Error', description: 'Reseller must be assigned to an admin to create routers.');
            return;
        }

        $voucherTemplateId = $this->voucher_template_id
            ?? VoucherTemplate::query()->where('is_active', true)->value('id')
            ?? VoucherTemplate::query()->value('id');

        // Handle logo upload
        $logoPath = null;
        if ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
        }

        // Check if billing user (admin) has an active subscription
        if (!$billingUser->hasActiveSubscription()) {
            $this->error(title: 'No Active Subscription', description: 'You need an active subscription to add routers.');
            return;
        }

        // Get active subscription and check expiration
        $subscription = $billingUser->activeSubscription();

        if ($subscription->hasEnded()) {
            $this->error(
                title: 'Subscription Expired',
                description: 'Your subscription has expired on ' . $subscription->end_date->format('M d, Y') . '. Please renew your subscription to add routers.'
            );
            return;
        }

        // Check if the admin can add more routers based on their subscription package
        if (!$billingUser->canAddRouter()) {
            $package = $billingUser->getCurrentPackage();
            $maxRouters = $package ? $package->max_routers : 0;
            $currentRouters = $billingUser->routers()->count();
            $this->error(
                title: 'Router Limit Reached',
                description: "You have {$currentRouters} of {$maxRouters} routers allowed by your {$package->name} package. Upgrade your package to add more routers."
            );
            return;
        }

        try {
            // Create router directly - no per-router billing anymore
            $router = Router::create([
                'name' => $this->name,
                'address' => $this->address,
                'login_address' => $this->login_address,
                'port' => $this->port,
                'username' => $this->username,
                'password' => Crypt::encryptString($this->password),
                'app_key' => bin2hex(random_bytes(16)),
                'user_id' => $routerOwner->id,  // Router belongs to admin, not reseller
                'voucher_template_id' => $voucherTemplateId,
                'monthly_isp_cost' => $this->monthly_isp_cost,
                'zone_id' => $this->zone_id,
                'logo' => $logoPath,
            ]);

            // If reseller created the router, automatically assign it to them
            if ($user->isReseller() && $router) {
                ResellerRouter::create([
                    'router_id' => $router->id,
                    'reseller_id' => $user->id,
                    'assigned_by' => $routerOwner->id, // Admin who owns the router
                ]);
            }
        } catch (\RuntimeException $e) {
            // Delete uploaded logo if router creation fails
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $this->error(title: 'Error', description: $e->getMessage());

            return;
        }

        // Reset form (keep port default)
        $this->reset([
            'name',
            'address',
            'login_address',
            'username',
            'password',
            'voucher_template_id',
            'monthly_isp_cost',
            'zone_id',
            'logo',
        ]);
        $this->port = 8728;
        $this->voucher_template_id = VoucherTemplate::query()
            ->where('is_active', true)
            ->value('id') ?? VoucherTemplate::query()->value('id');
        $this->monthly_isp_cost = 0.0;

        // Optional: toast/notify
        $this->success(title: 'Success', description: 'Router added successfully.');

        return $this->redirect(route('routers.index'), navigate: true);
    }

    public function cancel()
    {
        $this->redirect(route('routers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.router.create', [
            'voucherTemplates' => VoucherTemplate::select('id', 'name')
                ->orderBy('name')
                ->get(),
        ])
            ->title(__('Add Router'));
    }
}
