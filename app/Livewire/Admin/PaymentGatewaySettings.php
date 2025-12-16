<?php

namespace App\Livewire\Admin;

use App\Models\PaymentGateway;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mary\Traits\Toast;

class PaymentGatewaySettings extends Component
{
    use Toast;

    public $gateways = [];

    public function mount(): void
    {
        // Check if user is superadmin only
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

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
        try {
            $gateway = PaymentGateway::findOrFail($gatewayId);
            $gateway->is_active = ! $gateway->is_active;
            $gateway->save();

            $this->loadGateways();
            $this->success($gateway->name . ' ' . ($gateway->is_active ? 'activated' : 'deactivated') . ' successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to update gateway status: ' . $e->getMessage());
        }
    }

    public function saveCredentials(int $gatewayId): void
    {
        try {
            $gateway = PaymentGateway::findOrFail($gatewayId);

            // Find the gateway in the local array to get updated data
            $gatewayData = collect($this->gateways)->firstWhere('id', $gatewayId);

            if (!$gatewayData) {
                $this->error('Gateway not found.');
                return;
            }

            // Validate that required fields are not empty for the gateway
            $data = $gatewayData['data'] ?? [];
            $isEmpty = true;
            foreach ($data as $value) {
                if (!empty($value)) {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                $this->warning('Please fill in at least one credential field before saving.');
                return;
            }

            $gateway->data = $data;
            $gateway->save();

            $this->loadGateways();
            $this->success($gateway->name . ' credentials updated successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to save credentials: ' . $e->getMessage());
        }
    }
}
