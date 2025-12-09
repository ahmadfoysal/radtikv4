<?php

namespace App\Gateway\Contracts;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface PaymentGatewayContract
{
    /**
     * Create a payment with the external service
     *
     * @param User $user The user initiating the payment
     * @param float $amount The amount to charge
     * @param array $meta Additional metadata
     * @return RedirectResponse|string Redirect to payment page or payment URL
     */
    public function createPayment(User $user, float $amount, array $meta = []): RedirectResponse|string;

    /**
     * Handle the callback/webhook from the payment gateway
     *
     * @param Request $request The callback request
     * @return void
     */
    public function handleCallback(Request $request): void;
}
