<?php

namespace App\Livewire\Package;

use App\Models\Package;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class Create extends Component
{
    use Toast;

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

    public function save(): void
    {
        $this->validate();

        Package::create([
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
            description: 'Package created successfully.'
        );

        $this->redirect(route('packages.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('packages.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.package.create');
    }
}
