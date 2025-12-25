<?php

namespace App\Livewire\Subscription;

use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

class Index extends Component
{
    use AuthorizesRequests, Toast;

    public ?int $selectedPackageId = null;
    public string $selectedCycle = 'monthly';
    public bool $showSubscribeModal = false;
    public string $viewCycle = 'monthly'; // For toggling display between monthly/yearly

    public function getDiscountAmountProperty(): float
    {
        $user = Auth::user();
        if (!$this->selectedPackageId || !$user->hasRole('admin') || $user->commission <= 0) {
            return 0;
        }

        $package = Package::find($this->selectedPackageId);
        if (!$package) {
            return 0;
        }

        $amount = $this->selectedCycle === 'yearly'
            ? ($package->price_yearly ?? $package->price_monthly * 12)
            : $package->price_monthly;

        return round(($amount * $user->commission) / 100, 2);
    }

    public function getFinalAmountProperty(): float
    {
        if (!$this->selectedPackageId) {
            return 0;
        }

        $package = Package::find($this->selectedPackageId);
        if (!$package) {
            return 0;
        }

        $amount = $this->selectedCycle === 'yearly'
            ? ($package->price_yearly ?? $package->price_monthly * 12)
            : $package->price_monthly;

        return $amount - $this->discountAmount;
    }

    public function mount(): void
    {
        // Only admins can access this page
        abort_unless(Auth::user()?->isAdmin(), 403, 'Only admins can manage subscriptions.');
    }

    public function openSubscribeModal(int $packageId, string $cycle = 'monthly'): void
    {
        $package = Package::findOrFail($packageId);
        $user = Auth::user();

        // Check if downgrade is allowed
        if (!$this->canDowngradeToPackage($user, $package)) {
            return;
        }

        $this->selectedPackageId = $packageId;
        $this->selectedCycle = $cycle;
        $this->showSubscribeModal = true;
    }

    /**
     * Check if user can downgrade to the selected package
     */
    protected function canDowngradeToPackage($user, Package $package): bool
    {
        $currentSubscription = $user->activeSubscription();

        // Allow if no current subscription
        if (!$currentSubscription) {
            return true;
        }

        // Allow if upgrading (same or higher price)
        if ($package->price_monthly >= $currentSubscription->package->price_monthly) {
            return true;
        }

        // Check current usage against new package limits
        $activeRouters = $user->routers()->count();
        $activeZones = $user->zones()->count();

        // Prevent downgrade if exceeds router limit
        if ($activeRouters > $package->max_routers) {
            $excess = $activeRouters - $package->max_routers;
            $this->error("Cannot downgrade: You have {$activeRouters} routers but the {$package->name} package allows only {$package->max_routers}. Please delete {$excess} router(s) first.");
            return false;
        }

        // Prevent downgrade if exceeds zone limit (if package has a limit)
        if ($package->max_zones && $activeZones > $package->max_zones) {
            $excess = $activeZones - $package->max_zones;
            $this->error("Cannot downgrade: You have {$activeZones} zones but the {$package->name} package allows only {$package->max_zones}. Please delete {$excess} zone(s) first.");
            return false;
        }

        return true;
    }

    public function subscribe(): void
    {
        if (!$this->selectedPackageId) {
            $this->error('Please select a package.');
            return;
        }

        try {
            $user = Auth::user();
            $package = Package::findOrFail($this->selectedPackageId);

            // Double-check downgrade validation before processing
            if (!$this->canDowngradeToPackage($user, $package)) {
                $this->showSubscribeModal = false;
                return;
            }

            $amount = $this->selectedCycle === 'yearly'
                ? ($package->price_yearly ?? $package->price_monthly * 12)
                : $package->price_monthly;

            // Check if user has sufficient balance
            if ($user->balance < $amount) {
                $this->error('Insufficient balance. Please add funds to your wallet first.');
                $this->showSubscribeModal = false;
                return;
            }

            DB::transaction(function () use ($user, $package) {
                // Cancel existing active subscription if any
                if ($currentSubscription = $user->activeSubscription()) {
                    $currentSubscription->update(['status' => 'cancelled']);
                }

                // Subscribe to new package
                $user->subscribeToPackage($package, $this->selectedCycle);
            });

            $this->success("Successfully subscribed to {$package->name} package!");
            $this->showSubscribeModal = false;
            $this->selectedPackageId = null;
        } catch (\Exception $e) {
            $this->error('Failed to subscribe: ' . $e->getMessage());
        }
    }

    public function cancelSubscription(): void
    {
        try {
            $user = Auth::user();
            $subscription = $user->activeSubscription();

            if (!$subscription) {
                $this->error('No active subscription found.');
                return;
            }

            $subscription->update(['status' => 'cancelled']);
            $this->success('Subscription cancelled successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        $user = Auth::user();
        $currentSubscription = $user->activeSubscription();
        $packages = Package::where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        return view('livewire.subscription.index', [
            'currentSubscription' => $currentSubscription,
            'packages' => $packages,
            'balance' => $user->balance,
        ])->title('My Subscription');
    }
}
