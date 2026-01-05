<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AllNotifications extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, unread, read
    public $selectedNotifications = [];
    public $selectAll = false;

    protected $queryString = ['filter'];

    public function mount(): void
    {
        // No specific authorization needed - users can see their own notifications
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
        $this->selectedNotifications = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedNotifications = $this->getNotifications()
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedNotifications = [];
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
            $this->dispatch('notificationRead');
        }
    }

    public function markAsUnread(string $notificationId): void
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification && $notification->read_at) {
            $notification->forceFill(['read_at' => null])->save();
            $this->dispatch('notificationRead');
        }
    }

    public function markSelectedAsRead(): void
    {
        if (empty($this->selectedNotifications)) {
            return;
        }

        Auth::user()->notifications()
            ->whereIn('id', $this->selectedNotifications)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->selectedNotifications = [];
        $this->selectAll = false;
        $this->dispatch('notificationRead');
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->selectedNotifications = [];
        $this->selectAll = false;
        $this->dispatch('notificationRead');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedNotifications)) {
            return;
        }

        Auth::user()->notifications()
            ->whereIn('id', $this->selectedNotifications)
            ->delete();

        $this->selectedNotifications = [];
        $this->selectAll = false;
        $this->dispatch('notificationRead');
    }

    private function getNotifications()
    {
        $query = Auth::user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->latest()->get();
    }

    public function render()
    {
        $query = Auth::user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate(20);

        // Transform notifications for display
        $notifications->getCollection()->transform(function ($notification) {
            $notification->subject = $this->getNotificationSubject($notification);
            $notification->description = $this->getNotificationDescription($notification);
            $notification->icon = $this->getNotificationIcon($notification);
            $notification->color = $this->getNotificationColor($notification);
            return $notification;
        });

        $stats = [
            'total' => Auth::user()->notifications()->count(),
            'unread' => Auth::user()->unreadNotifications()->count(),
            'read' => Auth::user()->notifications()->whereNotNull('read_at')->count(),
        ];

        return view('livewire.components.all-notifications', [
            'notifications' => $notifications,
            'stats' => $stats,
        ]);
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

    private function getNotificationDescription($notification): string
    {
        $data = $notification->data;
        $type = class_basename($notification->type);

        return match ($type) {
            'PaymentReceivedNotification' => "Your payment of ৳{$data['amount']} has been received successfully. Your new balance is ৳{$data['balance_after']}. Invoice: #{$data['invoice_number']}",
            'InvoiceGeneratedNotification' => "Invoice #{$data['invoice_number']} has been generated for ৳{$data['amount']}. Due date: {$data['due_date']}",
            'SubscriptionRenewalNotification' => "Your {$data['package_name']} subscription has been " . ($data['is_auto_renewal'] ? 'automatically renewed' : 'renewed') . ". Valid until: {$data['valid_until']}",
            default => $notification->data['message'] ?? 'You have a new notification',
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
}
