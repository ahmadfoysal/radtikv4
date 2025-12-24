# Notification System Implementation

## Overview

The RADTik v4 notification system provides multi-channel notifications for billing events, enabling users to stay informed about payments, invoices, and subscription renewals.

## Features Implemented

### 1. Notification Classes

Located in `app/Notifications/Billing/`:

-   **PaymentReceivedNotification**: Sent when payment is successfully processed via gateway
-   **InvoiceGeneratedNotification**: Sent when any invoice is created
-   **SubscriptionRenewalNotification**: Sent when subscription is renewed or created

### 2. Notification Channels

-   **Database**: In-app notifications (always enabled by default)
-   **Email**: Optional email notifications based on user preferences

### 3. User Preferences

The `notification_preferences` table stores per-user settings:

```php
$user->notificationPreferences->email_enabled        // Enable/disable email
$user->notificationPreferences->payment_received     // Toggle payment notifications
$user->notificationPreferences->subscription_renewal // Toggle subscription notifications
$user->notificationPreferences->invoice_generated    // Toggle invoice notifications
```

### 4. Database Schema

#### notifications table (Laravel built-in)

-   Stores in-app notification data
-   Contains JSON data with notification details

#### notification_preferences table

```sql
- user_id (FK to users)
- email_enabled (boolean, default: true)
- database_enabled (boolean, default: true)
- router_offline (boolean, default: true)
- voucher_expiring (boolean, default: true)
- low_balance (boolean, default: true)
- payment_received (boolean, default: true)
- subscription_renewal (boolean, default: true)
- invoice_generated (boolean, default: true)
- low_balance_threshold (decimal, default: 100)
- voucher_expiry_days (integer, default: 7)
```

## Integration Points

### Payment Gateways

Notifications are sent after successful payment processing:

**Cryptomus Gateway** (`app/Gateway/CryptomusGateway.php`):

```php
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after
));
```

**PayStation Gateway** (`app/Gateway/PayStationGateway.php`):

```php
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after
));
```

### Subscription Management

Notifications are sent when subscribing to a package (`app/Models/User.php`):

```php
$user->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    false // isAutoRenewal
));
```

## Email Templates

Email notifications use Laravel's MailMessage format with:

-   Professional greeting with user name
-   Formatted currency amounts (৳)
-   Transaction details
-   Action buttons linking to relevant pages
-   Footer with support information

### Email Preview

**Payment Received Email**:

-   Subject: "Payment Received - RADTik"
-   Content: Payment amount, new balance, transaction ID
-   Action: "View Invoice" button

**Subscription Renewal Email**:

-   Subject: "Subscription Renewed - RADTik"
-   Content: Package name, billing cycle, valid until date
-   Action: "View Subscription" button

## Notification Data Structure

### Database Notification Payload

```json
{
    "type": "payment_received",
    "title": "Payment Received",
    "message": "Payment of ৳500.00 received successfully",
    "amount": 500.0,
    "balance_after": 1500.0,
    "invoice_id": 123,
    "transaction_id": "TXN123456",
    "icon": "o-check-circle",
    "color": "success",
    "action_url": "/billing/invoices",
    "action_label": "View Invoice"
}
```

## Usage Examples

### Accessing User Notifications

```php
// Get unread notifications
$unreadCount = auth()->user()->unreadNotifications()->count();

// Get all notifications
$notifications = auth()->user()->notifications;

// Mark as read
auth()->user()->unreadNotifications->markAsRead();

// Delete notification
$notification->delete();
```

### Creating Notification Preferences

```php
use App\Models\NotificationPreference;

// Create default preferences for user
$preferences = NotificationPreference::createDefault($user);

// Update specific preference
$user->notificationPreferences->update([
    'email_enabled' => false,
    'low_balance_threshold' => 50.00
]);
```

### Manual Notification Sending

```php
use App\Notifications\Billing\PaymentReceivedNotification;

$user->notify(new PaymentReceivedNotification(
    invoice: $invoice,
    amount: 100.00,
    balanceAfter: 500.00
));
```

## Queue Configuration

All notifications implement `ShouldQueue` interface for async processing:

```env
QUEUE_CONNECTION=database
```

Run queue worker:

```bash
php artisan queue:work
```

## Testing

```php
// Test notification sending
Notification::fake();

// Perform action that triggers notification
$user->credit(100, 'payment_gateway', 'Test payment');

// Assert notification was sent
Notification::assertSentTo($user, PaymentReceivedNotification::class);
```

## Future Enhancements

1. **Router Monitoring**

    - Router offline/online notifications
    - Scheduled status checks every 5 minutes

2. **Voucher Management**

    - Expiring voucher notifications
    - Low voucher stock alerts

3. **Balance Alerts**

    - Low balance warnings
    - Configurable threshold per user

4. **UI Components**

    - Notification bell in navbar
    - Notification dropdown list
    - Notification settings page

5. **Additional Channels**
    - SMS notifications via Twilio
    - Push notifications via FCM
    - Slack/Discord webhooks

## Related Files

-   Notification Classes: `app/Notifications/Billing/`
-   Models: `app/Models/NotificationPreference.php`
-   Migrations: `database/migrations/*_create_notification*`
-   Gateways: `app/Gateway/CryptomusGateway.php`, `PayStationGateway.php`
-   User Model: `app/Models/User.php` (subscribeToPackage method)

## Support

For questions or issues with the notification system, refer to:

-   Laravel Notifications Documentation: https://laravel.com/docs/11.x/notifications
-   Queue Documentation: https://laravel.com/docs/11.x/queues
