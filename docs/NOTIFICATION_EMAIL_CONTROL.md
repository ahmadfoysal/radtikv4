# Notification Email Control System

## Overview

All billing notifications now send to **database only by default**. Email notifications are **opt-in** via an optional parameter.

## Changes Made

### Updated Notification Classes

All three billing notification classes have been updated:

1. **PaymentReceivedNotification**
2. **SubscriptionRenewalNotification**
3. **InvoiceGeneratedNotification**

### What Changed

#### Before (Used Preferences)

```php
public function via(object $notifiable): array
{
    $channels = ['database'];

    // Checked user preferences and added email
    if ($prefs?->email_enabled && $prefs?->payment_received) {
        $channels[] = 'mail';
    }

    return $channels;
}
```

#### After (Explicit Control)

```php
public function __construct(
    public Invoice $invoice,
    public bool $sendEmail = false  // New optional parameter
) {}

public function via(object $notifiable): array
{
    $channels = ['database'];

    // Only send email if explicitly requested
    if ($this->sendEmail) {
        $channels[] = 'mail';
    }

    return $channels;
}
```

## Usage

### Default Behavior (Database Only)

All current notification calls continue to work without changes:

```php
// Payment notification - database only
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $amount,
    $balanceAfter
));

// Subscription notification - database only
$user->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    false // isAutoRenewal
));

// Invoice notification - database only
$user->notify(new InvoiceGeneratedNotification($invoice));
```

### With Email (Opt-in)

To send email, pass `true` as the last parameter:

```php
// Payment notification with email
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $amount,
    $balanceAfter,
    true  // Send email
));

// Subscription notification with email
$user->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    false, // isAutoRenewal
    true   // Send email
));

// Invoice notification with email
$user->notify(new InvoiceGeneratedNotification(
    $invoice,
    true  // Send email
));
```

## Current Notification Call Sites

### 1. CryptomusGateway (Payment Processing)

**File**: `app/Gateway/CryptomusGateway.php`

```php
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after
    // No email by default
));
```

### 2. PayStationGateway (Payment Processing)

**File**: `app/Gateway/PayStationGateway.php`

```php
$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after
    // No email by default
));
```

### 3. AutoRenewSubscriptions Command

**File**: `app/Console/Commands/AutoRenewSubscriptions.php`

```php
$user->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    true // Auto-renewal
    // No email by default
));
```

### 4. User Model (Manual Subscription)

**File**: `app/Models/User.php`

```php
$this->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    false // Not auto-renewal
    // No email by default
));
```

## Benefits

### 1. **Reduced Email Spam**

-   Users won't receive unnecessary emails
-   Only database notifications by default
-   Better user experience

### 2. **Explicit Control**

-   Developers choose when to send emails
-   No hidden behavior based on user preferences
-   Clear and predictable

### 3. **Backwards Compatible**

-   All existing calls continue to work
-   No changes needed to current code
-   Optional parameter defaults to `false`

### 4. **Flexible**

-   Easy to add email for specific scenarios
-   Can be controlled per notification call
-   Simple conditional logic:

```php
// Example: Only send email for large payments
$sendEmail = $amount >= 1000;

$user->notify(new PaymentReceivedNotification(
    $invoice,
    $amount,
    $balanceAfter,
    $sendEmail
));
```

## When to Send Email

Consider sending email notifications for:

✅ **High-value transactions** (e.g., payments over 1000 BDT)
✅ **Critical actions** (e.g., subscription cancellations)
✅ **Security events** (e.g., password changes)
✅ **Important reminders** (e.g., expiring subscriptions)

❌ **Avoid for**:

-   Routine small payments
-   Frequent recurring actions
-   Minor balance updates
-   Regular status changes

## Examples

### Conditional Email Based on Amount

```php
// Send email only for payments over 500 BDT
$sendEmail = $invoice->amount >= 500;

$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after,
    $sendEmail
));
```

### Email for First Subscription Only

```php
$isFirstSubscription = $user->subscriptions()->count() === 0;

$user->notify(new SubscriptionRenewalNotification(
    $subscription,
    $invoice,
    false,
    $isFirstSubscription  // Email for first subscription
));
```

### Email Based on User Preference

```php
// Check if user wants email notifications
$sendEmail = $user->notificationPreferences?->email_enabled ?? false;

$user->notify(new PaymentReceivedNotification(
    $invoice,
    $invoice->amount,
    $invoice->balance_after,
    $sendEmail
));
```

## Migration Notes

### No Migration Required

-   All existing code continues to work
-   Default behavior is database-only
-   No breaking changes

### If You Want Email

Just add `true` as the last parameter to any notification call.

### Notification Preferences Table

The `notification_preferences` table is still available but not automatically checked. You can manually check preferences if needed:

```php
$prefs = $user->notificationPreferences;
$sendEmail = $prefs?->email_enabled && $prefs?->payment_received;

$user->notify(new PaymentReceivedNotification(
    $invoice,
    $amount,
    $balance,
    $sendEmail  // Respects user preferences if you want
));
```

## Testing

### Test Database Notifications

```php
// Send notification
$user->notify(new PaymentReceivedNotification($invoice, 100, 200));

// Check it was stored in database
expect($user->notifications()->count())->toBe(1);
expect($user->unreadNotifications()->count())->toBe(1);
```

### Test Email Notifications

```php
Notification::fake();

// Send with email
$user->notify(new PaymentReceivedNotification($invoice, 100, 200, true));

// Assert email was queued
Notification::assertSentTo($user, PaymentReceivedNotification::class);
```

## Summary

-   ✅ All notifications default to **database only**
-   ✅ Add optional `true` parameter to **send email**
-   ✅ **No breaking changes** to existing code
-   ✅ Clear and **explicit control** over email sending
-   ✅ More **flexible** than user preferences
-   ✅ Better **user experience** (less email spam)

## Files Modified

1. `app/Notifications/Billing/PaymentReceivedNotification.php`
2. `app/Notifications/Billing/SubscriptionRenewalNotification.php`
3. `app/Notifications/Billing/InvoiceGeneratedNotification.php`

## Current Status

All billing notifications in the system:

-   ✅ Send to database (visible in UI)
-   ❌ Do NOT send email (unless explicitly enabled)
-   ✅ Ready for selective email activation when needed
