<?php

namespace App\Livewire\Package;

use App\Models\Package;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use Toast;

    public Package $package;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|numeric|min:0')]
    public string $price_monthly = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $price_yearly = null;

    #[Validate('required|integer|min:1')]
    public string $max_routers = '';

    #[Validate('required|integer|min:1')]
    public string $max_users = '';

    #[Validate('nullable|integer|min:0')]
    public ?string $max_zones = null;

    #[Validate('nullable|integer|min:0')]
    public ?string $max_vouchers_per_router = null;

    #[Validate('required|integer|min:1|max:30')]
    public string $grace_period_days = '3';

    #[Validate('nullable|integer|min:0')]
    public ?string $early_pay_days = null;

    #[Validate('nullable|integer|min:0|max:100')]
    public ?string $early_pay_discount_percent = null;

    #[Validate('boolean')]
    public bool $auto_renew_allowed = true;

    #[Validate('nullable|string|max:1000')]
    public ?string $description = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    public function mount(Package $package): void
    {
        $this->package = $package;
        $this->name = $package->name;
        $this->price_monthly = (string) $package->price_monthly;
        $this->price_yearly = $package->price_yearly ? (string) $package->price_yearly : null;
        $this->max_routers = (string) $package->max_routers;
        $this->max_users = (string) $package->max_users;
        $this->max_zones = $package->max_zones ? (string) $package->max_zones : null;
        $this->max_vouchers_per_router = $package->max_vouchers_per_router ? (string) $package->max_vouchers_per_router : null;
        $this->grace_period_days = (string) $package->grace_period_days;
        $this->early_pay_days = $package->early_pay_days ? (string) $package->early_pay_days : null;
        $this->early_pay_discount_percent = $package->early_pay_discount_percent ? (string) $package->early_pay_discount_percent : null;
        $this->auto_renew_allowed = $package->auto_renew_allowed;
        $this->description = $package->description;
        $this->is_active = $package->is_active;
    }

    public function update(): void
    {
        $this->validate();

        $this->package->update([
            'name' => $this->name,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly ?: null,
            'max_routers' => $this->max_routers,
            'max_users' => $this->max_users,
            'max_zones' => $this->max_zones ?: null,
            'max_vouchers_per_router' => $this->max_vouchers_per_router ?: null,
            'grace_period_days' => $this->grace_period_days,
            'early_pay_days' => $this->early_pay_days ?: null,
            'early_pay_discount_percent' => $this->early_pay_discount_percent ?: null,
            'auto_renew_allowed' => $this->auto_renew_allowed,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ]);

        $this->success(
            title: 'Success!',
            description: 'Package updated successfully.'
        );

        $this->redirect(route('packages.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->package->delete();

        $this->success(
            title: 'Deleted!',
            description: 'Package deleted successfully.'
        );

        $this->redirectRoute('packages.index', navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('packages.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.package.edit');
    }
}
