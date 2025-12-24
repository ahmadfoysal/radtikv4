<?php

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice
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
        if ($prefs?->email_enabled) {
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
        $typeLabel = $this->invoice->type === 'debit' ? 'charged from' : 'added to';

        $message = (new MailMessage)
            ->subject('New Invoice Generated - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("A new invoice has been generated for your account.");

        $message->line("**Invoice Details:**")
            ->line("Type: " . ucfirst($this->invoice->type))
            ->line("Category: " . ucfirst(str_replace('_', ' ', $this->invoice->category)))
            ->line("Amount: ৳{$formattedAmount} {$typeLabel} your account")
            ->line("Balance After Transaction: ৳{$formattedBalance}");

        if ($this->invoice->description) {
            $message->line("Description: {$this->invoice->description}");
        }

        if ($this->invoice->router) {
            $message->line("Router: {$this->invoice->router->name}");
        }

        $message->action('View Invoice', route('billing.invoices'))
            ->line('If you have any questions, please contact our support team.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $icon = $this->invoice->type === 'credit' ? 'o-arrow-trending-up' : 'o-arrow-trending-down';
        $color = $this->invoice->type === 'credit' ? 'success' : 'warning';

        return [
            'type' => 'invoice_generated',
            'title' => 'Invoice Generated',
            'message' => ucfirst($this->invoice->type) . ' invoice of ৳' . number_format($this->invoice->amount, 2) . ' generated',
            'invoice_id' => $this->invoice->id,
            'invoice_type' => $this->invoice->type,
            'category' => $this->invoice->category,
            'amount' => $this->invoice->amount,
            'balance_after' => $this->invoice->balance_after,
            'icon' => $icon,
            'color' => $color,
            'action_url' => route('billing.invoices'),
            'action_label' => 'View Details',
        ];
    }
}
