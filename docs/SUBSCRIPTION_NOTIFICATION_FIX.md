# Subscription Notification Fix - Testing Guide

## Issues Fixed

### 1. ✅ Free Subscriptions Now Get Notifications

**Problem**: Notifications were only sent when `$finalAmount > 0`, so free packages didn't trigger notifications.

**Solution**: Moved notification call outside the payment block and made invoice parameter nullable.

### 2. ✅ Invoice Made Optional

**Problem**: `SubscriptionRenewalNotification` required an invoice, but free subscriptions don't create invoices.

**Solution**: Made `$invoice` parameter nullable (`?Invoice $invoice = null`) and updated all references to handle null invoices.

### 3. ✅ Notification Now Shows for Both Free and Paid

-   Free subscriptions: "Package subscription activated"
-   Paid subscriptions: "Package renewed for ৳100.00"

## Important: Queue Processing

Notifications implement `ShouldQueue`, which means they're **queued** and processed asynchronously. For notifications to appear in the database, you must:

### Option 1: Run Queue Worker (Recommended for Production)

```bash
# Run in a separate terminal/background process
php artisan queue:work
```

Or use Supervisor/systemd to keep it running automatically.

### Option 2: Process Queue Manually (For Testing)

```bash
# Process all pending jobs
php artisan queue:work --once

# Or process multiple jobs
php artisan queue:work --stop-when-empty
```

### Option 3: Use Sync Queue Driver (For Development)

In your `.env` file, change:

```env
QUEUE_CONNECTION=sync
```

This will process notifications immediately without queuing.

## Testing Steps

### 1. Check Queue Configuration

```bash
# Check current queue driver
php artisan tinker
>>> config('queue.default')
```

### 2. Subscribe to a Package

Visit `/subscription` or use the subscription feature in your app.

### 3. Process the Queue (if not using sync)

```bash
php artisan queue:work --once
```

### 4. Verify Notification in Database

```bash
php artisan tinker
>>> $user = User::find(1); // Your user ID
>>> $user->notifications()->count()
>>> $user->unreadNotifications()->count()
>>> $user->notifications()->latest()->first()->data
```

### 5. Check in UI

-   Look at the bell icon in the header
-   Badge should show unread count
-   Click to see notification dropdown
-   Visit `/notifications` to see full list

## Manual Test Script

Run this in `php artisan tinker`:

```php
use App\Models\User;
use App\Models\Package;

// Get or create a test user
$user = User::first();

// Get a package (free or paid)
$package = Package::first();

// Subscribe to package
$subscription = $user->subscribeToPackage($package, 'monthly');

// If using queued notifications, process the queue
\Illuminate\Support\Facades\Artisan::call('queue:work --once');

// Check notifications
$user->notifications()->count(); // Should be > 0
$user->unreadNotifications()->count(); // Should be > 0

// See notification data
$notification = $user->notifications()->latest()->first();
print_r($notification->data);
```

## Expected Notification Data

### For Paid Subscription:

```php
[
    'type' => 'subscription_renewal',
    'title' => 'Subscription Renewed',
    'message' => 'Premium Package renewed for ৳500.00',
    'subscription_id' => 1,
    'invoice_id' => 123,
    'invoice_number' => 'INV-2026-001',
    'package_name' => 'Premium Package',
    'amount' => 500.00,
    'balance_after' => 1500.00,
    'billing_cycle' => 'monthly',
    'is_free' => false,
    // ...
]
```

### For Free Subscription:

```php
[
    'type' => 'subscription_renewal',
    'title' => 'Subscription Renewed',
    'message' => 'Free Package subscription activated',
    'subscription_id' => 2,
    'invoice_id' => null,
    'invoice_number' => 'N/A',
    'package_name' => 'Free Package',
    'amount' => 0,
    'balance_after' => 1500.00, // User's current balance
    'billing_cycle' => 'monthly',
    'is_free' => true,
    // ...
]
```

## Troubleshooting

### "No notifications in database"

**Check 1: Queue processed?**

```bash
php artisan queue:work --once
```

**Check 2: Jobs table**

```bash
php artisan tinker
>>> DB::table('jobs')->count() // Pending jobs
>>> DB::table('failed_jobs')->count() // Failed jobs
```

**Check 3: Failed jobs**

```bash
php artisan queue:failed
php artisan queue:retry all
```

### "Notification appears but UI doesn't update"

**Check 1: Clear browser cache**

-   Hard refresh (Ctrl+Shift+R)
-   Clear localStorage

**Check 2: Livewire is working**

-   Check browser console for errors
-   Verify `@livewireScripts` in layout

### "Free subscriptions don't get notifications"

This should now be fixed. The notification is sent regardless of amount.

## Queue Configuration for Production

### Using Supervisor (Recommended)

Create `/etc/supervisor/conf.d/radtik-worker.conf`:

```ini
[program:radtik-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/radtikv4/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/radtikv4/storage/logs/worker.log
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start radtik-worker:*
```

### Using systemd

Create `/etc/systemd/system/radtik-worker.service`:

```ini
[Unit]
Description=RADTik Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/radtikv4/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

Then:

```bash
sudo systemctl enable radtik-worker
sudo systemctl start radtik-worker
```

## Quick Fix for Immediate Testing

If you want notifications to work **immediately** without queue processing:

1. Edit `.env`:

```env
QUEUE_CONNECTION=sync
```

2. Clear config cache:

```bash
php artisan config:clear
```

3. Test subscription - notifications will appear instantly!

## Summary of Changes

**Files Modified:**

1. ✅ `app/Notifications/Billing/SubscriptionRenewalNotification.php`

    - Made `$invoice` parameter nullable
    - Updated `toMail()` to handle null invoice
    - Updated `toArray()` to handle null invoice and show appropriate message

2. ✅ `app/Models/User.php` - `subscribeToPackage()` method
    - Moved notification call outside payment block
    - Now sends notification for both free and paid subscriptions

**Result:**

-   ✅ Free subscriptions get notifications
-   ✅ Paid subscriptions get notifications
-   ✅ Notifications stored in database
-   ✅ UI displays notifications correctly
-   ⚠️ Requires queue processing (or sync driver)
