# General Settings - Quick Reference Card

## 🔗 Access URL
```
/admin/general-settings
```

## 🔐 Permissions
- ✅ Admin
- ✅ Superadmin
- ❌ Reseller (403)

## 📋 Settings Available

### 🏢 Company Information
| Setting | Type | Required | Validation |
|---------|------|----------|------------|
| Company Name | Text | Yes | Max 255 chars |
| Company Logo | File | No | PNG/JPG, Max 2MB |
| Company Address | Textarea | No | Max 500 chars |
| Company Phone | Text | No | Max 50 chars |
| Company Email | Email | No | Valid email format |
| Company Website | URL | No | Valid URL format |

### ⚙️ System Preferences
| Setting | Type | Default | Options |
|---------|------|---------|---------|
| Timezone | Select | UTC | 12 timezones |
| Date Format | Select | Y-m-d | 5 formats |
| Time Format | Select | H:i:s | 4 formats |
| Currency | Select | USD | 10 currencies |
| Currency Symbol | Text | $ | Auto-updated |
| Items Per Page | Number | 10 | 5-100 |

### 🔧 Maintenance Mode
| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| Enable | Toggle | Off | Enable maintenance mode |
| Message | Textarea | Default text | Message shown to users |

## 💾 Storage

### Database
```sql
Table: general_settings
Columns: id, key, value, type, description, is_active, timestamps
```

### File Storage
```
storage/app/public/logos/
```

### Cache
```
Key: general_setting_{key}
TTL: 3600 seconds (1 hour)
```

## 🔄 Code Usage

### Get Setting Value
```php
// Single value
$companyName = GeneralSetting::getValue('company_name');
$companyName = GeneralSetting::getValue('company_name', 'Default');

// All settings
$settings = GeneralSetting::getAllSettings();

// Company info only
$companyInfo = GeneralSetting::getCompanyInfo();
```

### Set Setting Value
```php
GeneralSetting::setValue('company_name', 'New Company Name');
```

### Apply to Config
```php
GeneralSetting::applyToConfig();
// Updates: config('app.name'), config('app.timezone')
```

## 🎨 UI Components Used

### MaryUI Components
- `x-mary-card` - Card containers
- `x-mary-form` - Form wrapper
- `x-mary-input` - Text inputs
- `x-mary-textarea` - Text areas
- `x-mary-select` - Dropdown selects
- `x-mary-toggle` - Toggle switches
- `x-mary-file` - File upload
- `x-mary-button` - Action buttons
- `x-mary-badge` - Status badges
- `x-mary-alert` - Alert messages

## 🧪 Testing

### Run Tests
```bash
# All tests
php artisan test --filter=GeneralSettingsTest

# Specific test
php artisan test --filter="admin can access general settings page"
```

### Test Coverage
- ✅ Admin access (200)
- ✅ Superadmin access (200)
- ✅ Reseller denied (403)
- ✅ Model getValue/setValue
- ✅ Save settings
- ✅ Email validation
- ✅ URL validation
- ✅ Reset to defaults

## 📦 Files Created

```
app/
├── Livewire/Admin/
│   └── GeneralSettings.php ............... Component
└── Models/
    └── GeneralSetting.php ................ Model

database/migrations/
└── 2025_12_16_111500_create_general_settings_table.php

resources/views/
├── components/menu/
│   ├── admin-menu.blade.php .............. Updated
│   └── superadmin-menu.blade.php ......... Updated
└── livewire/admin/
    └── general-settings.blade.php ........ View

routes/
└── web.php ............................... Updated

tests/Feature/Settings/
└── GeneralSettingsTest.php ............... Tests

Documentation/
├── GENERAL_SETTINGS_DOCUMENTATION.md
└── IMPLEMENTATION_SUMMARY.md
```

## 🚀 Setup Instructions

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Create Storage Symlink
```bash
php artisan storage:link
```

### 3. Set Permissions
```bash
chmod -R 755 storage/app/public/logos
```

### 4. Clear Cache (if needed)
```bash
php artisan cache:clear
php artisan config:clear
```

## 🎯 Common Tasks

### Update Company Name
1. Navigate to `/admin/general-settings`
2. Update "Company Name" field
3. Click "Save Settings"

### Upload Logo
1. Navigate to `/admin/general-settings`
2. Click "Choose File" under Company Logo
3. Select PNG or JPG file (max 2MB)
4. Preview appears automatically
5. Click "Save Settings"

### Enable Maintenance Mode
1. Navigate to `/admin/general-settings`
2. Scroll to "Maintenance Mode" section
3. Toggle "Enable Maintenance Mode" switch
4. Update message if needed
5. Click "Save Settings"
6. Warning appears when active

### Reset to Defaults
1. Navigate to `/admin/general-settings`
2. Click "Reset to Defaults" button
3. Review reset values
4. Click "Save Settings" to persist

## 🔍 Troubleshooting

### Logo Not Uploading
- Check file size (max 2MB)
- Check file type (PNG, JPG, JPEG only)
- Ensure storage is linked: `php artisan storage:link`
- Check directory permissions

### Settings Not Saving
- Check database connection
- Clear cache: `php artisan cache:clear`
- Check logs: `storage/logs/laravel.log`
- Verify admin/superadmin role

### Cache Issues
- Clear cache: `php artisan cache:clear`
- Restart queue workers if running
- Check cache driver in `.env`

## 📊 Database Queries

### View All Settings
```sql
SELECT * FROM general_settings WHERE is_active = 1;
```

### Update Single Setting
```sql
UPDATE general_settings 
SET value = 'New Value' 
WHERE key = 'company_name';
```

### Reset All Settings
```sql
-- Run migration down/up
php artisan migrate:rollback --step=1
php artisan migrate
```

## 🌟 Best Practices

1. **Always save after changes** - Changes are not persisted until "Save Settings" is clicked
2. **Test maintenance mode** - Enable during low-traffic periods
3. **Optimize logo files** - Compress images before upload
4. **Clear cache after updates** - If settings don't reflect immediately
5. **Backup before changes** - Especially for maintenance mode
6. **Use reset wisely** - Reset to defaults erases all customizations
7. **Validate email/website** - Ensure correct format before saving

## 📞 Support

For issues or questions:
- Check documentation: `GENERAL_SETTINGS_DOCUMENTATION.md`
- Review implementation: `IMPLEMENTATION_SUMMARY.md`
- Check tests: `tests/Feature/Settings/GeneralSettingsTest.php`
- View logs: `storage/logs/laravel.log`

---

**Version**: 1.0  
**Last Updated**: 2025-12-16  
**Status**: Production Ready ✅
