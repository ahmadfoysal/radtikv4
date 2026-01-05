# Multi-Tenant Settings Implementation Summary

## What Was Implemented

✅ **User-Specific Settings System**

-   SuperAdmin settings are global (apply to all users as defaults)
-   Admin settings are user-specific (override global for themselves only)
-   Reseller settings inherit from parent admin, then global

✅ **Dynamic Timezone Support**

-   Each user can set their own timezone
-   Automatically applied via middleware on every request
-   All dates/times display in user's local timezone

✅ **Dynamic Currency Support**

-   Each user can set their own currency and symbol
-   Blade directives for easy formatting: `@userCurrency($amount)`

✅ **Dynamic Date/Time Format Support**

-   Each user chooses their preferred format (DD/MM/YYYY, MM/DD/YYYY, etc.)
-   Blade directives: `@userDate()`, `@userTime()`, `@userDateTime()`

✅ **Reseller Inheritance**

-   Resellers automatically inherit settings from parent admin
-   Can override by setting their own preferences

## Files Modified

1. **app/Models/GeneralSetting.php**

    - Updated `getValue()` to support reseller inheritance
    - Updated `setValue()` to properly handle superadmin/admin/reseller
    - Fixed `applyToConfig()` to only use global settings
    - Added helper methods: `formatDate()`, `formatTime()`, `formatDateTime()`, `getCurrencySymbol()`, `getCurrency()`
    - Added `applyUserConfig()` for per-user timezone application

2. **app/Livewire/Admin/GeneralSettings.php**

    - Updated `saveSettings()` to correctly handle superadmin vs admin/reseller
    - Updated `mount()` to allow resellers access
    - Fixed dropdown options format for MaryUI

3. **app/Http/Middleware/ApplyUserSettings.php** (NEW)

    - Middleware to apply user's timezone on every request

4. **bootstrap/app.php**

    - Registered ApplyUserSettings middleware to web group

5. **app/Providers/AppServiceProvider.php**

    - Added Blade directives: `@userDate`, `@userTime`, `@userDateTime`, `@userCurrency`
    - Apply global platform settings on boot

6. **resources/views/livewire/admin/general-settings.blade.php**
    - Updated header messages to clarify settings scope
    - Added info badge for non-superadmin users

## How It Works

### Settings Priority

```
User's Own Setting
    ↓ (if not found)
Parent Admin's Setting (resellers only)
    ↓ (if not found)
Global Platform Setting
    ↓ (if not found)
Default Value
```

### Database Structure

```
general_settings table:
- user_id = null     → Global platform settings (SuperAdmin)
- user_id = 123      → Admin's personal settings
- user_id = 456      → Reseller's personal settings
```

### Examples

**Scenario: Multi-Country Admins**

-   Admin A (Bangladesh): timezone=Asia/Dhaka, currency=BDT
-   Admin B (USA): timezone=America/New_York, currency=USD
-   Admin C (UK): timezone=Europe/London, currency=GBP

Each sees dates/times/currency in their local format, completely isolated.

**Scenario: Reseller Inheritance**

-   Admin (India): timezone=Asia/Kolkata, currency=INR
-   Reseller under Admin: No settings set
-   Result: Reseller sees Asia/Kolkata time and INR currency (inherited)

## Usage in Code

### In Blade Templates

```blade
{{-- User's formatted date --}}
@userDate($invoice->created_at)

{{-- User's formatted datetime --}}
@userDateTime($order->created_at)

{{-- User's currency format --}}
@userCurrency($invoice->total)
```

### In PHP

```php
// Get user's timezone
$timezone = GeneralSetting::getValue('timezone');

// Format date for user
$formatted = GeneralSetting::formatDate($date);

// Get user's currency symbol
$symbol = GeneralSetting::getCurrencySymbol();
```

## Testing Checklist

-   [ ] Login as SuperAdmin, set timezone to UTC
-   [ ] Login as Admin A, set timezone to Asia/Dhaka, currency to BDT
-   [ ] Login as Admin B, set timezone to America/New_York, currency to USD
-   [ ] Verify each admin sees their own settings
-   [ ] Create reseller under Admin A
-   [ ] Login as reseller, verify they see Asia/Dhaka timezone (inherited)
-   [ ] Reseller sets their own timezone to Asia/Kolkata
-   [ ] Verify reseller now sees their custom timezone

## Documentation

Full documentation available in: `USER_SPECIFIC_SETTINGS.md`
