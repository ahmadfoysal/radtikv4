<?php

namespace App\Livewire\Admin\CustomerManagement;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public User $customer;

    public function mount(User $customer): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403, 'Access denied. Superadmin only.');
        abort_unless($customer->hasRole('admin'), 404, 'Customer not found.');

        $this->customer = $customer->load([
            'roles',
            'routers',
            'subscriptions' => function ($query) {
                $query->latest();
            },
            'invoices' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);
    }

    public function render()
    {
        $activeSubscription = $this->customer->subscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();

        $stats = [
            'total_routers' => $this->customer->routers()->count(),
            'active_routers' => $this->customer->routers()->count(),
            'total_subscriptions' => $this->customer->subscriptions()->count(),
            'total_invoices' => $this->customer->invoices()->count(),
            'total_spent' => $this->customer->invoices()
                ->where('type', 'debit')
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        return view('livewire.admin.customer-management.show', [
            'activeSubscription' => $activeSubscription,
            'stats' => $stats,
        ]);
    }
}
