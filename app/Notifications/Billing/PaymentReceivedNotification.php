<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
        public float $amount,
        public float $balanceAfter
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
        if ($prefs?->email_enabled && $prefs?->payment_received) {
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
        $formattedAmount = number_format($this->amount, 2);
        $formattedBalance = number_format($this->balanceAfter, 2);

        return (new MailMessage)
            ->subject('Payment Received - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("We have received your payment successfully.")
            ->line("**Payment Details:**")
            ->line("Amount Received: ৳{$formattedAmount}")
            ->line("New Balance: ৳{$formattedBalance}")
            ->line("Transaction ID: {$this->invoice->transaction_id}")
            ->line("Payment Method: " . ucfirst($this->invoice->paymentGateway?->name ?? 'N/A'))
            ->action('View Invoice', route('billing.invoices'))
            ->line('Thank you for your payment!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'title' => 'Payment Received',
            'message' => "Payment of ৳" . number_format($this->amount, 2) . " received successfully",
            'amount' => $this->amount,
            'balance_after' => $this->balanceAfter,
            'invoice_id' => $this->invoice->id,
            'transaction_id' => $this->invoice->transaction_id,
            'icon' => 'o-check-circle',
            'color' => 'success',
            'action_url' => route('billing.invoices'),
            'action_label' => 'View Invoice',
        ];
    }
}
