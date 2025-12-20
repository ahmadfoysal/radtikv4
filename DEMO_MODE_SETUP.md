# Demo Mode Setup Guide

## Overview

RADTik v4 includes a comprehensive demo mode that allows potential customers to try the software with realistic data. The system automatically resets every hour to maintain a clean demo environment.

## Features

### Demo Data Includes:

-   **3 Demo Users**: Superadmin, Admin, and Reseller with predefined credentials
-   **5 Routers**: Distributed across 5 zones with realistic configurations
-   **Multiple Profiles**: Various bandwidth and validity options per router
-   **Vouchers**: Active, inactive, and expired vouchers for testing
-   **Voucher Activation Logs**: 20-50 activations per profile for analytics
-   **Invoices**: 30+ invoices showing payment history
-   **Support Tickets**: 15 tickets with various statuses and priorities
-   **Zones**: 5 geographical zones (North, South, East, West, Central)

### Demo Credentials:

```
Superadmin: demo-superadmin@radtik.local / password
Admin:      demo-admin@radtik.local / password
Reseller:   demo-reseller@radtik.local / password
```

## Installation

### 1. Enable Demo Mode

Add to your `.env` file:

```env
DEMO_MODE=true
```

### 2. Initial Setup

Run the demo seeder to populate initial data:

```bash
php artisan db:seed --class=DemoDataSeeder
```

### 3. Configure Scheduler

Ensure the Laravel scheduler is running. Add to your crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The system will automatically reset demo data every hour when `DEMO_MODE=true`.

### 4. Manual Reset (Optional)

You can manually reset demo data anytime:

```bash
# With confirmation
php artisan demo:reset

# Without confirmation (force)
php artisan demo:reset --force
```

## How It Works

### Automatic Hourly Reset

When `DEMO_MODE=true`, the scheduler runs `demo:reset` command every hour:

1. **Clears Demo Data**: Deletes all data from demo users (identified by `@radtik.local` email)

    - Voucher logs
    - Vouchers
    - Reseller router assignments
    - Profiles
    - Tickets
    - Invoices
    - Routers
    - Zones
    - Activity logs
    - Demo users

2. **Seeds Fresh Data**: Runs `DemoDataSeeder` to populate new demo data

3. **Clears Caches**: Clears all application caches for clean state

### Demo Banner

When demo mode is active, a prominent banner appears at the top of every page showing:

-   Demo mode notification
-   Auto-reset information
-   All demo credentials for easy access

## Demo Data Details

### Users

-   **Superadmin**: Full system access, 50,000 BDT balance
-   **Admin**: Router management, 25,000 BDT balance
-   **Reseller**: Assigned to admin, 5,000 BDT balance, 3 routers assigned

### Routers

Each router includes:

-   Unique IP address (192.168.x.1)
-   Active package subscription
-   Login address for hotspot
-   Associated zone
-   Voucher template
-   Monthly expense

### Profiles per Router

-   1 Hour - 5 Mbps
-   1 Day - 10 Mbps
-   3 Days - 15 Mbps
-   7 Days - 20 Mbps
-   30 Days - 50 Mbps

### Vouchers

-   10 vouchers per profile per router
-   Mixed statuses (active, inactive, expired)
-   Realistic creation dates (last 30 days)

### Analytics Data

-   Voucher activation logs spanning 30 days
-   Income tracking per profile
-   Router performance metrics
-   Sales summary data

## Security Considerations

### Production Use

1. **Never use demo mode in production** with real customer data
2. Demo mode should only be enabled on dedicated demo instances
3. All demo users use `@radtik.local` domain - ensure this domain is not used for real users

### Isolation

Demo data is completely isolated by email domain:

-   Only users with `@radtik.local` email are affected by reset
-   Real users and their data are never touched
-   Safe to run on database with mixed demo/real data (not recommended)

## Best Practices

### Dedicated Demo Server

Recommended setup for demo mode:

1. Use a separate server/subdomain (e.g., demo.radtik.com)
2. Configure `DEMO_MODE=true` only on demo server
3. Use a separate database from production
4. Implement read-only mode for certain sensitive operations

### Monitoring

-   Check logs for successful resets: `storage/logs/laravel.log`
-   Monitor scheduler execution: `php artisan schedule:list`
-   Verify reset timing: `php artisan schedule:test`

### Custom Reset Schedule

To change reset frequency, edit `routes/console.php`:

```php
// Change from hourly to every 30 minutes
Schedule::command('demo:reset --force')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Or every 2 hours
Schedule::command('demo:reset --force')
    ->everyTwoHours()
    ->withoutOverlapping();
```

## Troubleshooting

### Reset Not Running

1. Verify scheduler is configured in crontab
2. Check `DEMO_MODE=true` in `.env`
3. Run `php artisan schedule:list` to see scheduled tasks
4. Test manually: `php artisan demo:reset --force`

### Missing Demo Users

1. Run seeder manually: `php artisan db:seed --class=DemoDataSeeder`
2. Check for database errors in logs
3. Verify permissions are seeded: `php artisan db:seed --class=PermissionSeed`

### Performance Issues

1. Reset process takes 5-20 seconds depending on data volume
2. Uses database transactions for safety
3. Runs in background with `->runInBackground()`
4. Prevents overlapping with `->withoutOverlapping()`

## Customization

### Add More Demo Data

Edit `database/seeders/DemoDataSeeder.php` to:

-   Add more demo users
-   Create additional routers
-   Increase voucher count
-   Add custom zones
-   Include more ticket types

### Modify Reset Behavior

Edit `app/Console/Commands/ResetDemoData.php` to:

-   Preserve certain data
-   Add custom cleanup logic
-   Modify reset notifications
-   Implement soft deletes instead of hard deletes

## Environment Variables

```env
# Enable/disable demo mode
DEMO_MODE=true

# Optionally customize demo behavior
DEMO_RESET_INTERVAL=3600  # seconds (default: 1 hour)
DEMO_SHOW_BANNER=true      # show demo banner
```

## Testing Demo Mode

### Local Testing

```bash
# 1. Enable demo mode
echo "DEMO_MODE=true" >> .env

# 2. Seed demo data
php artisan db:seed --class=DemoDataSeeder

# 3. Login with demo credentials
# Visit: http://localhost/login
# Email: demo-admin@radtik.local
# Password: password

# 4. Test manual reset
php artisan demo:reset

# 5. Verify scheduler
php artisan schedule:test
```

### Verify Reset Works

```bash
# Watch the scheduler run (requires screen/tmux or separate terminal)
php artisan schedule:work

# Or check specific command
php artisan tinker
>>> Schedule::command('demo:reset')->hourly();
```

## Support

For issues with demo mode:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database connection
3. Ensure all migrations are run
4. Confirm permissions are properly seeded

---

**Note**: Demo mode is designed for showcasing the software to potential customers. It should not be used in production environments with real customer data.
