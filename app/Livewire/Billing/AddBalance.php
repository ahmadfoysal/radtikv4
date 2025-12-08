<?php

namespace App\Livewire\Billing;

use App\Models\PaymentGateway;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class AddBalance extends Component
{
    use Toast;

    public ?float $amount = null;

    public ?int $payment_gateway_id = null;

    public function mount(): void
    {
        // Component initialization
    }

    public function render(): View
    {
        $gateways = PaymentGateway::active()
            ->orderBy('name')
            ->get()
            ->map(fn ($gateway) => [
                'id' => $gateway->id,
                'name' => $gateway->name,
            ])
            ->toArray();

        return view('livewire.billing.add-balance', [
            'gateways' => $gateways,
        ]);
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'amount' => 'required|numeric|min:1',
            'payment_gateway_id' => 'required|integer|exists:payment_gateways,id',
        ]);

        try {
            $gateway = PaymentGateway::findOrFail($validated['payment_gateway_id']);

            if (! $gateway->isActive()) {
                $this->error('Selected payment gateway is not available.');
                return;
            }

            // Resolve the gateway handler class from the service container
            $handler = app($gateway->class, ['gateway' => $gateway]);

            // Create payment and get redirect response
            $redirect = $handler->createPayment(
                auth()->user(),
                (float) $validated['amount'],
                [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            );

            // Redirect to payment gateway
            if ($redirect instanceof \Illuminate\Http\RedirectResponse) {
                $this->redirect($redirect->getTargetUrl());
                return;
            }

            // If it's a string URL, redirect to it
            if (is_string($redirect)) {
                $this->redirect($redirect);
                return;
            }
        } catch (\Exception $e) {
            $this->error('Failed to initiate payment: '.$e->getMessage());
        }
    }
}

