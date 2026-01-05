# General Settings Implementation

This document describes the implementation of the General Settings page for RADTik v4.

## Overview

The General Settings page allows admin and superadmin users to configure company information, system preferences, and maintenance mode settings.

## Components Created

### 1. Database Migration
**File**: `database/migrations/2025_12_16_111500_create_general_settings_table.php`

Creates the `general_settings` table with the following structure:
- `id`: Primary key
- `key`: Unique setting identifier
- `value`: Setting value (stored as text)
- `type`: Data type (string, boolean, integer, array)
- `description`: Setting description
- `is_active`: Boolean flag
- `timestamps`: Created and updated timestamps

Default settings include:
- Company information (name, logo, address, phone, email, website)
- System preferences (timezone, date format, time format, currency, items per page)
- Maintenance mode configuration

### 2. Model
**File**: `app/Models/GeneralSetting.php`

Features:
- Extends Laravel's Eloquent Model
- Uses `LogsActivity` trait for activity tracking
- Type casting for different data types (string, boolean, integer, array)
- Static methods for getting/setting values with caching
- Configuration application to Laravel config

Methods:
- `getValue($key, $default)`: Get setting value by key
- `setValue($key, $value)`: Set setting value by key
- `applyToConfig()`: Apply settings to Laravel configuration
- `getAllSettings()`: Get all settings as key-value array
- `getCompanyInfo()`: Get company-related settings

### 3. Livewire Component
**File**: `app/Livewire/Admin/GeneralSettings.php`

Features:
- Admin/Superadmin access only (403 for other roles)
- File upload support for company logo
- Real-time validation using Livewire attributes
- Auto-update currency symbol when currency changes
- Settings persistence with cache clearing

Sections:
1. **Company Information**: Name, logo, address, phone, email, website
2. **System Preferences**: Timezone, date/time formats, currency, pagination
3. **Maintenance Mode**: Enable/disable with custom message

### 4. Blade View
**File**: `resources/views/livewire/admin/general-settings.blade.php`

UI Features:
- Responsive 3-column layout (2 columns for forms, 1 for info)
- MaryUI component library for consistent design
- Real-time preview of current settings
- Logo upload with preview
- Validation feedback
- Quick tips and format examples
- Reset to defaults functionality

### 5. Route
**File**: `routes/web.php`

Added route:
```php
Route::get('/admin/general-settings', App\Livewire\Admin\GeneralSettings::class)->name('admin.general-settings');
```

Access: Requires authentication and non-suspended account

### 6. Menu Integration

Updated files:
- `resources/views/components/menu/admin-menu.blade.php`
- `resources/views/components/menu/superadmin-menu.blade.php`

Added "General Settings" menu item in the "Admin Settings" submenu with proper route and icon.

### 7. Tests
**File**: `tests/Feature/Settings/GeneralSettingsTest.php`

Test coverage:
- Admin can access general settings page
- Superadmin can access general settings page
- Reseller cannot access general settings page (403)
- Model can get and set values correctly
- Admin can save general settings
- Email validation works correctly
- URL validation works correctly
- Reset to defaults functionality

## Usage

### Accessing the Page
1. Log in as admin or superadmin
2. Navigate to: Admin Settings → General Settings
3. Or directly visit: `/admin/general-settings`

### Configuring Settings

#### Company Information
- **Company Name**: Required, displayed on invoices and emails
- **Logo**: Optional, PNG/JPG, max 2MB
- **Address**: Physical address
- **Phone**: Contact phone number
- **Email**: Contact email (validated format)
- **Website**: Company website URL (validated format)

#### System Preferences
- **Timezone**: Default system timezone
- **Date Format**: How dates are displayed
- **Time Format**: 12-hour or 24-hour format
- **Currency**: Default currency code
- **Currency Symbol**: Automatically updated when currency changes
- **Items Per Page**: Default pagination size (5-100)

#### Maintenance Mode
- **Enable/Disable**: Toggle maintenance mode
- **Message**: Custom message shown to users during maintenance
- Warning displayed when maintenance mode is active

### Saving Settings
1. Fill in desired values
2. Click "Save Settings" button
3. Settings are saved to database and applied to Laravel config
4. Cache is cleared for updated settings

### Reset to Defaults
Click "Reset to Defaults" to restore all settings to their initial values. Remember to save after resetting.

## Technical Details

### Caching
Settings are cached for 1 hour to improve performance. Cache is automatically cleared when settings are updated.

### File Storage
Company logos are stored in `storage/app/public/logos/` directory. Make sure to run:
```bash
php artisan storage:link
```

### Permission Check
Access is restricted using the following check in the component's `mount()` method:
```php
$user = auth()->user();
abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403);
```

### Database Schema
To run the migration:
```bash
php artisan migrate
```

## Integration Points

### Configuration Application
Settings can be applied to Laravel's configuration:
```php
GeneralSetting::applyToConfig();
```

This updates:
- `config('app.name')` with company name
- `config('app.timezone')` with selected timezone

### Retrieving Settings
In your code, you can retrieve settings using:
```php
$companyName = GeneralSetting::getValue('company_name');
$timezone = GeneralSetting::getValue('timezone', 'UTC');
```

## Future Enhancements

Possible additions:
- More timezone options
- Additional date/time format presets
- Multi-language support
- Invoice template customization
- Social media links
- Business hours configuration
- Tax/VAT settings
- Default email templates

## Design Patterns Used

1. **Repository Pattern**: Similar to EmailSetting model
2. **Type Casting**: Automatic type conversion for different data types
3. **Caching Strategy**: 1-hour cache with invalidation on update
4. **Livewire Components**: For reactive UI without page reloads
5. **MaryUI Components**: For consistent design system
6. **Authorization**: Role-based access control
7. **Activity Logging**: Using LogsActivity trait

## Testing

Run tests with:
```bash
php artisan test --filter=GeneralSettingsTest
```

Or run all tests:
```bash
php artisan test
```
