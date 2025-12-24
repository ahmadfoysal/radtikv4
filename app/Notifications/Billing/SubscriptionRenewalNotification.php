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
        public Invoice $invoice,
        public bool $isAutoRenewal = false
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Check user's notification preferences
        $prefs = $notifiable->notificationPreferences;

        // Add email if user has email notifications enabled
        if ($prefs?->email_enabled && $prefs?->subscription_renewal) {
            $channels[] = 'mail';
        } elseif (!$prefs && $notifiable->email_notifications) {
            // Fallback to user's general email notification setting
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $formattedAmount = number_format($this->invoice->amount, 2);
        $formattedBalance = number_format($this->invoice->balance_after, 2);
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

        return [
            'type' => 'subscription_renewal',
            'title' => $this->isAutoRenewal ? 'Subscription Auto-Renewed' : 'Subscription Renewed',
            'message' => "{$packageName} renewed for ৳" . number_format($this->invoice->amount, 2),
            'subscription_id' => $this->subscription->id,
            'invoice_id' => $this->invoice->id,
            'package_name' => $packageName,
            'amount' => $this->invoice->amount,
            'balance_after' => $this->invoice->balance_after,
            'billing_cycle' => $this->subscription->billing_cycle,
            'end_date' => $this->subscription->end_date,
            'is_auto_renewal' => $this->isAutoRenewal,
            'icon' => 'o-arrow-path',
            'color' => 'info',
            'action_url' => route('subscription.index'),
            'action_label' => 'View Subscription',
        ];
    }
}
