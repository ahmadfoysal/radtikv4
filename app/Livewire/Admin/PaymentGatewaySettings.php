<?php

namespace App\Livewire\Admin;

use App\Models\PaymentGateway;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class PaymentGatewaySettings extends Component
{
    use Toast;

    public $gateways = [];

    public function mount(): void
    {
        // Check if user is admin/superadmin
        abort_unless(auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin(), 403);

        $this->loadGateways();
    }

    public function render(): View
    {
        return view('livewire.admin.payment-gateway-settings');
    }

    public function loadGateways(): void
    {
        $this->gateways = PaymentGateway::orderBy('name')->get()->map(function ($gateway) {
            return [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'class' => $gateway->class,
                'is_active' => $gateway->is_active,
                'data' => $gateway->data ?? [],
            ];
        })->toArray();
    }

    public function toggleActive(int $gatewayId): void
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $gateway->is_active = ! $gateway->is_active;
        $gateway->save();

        $this->loadGateways();
        $this->success($gateway->name.' '.($gateway->is_active ? 'activated' : 'deactivated').' successfully.');
    }

    public function saveCredentials(int $gatewayId, array $data): void
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $gateway->data = $data;
        $gateway->save();

        $this->loadGateways();
        $this->success($gateway->name.' credentials updated successfully.');
    }
}
