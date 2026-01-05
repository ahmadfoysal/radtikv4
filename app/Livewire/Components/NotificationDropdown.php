<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public $unreadCount = 0;
    public $notifications = [];
    public $showDropdown = false;

    protected $listeners = ['notificationRead' => 'refreshNotifications'];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = Auth::user();

        // Get unread count
        $this->unreadCount = $user->unreadNotifications()->count();

        // Get latest 5 notifications (mix of read and unread)
        $this->notifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'subject' => $this->getNotificationSubject($notification),
                    'short_description' => $this->getNotificationShortDescription($notification),
                    'icon' => $this->getNotificationIcon($notification),
                    'color' => $this->getNotificationColor($notification),
                ];
            })
            ->toArray();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;

        if ($this->showDropdown) {
            $this->loadNotifications();
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
            $this->loadNotifications();
            $this->dispatch('notificationRead');
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
        $this->dispatch('notificationRead');
    }

    private function getNotificationSubject($notification): string
    {
        $type = class_basename($notification->type);

        return match ($type) {
            'PaymentReceivedNotification' => 'Payment Received',
            'InvoiceGeneratedNotification' => 'Invoice Generated',
            'SubscriptionRenewalNotification' => 'Subscription Renewed',
            default => 'Notification',
        };
    }

    private function getNotificationShortDescription($notification): string
    {
        $data = $notification->data;
        $type = class_basename($notification->type);

        return match ($type) {
            'PaymentReceivedNotification' => "Payment of ৳{$data['amount']} received successfully",
            'InvoiceGeneratedNotification' => "Invoice #{$data['invoice_number']} for ৳{$data['amount']}",
            'SubscriptionRenewalNotification' => "{$data['package_name']} subscription renewed",
            default => 'You have a new notification',
        };
    }

    private function getNotificationIcon($notification): string
    {
        $type = class_basename($notification->type);

        return match ($type) {
            'PaymentReceivedNotification' => 'o-banknotes',
            'InvoiceGeneratedNotification' => 'o-document-text',
            'SubscriptionRenewalNotification' => 'o-arrow-path',
            default => 'o-bell',
        };
    }

    private function getNotificationColor($notification): string
    {
        $type = class_basename($notification->type);

        return match ($type) {
            'PaymentReceivedNotification' => 'success',
            'InvoiceGeneratedNotification' => 'info',
            'SubscriptionRenewalNotification' => 'warning',
            default => 'primary',
        };
    }

    public function render()
    {
        return view('livewire.components.notification-dropdown');
    }
}
