<?php

use App\Livewire\Components\AllNotifications;
use App\Livewire\Components\NotificationDropdown;
use App\Models\User;
use App\Notifications\Billing\PaymentReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test user with role
    $this->user = User::factory()->create();
});

describe('Notification Dropdown Component', function () {
    it('loads without errors', function () {
        actingAs($this->user);

        Livewire::test(NotificationDropdown::class)
            ->assertStatus(200);
    });

    it('displays correct unread count', function () {
        // Send a test notification
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
        ]);

        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));

        actingAs($this->user);

        Livewire::test(NotificationDropdown::class)
            ->assertSet('unreadCount', 1)
            ->assertSee('1'); // Badge should show count
    });

    it('marks notification as read when clicked', function () {
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
        ]);

        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));
        $notification = $this->user->unreadNotifications->first();

        actingAs($this->user);

        Livewire::test(NotificationDropdown::class)
            ->call('markAsRead', $notification->id);

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    it('marks all notifications as read', function () {
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Send multiple notifications
        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));
        $this->user->notify(new PaymentReceivedNotification($invoice, 50.00, 250.00));

        actingAs($this->user);

        Livewire::test(NotificationDropdown::class)
            ->assertSet('unreadCount', 2)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);
    });
});

describe('All Notifications Page Component', function () {
    it('loads without errors', function () {
        actingAs($this->user);

        Livewire::test(AllNotifications::class)
            ->assertStatus(200);
    });

    it('displays notification statistics', function () {
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Send notifications
        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));
        $this->user->notify(new PaymentReceivedNotification($invoice, 50.00, 250.00));

        actingAs($this->user);

        Livewire::test(AllNotifications::class)
            ->assertSee('2') // Total count
            ->assertSee('Unread');
    });

    it('filters notifications by status', function () {
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Send and mark one as read
        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));
        $this->user->notify(new PaymentReceivedNotification($invoice, 50.00, 250.00));
        $this->user->notifications->first()->markAsRead();

        actingAs($this->user);

        // Test unread filter
        Livewire::test(AllNotifications::class)
            ->set('filter', 'unread')
            ->assertSee('1'); // Should show 1 unread

        // Test read filter
        Livewire::test(AllNotifications::class)
            ->set('filter', 'read')
            ->assertSee('1'); // Should show 1 read
    });

    it('can mark selected notifications as read', function () {
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));
        $notification = $this->user->unreadNotifications->first();

        actingAs($this->user);

        Livewire::test(AllNotifications::class)
            ->set('selectedNotifications', [$notification->id])
            ->call('markSelectedAsRead');

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    it('can delete selected notifications', function () {
        $invoice = \App\Models\Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->user->notify(new PaymentReceivedNotification($invoice, 100.00, 200.00));
        $notification = $this->user->notifications->first();

        actingAs($this->user);

        Livewire::test(AllNotifications::class)
            ->set('selectedNotifications', [$notification->id])
            ->call('deleteSelected');

        expect($this->user->notifications()->count())->toBe(0);
    });
});
