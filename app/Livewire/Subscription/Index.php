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
        $activeResellers = $user->reseller()->count();

        // Get max vouchers across all routers
        $maxVouchersInUse = 0;
        foreach ($user->routers as $router) {
            $voucherCount = $router->vouchers()->count();
            if ($voucherCount > $maxVouchersInUse) {
                $maxVouchersInUse = $voucherCount;
            }
        }

        // Prevent downgrade if exceeds router limit
        if ($activeRouters > $package->max_routers) {
            $excess = $activeRouters - $package->max_routers;
            $this->error("Cannot downgrade: You have {$activeRouters} routers but the {$package->name} package allows only {$package->max_routers}. Please delete {$excess} router(s) first.");
            return false;
        }

        // Prevent downgrade if exceeds zone limit
        if ($package->max_zones && $activeZones > $package->max_zones) {
            $excess = $activeZones - $package->max_zones;
            $this->error("Cannot downgrade: You have {$activeZones} zones but the {$package->name} package allows only {$package->max_zones}. Please delete {$excess} zone(s) first.");
            return false;
        }

        // Prevent downgrade if exceeds reseller/user limit
        if ($package->max_users && $activeResellers > $package->max_users) {
            $excess = $activeResellers - $package->max_users;
            $this->error("Cannot downgrade: You have {$activeResellers} resellers but the {$package->name} package allows only {$package->max_users}. Please remove {$excess} reseller(s) first.");
            return false;
        }

        // Prevent downgrade if exceeds voucher per router limit
        if ($package->max_vouchers_per_router && $maxVouchersInUse > $package->max_vouchers_per_router) {
            $excess = $maxVouchersInUse - $package->max_vouchers_per_router;
            $this->error("Cannot downgrade: You have {$maxVouchersInUse} vouchers in one router but the {$package->name} package allows only {$package->max_vouchers_per_router} per router. Please delete {$excess} voucher(s) from your routers first.");
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

            // Apply commission discount for admin users
            $discount = 0;
            if ($amount > 0 && $user->hasRole('admin') && $user->commission > 0) {
                $discount = round(($amount * $user->commission) / 100, 2);
            }

            $finalAmount = $amount - $discount;

            // Check if user has sufficient balance for the final amount (after discount)
            if ($user->balance < $finalAmount) {
                $this->error('Insufficient balance. Please add funds to your wallet first.');
                $this->showSubscribeModal = false;
                return;
            }

            DB::transaction(function () use ($user, $package) {
                // Cancel existing active subscription if any
                if ($currentSubscription = $user->activeSubscription()) {
                    $currentSubscription->cancel('Switched to ' . $package->name . ' package');
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
        $daysLeft = $currentSubscription ? $currentSubscription->end_date->diffInDays(now(), false) : null;
        $inGrace = $currentSubscription && $currentSubscription->status === 'grace_period';
        $graceDays = $currentSubscription && $currentSubscription->package ? $currentSubscription->package->grace_period_days : 0;
        $packages = Package::where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        // Subscription expiry alerts
        $subscriptionAlert = null;
        if ($currentSubscription) {
            $now = now();
            $endDate = $currentSubscription->end_date;
            $gracePeriodDays = $currentSubscription->package->grace_period_days ?? 0;

            // Check if past end_date (expired)
            if ($now->gt($endDate)) {
                // Calculate grace period end date
                $gracePeriodEndDate = $endDate->copy()->addDays($gracePeriodDays);

                // Check if still within grace period
                if ($now->lte($gracePeriodEndDate)) {
                    // In grace period - show remaining days until grace period ends
                    $graceRemaining = (int) $now->diffInDays($gracePeriodEndDate);

                    $subscriptionAlert = [
                        'type' => 'error',
                        'message' => "Your subscription has expired. Please renew within {$graceRemaining} day" . ($graceRemaining != 1 ? 's' : '') . " to avoid service interruption.",
                        'daysLeft' => $graceRemaining,
                        'gracePeriod' => true
                    ];
                }
            } elseif ($endDate->isFuture()) {
                // Active subscription - check if expiring within 7 days
                $daysUntilExpiry = (int) $now->diffInDays($endDate);

                if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
                    $subscriptionAlert = [
                        'type' => 'warning',
                        'message' => "Your subscription will expire in {$daysUntilExpiry} day" . ($daysUntilExpiry > 1 ? 's' : '') . ". Please renew before expiry for smooth operation.",
                        'daysLeft' => $daysUntilExpiry,
                        'gracePeriod' => false
                    ];
                }
            }
        }

        return view('livewire.subscription.index', [
            'currentSubscription' => $currentSubscription,
            'packages' => $packages,
            'balance' => $user->balance,
            'daysLeft' => $daysLeft,
            'inGrace' => $inGrace,
            'graceDays' => $graceDays,
            'subscriptionAlert' => $subscriptionAlert,
        ])->title('My Subscription');
    }
}
