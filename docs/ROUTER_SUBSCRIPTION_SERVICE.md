# Router Subscription Service

This document explains how to use the Router Subscription & Billing system.

## Overview

The Router Subscription Service centralizes all router subscription and billing logic, ensuring consistency between router creation and renewal flows. It integrates with the existing BillingService to handle balance deductions and invoice creation.

## Key Components

### 1. RouterSubscriptionService

Located at `app/Services/Subscriptions/RouterSubscriptionService.php`

This service handles:
- Balance validation for package subscriptions
- Creating new router subscriptions with billing
- Renewing existing router subscriptions
- Managing package snapshots and subscription dates

### 2. HasRouterBilling Trait

Located at `app/Models/Traits/HasRouterBilling.php`

This trait is added to the User model and provides convenient methods:
- `hasBalanceForPackage(Package $package): bool` - Check if user has sufficient balance
- `subscribeRouterWithPackage(array $routerData, Package $package): Router` - Subscribe to a new router with billing

### 3. Router Model Updates

New fields added to the Router model:
- `package_start_date` (datetime) - When the subscription started
- `package_end_date` (datetime) - When the subscription expires
- `auto_renew` (boolean) - Whether to automatically renew the subscription

## Usage Examples

### Check Balance Before Subscription

```php
use App\Models\Package;
use App\Models\User;

$user = User::find(1);
$package = Package::find(1);

if ($user->hasBalanceForPackage($package)) {
    // User has sufficient balance
} else {
    // Show error - insufficient balance
}
```

### Create a Router with Package Subscription

```php
$user = User::find(1);
$package = Package::find(1);

$routerData = [
    'name' => 'My Router',
    'address' => '192.168.1.1',
    'port' => 8728,
    'username' => 'admin',
    'password' => Crypt::encryptString('password'),
    'zone_id' => 1,
];

try {
    $router = $user->subscribeRouterWithPackage($routerData, $package);
    // Router created successfully, balance deducted, invoice created
} catch (\RuntimeException $e) {
    // Handle error (insufficient balance, etc.)
}
```

### Renew a Router Subscription

```php
use App\Models\Router;
use App\Services\Subscriptions\RouterSubscriptionService;

$router = Router::find(1);
$service = app(RouterSubscriptionService::class);

try {
    $service->renewRouter($router);
    // Subscription renewed, balance deducted, invoice created
} catch (\RuntimeException $e) {
    // Handle error (insufficient balance, package not found, etc.)
}
```

### Renew with a Different Package (Upgrade/Downgrade)

```php
$router = Router::find(1);
$newPackage = Package::find(2);
$service = app(RouterSubscriptionService::class);

try {
    $service->renewRouter($router, $newPackage);
    // Subscription renewed with new package
} catch (\RuntimeException $e) {
    // Handle error
}
```

## Auto-Renewal Command

The system includes a console command to automatically renew router subscriptions.

### Command Usage

```bash
# Renew routers expiring within 7 days (default)
php artisan routers:renew-subscriptions

# Renew routers expiring within 3 days
php artisan routers:renew-subscriptions --days=3
```

### Setting Up Auto-Renewal

Add the command to your scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run daily at 2 AM
    $schedule->command('routers:renew-subscriptions')->dailyAt('02:00');
    
    // Or run every 6 hours
    $schedule->command('routers:renew-subscriptions')->everySixHours();
}
```

### How Auto-Renewal Works

1. The command finds all routers where:
   - `auto_renew` is `true`
   - `package_end_date` is within the specified days window
   - `package_end_date` is not already expired

2. For each router found, it attempts to renew using the `RouterSubscriptionService`

3. If renewal fails (insufficient balance, etc.), it logs the error but continues processing other routers

4. The command reports a summary of successful and failed renewals

## Integration with Livewire Components

### Router Creation Component

The router creation flow has been updated in `app/Livewire/Router/Create.php`:

```php
public function save()
{
    $this->validate();
    
    if ($this->package_id) {
        $package = Package::find($this->package_id);
        
        // Check balance
        if (!$user->hasBalanceForPackage($package)) {
            $this->error('Insufficient balance');
            return;
        }
        
        // Create with billing
        $user->subscribeRouterWithPackage($routerData, $package);
    } else {
        // Create without billing
        Router::create($routerData);
    }
}
```

## Database Schema

### Migration

The migration `2025_12_07_150730_add_subscription_fields_to_routers_table.php` adds:

```php
$table->timestamp('package_start_date')->nullable()->after('package');
$table->timestamp('package_end_date')->nullable()->after('package_start_date');
$table->boolean('auto_renew')->default(false)->after('package_end_date');
```

## Billing Flow

### Package Subscription Flow

1. User checks if they have sufficient balance
2. Service validates balance and deducts the package price
3. An invoice is created with:
   - Type: `debit`
   - Category: `router_subscription`
   - Amount: Package price (monthly or yearly based on billing cycle)
   - Router ID: The newly created router
4. Router is created with package snapshot and subscription dates
5. Invoice is linked to the router

### Renewal Flow

1. Service finds the current package from router's package snapshot
2. Service validates user has sufficient balance
3. Balance is debited and invoice created with category `router_renewal`
4. Router's subscription dates are extended:
   - If not expired: extends from current end date
   - If expired: starts from now
5. New package snapshot is stored (supports package upgrades)

## Error Handling

The service throws `RuntimeException` in the following cases:

- **Insufficient balance**: When user doesn't have enough balance for the package
- **Package not found**: When trying to renew with a package that doesn't exist
- **No package information**: When trying to renew a router without package information

All operations are wrapped in database transactions to ensure consistency.

## Testing

The implementation includes comprehensive tests:

- `tests/Feature/RouterSubscriptionServiceTest.php` (10 tests)
  - Balance checking
  - Subscription creation
  - Renewal logic
  - User trait methods
  
- `tests/Feature/RenewRouterSubscriptionsCommandTest.php` (5 tests)
  - Auto-renewal with different scenarios
  - Handling insufficient balance
  - Custom days window
  
- `tests/Feature/BillingServiceTest.php` (12 tests)
  - Existing billing tests ensure no regression

Run tests with:
```bash
php artisan test --filter="RouterSubscriptionServiceTest|RenewRouterSubscriptionsCommandTest"
```

## Best Practices

1. **Always check balance first**: Use `hasBalanceForPackage()` before attempting subscription
2. **Handle exceptions**: Wrap service calls in try-catch blocks
3. **Set auto_renew appropriately**: Only enable for packages that allow it
4. **Monitor renewal command**: Check logs regularly to ensure auto-renewals are working
5. **Test in staging**: Test package changes and renewals in a staging environment first

## Support

For questions or issues, please refer to:
- Service code: `app/Services/Subscriptions/RouterSubscriptionService.php`
- Trait code: `app/Models/Traits/HasRouterBilling.php`
- Tests: `tests/Feature/RouterSubscriptionServiceTest.php`
