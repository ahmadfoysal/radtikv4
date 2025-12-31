<?php

namespace App\Exceptions;

use Exception;

class SubscriptionExpiredException extends Exception
{
    protected $daysRemaining;
    protected $gracePeriodActive;

    public function __construct(string $message = "Subscription expired", int $daysRemaining = 0, bool $gracePeriodActive = false)
    {
        parent::__construct($message);
        $this->daysRemaining = $daysRemaining;
        $this->gracePeriodActive = $gracePeriodActive;
    }

    public function getDaysRemaining(): int
    {
        return $this->daysRemaining;
    }

    public function isGracePeriodActive(): bool
    {
        return $this->gracePeriodActive;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Subscription expired',
                'message' => $this->getMessage(),
                'days_remaining' => $this->daysRemaining,
                'grace_period_active' => $this->gracePeriodActive,
                'redirect' => route('subscription.index')
            ], 403);
        }

        return redirect()
            ->route('subscription.index')
            ->with('error', $this->getMessage());
    }
}
