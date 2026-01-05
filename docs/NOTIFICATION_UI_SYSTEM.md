# Notification System UI Implementation

## Overview

Complete implementation of a real-time notification system with badge counter, dropdown menu, and dedicated notifications page.

## Features Implemented

### 1. Notification Dropdown in Header

-   **Location**: Top navigation bar (app.blade.php)
-   **Badge**: Shows unread notification count with red badge
-   **Dropdown Menu**: Click to view recent 5 notifications
-   **Auto-refresh**: Updates count on read/unread actions
-   **Styling**: Follows DaisyUI/TailwindCSS design system

### 2. All Notifications Page

-   **Route**: `/notifications` (named route: `notifications.index`)
-   **Features**:
    -   Filter tabs (All, Unread, Read)
    -   Bulk selection with checkboxes
    -   Bulk actions (Mark as Read, Delete)
    -   Individual notification actions
    -   Pagination for large lists
    -   Statistics cards showing total, unread, and read counts

### 3. Notification Types

The system displays three types of billing notifications:

#### Payment Received

-   **Icon**: Banknotes (o-banknotes)
-   **Color**: Success (green)
-   **Subject**: "Payment Received"
-   **Details**: Amount, new balance, invoice number

#### Invoice Generated

-   **Icon**: Document (o-document-text)
-   **Color**: Info (blue)
-   **Subject**: "Invoice Generated"
-   **Details**: Invoice number, amount, due date

#### Subscription Renewal

-   **Icon**: Refresh (o-arrow-path)
-   **Color**: Warning (yellow)
-   **Subject**: "Subscription Renewed"
-   **Details**: Package name, renewal type, validity period

## Components Structure

### Livewire Components

#### NotificationDropdown

**Location**: `app/Livewire/Components/NotificationDropdown.php`

**Methods**:

-   `loadNotifications()`: Fetches latest 5 notifications
-   `toggleDropdown()`: Shows/hides dropdown
-   `markAsRead($id)`: Marks single notification as read
-   `markAllAsRead()`: Marks all notifications as read
-   `getNotificationSubject()`: Returns formatted subject
-   `getNotificationShortDescription()`: Returns brief description
-   `getNotificationIcon()`: Returns appropriate icon
-   `getNotificationColor()`: Returns color theme

**Properties**:

```php
public $unreadCount = 0;          // Number of unread notifications
public $notifications = [];       // Latest notifications array
public $showDropdown = false;     // Dropdown visibility state
```

**Events**:

-   Listens: `notificationRead` - Refreshes notifications
-   Dispatches: `notificationRead` - Notifies other components

#### AllNotifications

**Location**: `app/Livewire/Components/AllNotifications.php`

**Methods**:

-   `markAsRead($id)`: Mark single as read
-   `markAsUnread($id)`: Mark single as unread
-   `markSelectedAsRead()`: Bulk mark as read
-   `markAllAsRead()`: Mark all as read
-   `deleteSelected()`: Bulk delete
-   Notification helper methods (same as dropdown)

**Properties**:

```php
public $filter = 'all';               // Filter: all, unread, read
public $selectedNotifications = [];   // Selected notification IDs
public $selectAll = false;            // Select all checkbox state
```

### Blade Views

#### notification-dropdown.blade.php

**Location**: `resources/views/livewire/components/notification-dropdown.blade.php`

**Features**:

-   Alpine.js dropdown with smooth transitions
-   Unread count badge (shows "99+" if > 99)
-   Notification list with icons and colors
-   "Mark all as read" button
-   "View All Notifications" link
-   Click outside to close

**Styling**:

-   Width: 384px (w-96)
-   Max height: 384px (max-h-96) with scroll
-   Unread items have blue dot indicator
-   Read items have reduced opacity

#### all-notifications.blade.php

**Location**: `resources/views/livewire/components/all-notifications.blade.php`

**Features**:

-   Statistics cards at top
-   Filter tabs (All/Unread/Read)
-   Bulk selection checkbox
-   Bulk action buttons
-   Individual notification cards
-   Dropdown menu per notification
-   Pagination
-   Empty state messages

## Integration

### App Layout Update

**File**: `resources/views/components/layouts/app.blade.php`

```blade
<x-slot:actions>
    {{-- Theme toggle --}}
    <button type="button" class="btn btn-ghost btn-sm" ...>
        ...
    </button>

    {{-- Notification Dropdown --}}
    @auth
        <livewire:components.notification-dropdown />
    @endauth
</x-slot:actions>
```

### Route Registration

**File**: `routes/web.php`

```php
Route::middleware(['auth', 'check.suspended'])->group(function () {
    // ... other routes ...

    // Notifications
    Route::get('/notifications', App\Livewire\Components\AllNotifications::class)
        ->name('notifications.index');
});
```

## Usage

### For End Users

1. **View Notifications**:

    - Click bell icon in header to see recent notifications
    - Badge shows unread count
    - Click "View All Notifications" to see complete list

2. **Mark as Read**:

    - Click on notification in dropdown to mark as read
    - Use "Mark all as read" button for bulk action
    - Individual actions available in all notifications page

3. **Filter Notifications**:
    - Use tabs on notifications page (All/Unread/Read)
    - Select multiple notifications for bulk actions

### For Developers

#### Sending Notifications

Notifications are automatically sent via existing notification classes:

```php
// Payment received
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after
));

// Invoice generated
$user->notify(new InvoiceGeneratedNotification($invoice));

// Subscription renewal
$user->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    $isAutoRenewal
));
```

#### Adding New Notification Types

1. Create notification class in `app/Notifications/`
2. Add to database channel via `via()` method
3. Update helper methods in both components:

```php
// In NotificationDropdown.php and AllNotifications.php
private function getNotificationSubject($notification): string
{
    $type = class_basename($notification->type);

    return match ($type) {
        'PaymentReceivedNotification' => 'Payment Received',
        'NewNotificationType' => 'New Subject', // Add here
        default => 'Notification',
    };
}

// Repeat for getNotificationDescription(), getNotificationIcon(), getNotificationColor()
```

#### Notification Data Structure

Store data in notification's `data` array:

```php
public function toDatabase(object $notifiable): array
{
    return [
        'subject' => 'Notification Subject',
        'message' => 'Short message',
        'amount' => 100.00,
        'invoice_number' => 'INV-001',
        // ... other data
    ];
}
```

## Styling Reference

### Color Mapping

-   Success (green): Payment received
-   Info (blue): Invoices, general information
-   Warning (yellow): Subscription renewals, warnings
-   Primary (purple): Default notifications

### Icons (Heroicons)

-   `o-banknotes`: Payments
-   `o-document-text`: Invoices, documents
-   `o-arrow-path`: Renewals, updates
-   `o-bell`: General notifications
-   `o-bell-slash`: No notifications

### Responsive Behavior

-   Notification dropdown: Fixed width 384px
-   Badge text: Hidden on small screens (`hidden sm:inline`)
-   All notifications page: Responsive grid for stats cards
-   Mobile-friendly dropdowns and tabs

## Best Practices

### Performance

-   Dropdown loads only latest 5 notifications
-   All notifications page uses pagination (20 per page)
-   Queries optimized with proper eager loading
-   Notification data cached in component state

### User Experience

-   Unread notifications clearly indicated (badge, blue dot, highlight)
-   Click on notification auto-marks as read
-   Smooth transitions with Alpine.js
-   Empty states with helpful messages
-   Loading states handled by Livewire

### Security

-   All routes protected by `auth` middleware
-   Users can only see their own notifications
-   CSRF protection on all actions
-   Authorization checks via Laravel's built-in notification system

## Troubleshooting

### Badge Not Updating

-   Ensure `notificationRead` event is dispatched after marking as read
-   Check that component is listening to the event
-   Verify Livewire is properly initialized

### Notifications Not Showing

-   Verify notification was sent to `database` channel
-   Check `notifications` table has records
-   Ensure user is authenticated
-   Check notification preferences if applicable

### Dropdown Not Closing

-   Verify Alpine.js is loaded (`@vite` directive in layout)
-   Check `x-data` and `@click.away` directives
-   Ensure no JavaScript errors in console

### Styling Issues

-   Verify TailwindCSS is compiled (`npm run dev` or `npm run build`)
-   Check DaisyUI theme is properly configured
-   Ensure MaryUI components are installed

## Testing

### Manual Testing Checklist

-   [ ] Badge shows correct unread count
-   [ ] Dropdown opens/closes properly
-   [ ] Clicking notification marks as read
-   [ ] "Mark all as read" works
-   [ ] All notifications page loads
-   [ ] Filters work (All/Unread/Read)
-   [ ] Bulk selection works
-   [ ] Pagination works
-   [ ] Empty states display correctly
-   [ ] Responsive on mobile devices

### Automated Testing

Create tests in `tests/Feature/Components/`:

```php
it('displays unread notification count', function () {
    $user = User::factory()->create();
    $user->notify(new PaymentReceivedNotification(...));

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->assertSee('1'); // Badge shows 1
});

it('marks notification as read when clicked', function () {
    $user = User::factory()->create();
    $user->notify(new PaymentReceivedNotification(...));
    $notification = $user->unreadNotifications->first();

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('markAsRead', $notification->id);

    expect($user->unreadNotifications)->toHaveCount(0);
});
```

## Future Enhancements

Potential improvements:

-   Real-time notifications with WebSockets (Laravel Echo)
-   Sound/desktop notifications
-   Notification preferences per type
-   Group notifications by date
-   Search functionality
-   Mark notification as important/starred
-   Notification categories/tags
-   Archive functionality
-   Export notifications
-   Notification templates

## Dependencies

-   Laravel 12
-   Livewire 3
-   Alpine.js (included with Livewire)
-   MaryUI 2.4
-   TailwindCSS 4.1
-   DaisyUI 5.3
-   Heroicons (via MaryUI)

## Files Created/Modified

### Created:

-   `app/Livewire/Components/NotificationDropdown.php`
-   `app/Livewire/Components/AllNotifications.php`
-   `resources/views/livewire/components/notification-dropdown.blade.php`
-   `resources/views/livewire/components/all-notifications.blade.php`
-   `docs/NOTIFICATION_UI_SYSTEM.md` (this file)

### Modified:

-   `resources/views/components/layouts/app.blade.php`
-   `routes/web.php`

## Support

For issues or questions:

1. Check existing notification system documentation in `NOTIFICATION_SYSTEM.md`
2. Review Laravel notification documentation
3. Check Livewire 3 documentation for component patterns
4. Verify MaryUI component usage
