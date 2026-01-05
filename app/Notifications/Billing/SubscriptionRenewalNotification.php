<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public ?Invoice $invoice = null,
        public bool $isAutoRenewal = false,
        public bool $sendEmail = false
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add email channel only if explicitly requested
        if ($this->sendEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amount = $this->invoice?->amount ?? 0;
        $balanceAfter = $this->invoice?->balance_after ?? $notifiable->balance;
        $formattedAmount = number_format($amount, 2);
        $formattedBalance = number_format($balanceAfter, 2);
        $packageName = $this->subscription->package->name ?? 'Your Package';
        $endDate = Carbon::parse($this->subscription->end_date)->format('M d, Y');

        $subject = $this->isAutoRenewal
            ? 'Subscription Auto-Renewed - ' . config('app.name')
            : 'Subscription Renewed - ' . config('app.name');

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->isAutoRenewal
                ? "Your subscription has been automatically renewed."
                : "Your subscription has been renewed successfully.");

        $message->line("**Subscription Details:**")
            ->line("Package: {$packageName}")
            ->line("Amount Charged: ৳{$formattedAmount}")
            ->line("Billing Cycle: " . ucfirst($this->subscription->billing_cycle))
            ->line("Valid Until: {$endDate}")
            ->line("Remaining Balance: ৳{$formattedBalance}");

        $message->action('View Subscription', route('subscription.index'));

        if ($this->isAutoRenewal) {
            $message->line('This was an automatic renewal. You can disable auto-renewal from your subscription settings.');
        }

        $message->line('Thank you for continuing with us!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $packageName = $this->subscription->package->name ?? 'Package';
        $amount = $this->invoice?->amount ?? 0;
        $isFree = $amount == 0;

        return [
            'type' => 'subscription_renewal',
            'title' => $this->isAutoRenewal ? 'Subscription Auto-Renewed' : 'Subscription Renewed',
            'message' => $isFree
                ? "{$packageName} subscription activated"
                : "{$packageName} renewed for ৳" . number_format($amount, 2),
            'subscription_id' => $this->subscription->id,
            'invoice_id' => $this->invoice?->id,
            'invoice_number' => $this->invoice?->invoice_number ?? 'N/A',
            'package_name' => $packageName,
            'amount' => $amount,
            'balance_after' => $this->invoice?->balance_after ?? $notifiable->balance,
            'billing_cycle' => $this->subscription->billing_cycle,
            'end_date' => $this->subscription->end_date,
            'valid_until' => $this->subscription->end_date?->format('M d, Y'),
            'is_auto_renewal' => $this->isAutoRenewal,
            'is_free' => $isFree,
            'icon' => 'o-arrow-path',
            'color' => 'info',
            'action_url' => route('subscription.index'),
            'action_label' => 'View Subscription',
        ];
    }
}
