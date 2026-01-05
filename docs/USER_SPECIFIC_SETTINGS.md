# User-Specific Settings Implementation

## Overview

RADTik v4 now supports dynamic, user-specific settings for timezone, currency, date/time formats, and company information. This allows each admin from different countries to have their own localized settings.

## Settings Hierarchy

The system uses a three-tier inheritance model:

1. **SuperAdmin Settings (Global)** - `user_id = null`

    - Platform-wide defaults
    - All users inherit these if they haven't set their own

2. **Admin Settings (User-Specific)** - `user_id = admin_id`

    - Overrides global settings for that admin only
    - Isolated from other admins

3. **Reseller Settings (User-Specific with Inheritance)** - `user_id = reseller_id`
    - Can set their own settings
    - Falls back to parent admin's settings if not set
    - Falls back to global settings if parent hasn't set them

## Priority Order

```
User's Setting > Parent Admin's Setting (resellers only) > Global Setting > Default Value
```

## How It Works

### Setting Values

When a user saves their settings:

```php
// SuperAdmin
GeneralSetting::setValue('timezone', 'UTC', 'string', null);  // user_id = null (global)

// Admin
GeneralSetting::setValue('timezone', 'Asia/Dhaka', 'string', 123);  // user_id = 123

// Reseller
GeneralSetting::setValue('timezone', 'Asia/Kolkata', 'string', 456);  // user_id = 456
```

### Getting Values

```php
// Automatically uses auth()->id() and handles inheritance
$timezone = GeneralSetting::getValue('timezone');

// Or specify user explicitly
$timezone = GeneralSetting::getValue('timezone', 'UTC', $userId);
```

### Reseller Inheritance Example

**Scenario:**

-   SuperAdmin sets global timezone: `UTC`
-   Admin (ID: 100) sets timezone: `Asia/Dhaka`
-   Reseller (ID: 200, admin_id: 100) doesn't set timezone

**Result:**

```php
GeneralSetting::getValue('timezone', null, 200);
// Returns: 'Asia/Dhaka' (inherited from parent admin)
```

## Automatic Application

### Middleware

The `ApplyUserSettings` middleware automatically applies timezone settings for each authenticated user:

```php
// In bootstrap/app.php
$middleware->web(append: [
    \App\Http\Middleware\ApplyUserSettings::class,
]);
```

This ensures:

-   User's timezone is applied to `Config::set('app.timezone')`
-   PHP's `date_default_timezone_set()` is called
-   All date/time operations use the user's timezone

## Blade Directives

Use these directives in Blade templates for automatic formatting:

```blade
{{-- Format date according to user's preference --}}
@userDate($createdAt)
{{-- Output: 2024-12-17 or 17/12/2024 depending on user setting --}}

{{-- Format time --}}
@userTime($createdAt)
{{-- Output: 14:30:45 or 02:30 PM depending on user setting --}}

{{-- Format date and time --}}
@userDateTime($createdAt)
{{-- Output: 2024-12-17 14:30:45 or 17/12/2024 02:30 PM --}}

{{-- Format currency --}}
@userCurrency($amount)
{{-- Output: $100.00 or ৳100.00 or ₹100.00 depending on user setting --}}
```

## Helper Methods

### In PHP Code

```php
// Get formatted date for current user
GeneralSetting::formatDate($date);

// Get formatted time
GeneralSetting::formatTime($time);

// Get formatted datetime
GeneralSetting::formatDateTime($datetime);

// Get currency symbol
GeneralSetting::getCurrencySymbol();  // Returns: $, ৳, ₹, etc.

// Get currency code
GeneralSetting::getCurrency();  // Returns: USD, BDT, INR, etc.
```

### With Specific User

```php
// Format date for specific user
GeneralSetting::formatDate($date, $userId);

// Get currency for specific user
GeneralSetting::getCurrencySymbol($userId);
```

## Database Structure

```sql
CREATE TABLE general_settings (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULL,  -- NULL = global, otherwise user-specific
    key VARCHAR(255),
    value TEXT,
    type VARCHAR(255),  -- string, boolean, integer, array
    description TEXT,
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, key)  -- Allows same key for different users
);
```

## Available Settings Keys

### Company Information (User-Specific)

-   `company_name`
-   `company_logo`
-   `company_address`
-   `company_phone`
-   `company_email`
-   `company_website`

### Preferences (User-Specific)

-   `timezone` - User's local timezone
-   `date_format` - Y-m-d, d/m/Y, m/d/Y, etc.
-   `time_format` - H:i:s, h:i A, etc.
-   `currency` - USD, BDT, INR, EUR, etc.
-   `currency_symbol` - $, ৳, ₹, €, etc.
-   `items_per_page` - Pagination limit

### Platform Settings (Global Only)

-   `platform_name`
-   `default_timezone`
-   `default_currency`
-   `default_currency_symbol`
-   `default_date_format`
-   `default_time_format`
-   `default_items_per_page`
-   `maintenance_mode` (boolean)
-   `maintenance_message`

## Example Use Cases

### Case 1: Multi-Country Admins

**Admin in Bangladesh:**

-   Timezone: `Asia/Dhaka`
-   Currency: `BDT` (৳)
-   Date format: `d/m/Y`
-   Sees all dates/times in BST, prices in BDT

**Admin in USA:**

-   Timezone: `America/New_York`
-   Currency: `USD` ($)
-   Date format: `m/d/Y`
-   Sees all dates/times in EST, prices in USD

**Result:** Both admins have completely isolated, localized experiences.

### Case 2: Reseller Inheritance

**Admin (India):**

-   Timezone: `Asia/Kolkata`
-   Currency: `INR` (₹)

**Reseller under this Admin:**

-   Hasn't set any preferences
-   Automatically inherits: `Asia/Kolkata` timezone and `INR` currency
-   Can override by setting their own preferences

### Case 3: Platform Defaults

**SuperAdmin sets:**

-   Global timezone: `UTC`
-   Global currency: `USD`

**New Admin signs up:**

-   Initially sees UTC time and USD currency
-   Can customize in General Settings to their local preferences

## Code Examples

### In Livewire Components

```php
class InvoiceList extends Component
{
    public function render()
    {
        $invoices = Invoice::where('user_id', auth()->id())->get();

        // Dates are automatically in user's timezone due to middleware
        return view('livewire.invoices.list', compact('invoices'));
    }
}
```

### In Blade Views

```blade
@foreach($invoices as $invoice)
    <tr>
        <td>{{ $invoice->invoice_number }}</td>
        <td>@userDate($invoice->created_at)</td>
        <td>@userCurrency($invoice->amount)</td>
    </tr>
@endforeach
```

### Manual Formatting

```php
// In a controller or Livewire component
public function exportInvoice($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);

    $data = [
        'date' => GeneralSetting::formatDate($invoice->created_at),
        'amount' => GeneralSetting::getCurrencySymbol() . number_format($invoice->amount, 2),
        'currency' => GeneralSetting::getCurrency(),
    ];

    return PDF::loadView('invoices.export', $data)->download();
}
```

## Testing Scenarios

### Test 1: Isolation

1. Login as Admin A
2. Set timezone to `Asia/Dhaka`, currency to `BDT`
3. Logout and login as Admin B
4. Set timezone to `America/New_York`, currency to `USD`
5. Each admin should see their own settings independently

### Test 2: Reseller Inheritance

1. Login as Admin with timezone `Europe/London`
2. Create a reseller under this admin
3. Login as the reseller
4. Check timezone in General Settings
5. Should show `Europe/London` (inherited)
6. Reseller can override by setting their own

### Test 3: Global Fallback

1. Login as SuperAdmin
2. Set global timezone to `UTC`
3. Create a new admin who hasn't set preferences
4. Login as new admin
5. Should see `UTC` timezone (global fallback)

## Caching

Settings are cached for 1 hour per user:

```php
Cache key format:
- User-specific: "general_setting_{userId}_{key}"
- Global: "general_setting_global_{key}"
```

Cache is automatically cleared when settings are updated.

## Migration Notes

Existing installations will:

1. Continue working with existing data
2. SuperAdmin settings become global (user_id = null)
3. Admin settings remain user-specific
4. Resellers can now set their own or inherit from parent
