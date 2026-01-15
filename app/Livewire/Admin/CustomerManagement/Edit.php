<?php

namespace App\Livewire\Admin\CustomerManagement;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Mary\Traits\Toast;

class Edit extends Component
{
    use AuthorizesRequests, Toast;

    public User $customer;

    #[Rule(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Rule(['required', 'email', 'max:255'])]
    public string $email = '';

    #[Rule(['nullable', 'string', 'min:8'])]
    public ?string $password = null;

    #[Rule(['nullable', 'string', 'max:20'])]
    public ?string $phone = null;

    #[Rule(['nullable', 'string', 'max:500'])]
    public ?string $address = null;

    #[Rule(['nullable', 'string', 'max:100'])]
    public ?string $country = null;

    #[Rule(['required', 'numeric', 'min:0', 'max:100'])]
    public float $commission = 0;

    #[Rule(['nullable', 'string', 'max:500'])]
    public string $suspension_reason = '';

    public bool $showSuspendModal = false;

    #[Rule(['required', 'boolean'])]
    public bool $is_active = true;

    #[Rule(['nullable', 'date'])]
    public ?string $expiration_date = null;

    public bool $email_notifications = true;
    public bool $login_alerts = true;

    public function mount(User $customer): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403, 'Access denied. Superadmin only.');
        abort_unless($customer->hasRole('admin'), 404, 'Customer not found.');

        $this->customer = $customer;
        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
        $this->country = $customer->country ?? '';
        $this->commission = (float) $customer->commission;
        $this->is_active = (bool) $customer->is_active;
        $this->expiration_date = $customer->expiration_date?->format('Y-m-d');
        $this->email_notifications = (bool) $customer->email_notifications;
        $this->login_alerts = (bool) $customer->login_alerts;
    }

    public function render()
    {
        return view('livewire.admin.customer-management.edit');
    }

    public function save()
    {
        // Custom email validation to exclude current user
        $this->validate([
            'email' => "required|email|max:255|unique:users,email,{$this->customer->id}",
        ]);

        $this->validate();

        $this->customer->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'country' => $this->country,
            'commission' => $this->commission,
            'is_active' => $this->is_active,
            'expiration_date' => $this->expiration_date,
            'email_notifications' => $this->email_notifications,
            'login_alerts' => $this->login_alerts,
        ]);

        // Update password if provided
        if ($this->password) {
            $this->customer->update([
                'password' => Hash::make($this->password),
            ]);
        }

        $this->success('Customer updated successfully!');

        return $this->redirect(route('customers.show', $this->customer), navigate: true);
    }

    public function openSuspendModal()
    {
        $this->suspension_reason = '';
        $this->showSuspendModal = true;
    }

    public function suspend()
    {
        $this->validate([
            'suspension_reason' => 'required|string|max:500',
        ]);

        $this->customer->update([
            'suspended_at' => now(),
            'suspension_reason' => $this->suspension_reason,
        ]);

        $this->showSuspendModal = false;
        $this->success('Customer suspended successfully!');

        // Refresh customer data
        $this->customer->refresh();
    }

    public function unsuspend()
    {
        $this->customer->update([
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        $this->success('Customer unsuspended successfully!');

        // Refresh customer data
        $this->customer->refresh();
    }
}
