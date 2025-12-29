# Database Seeders Guide

This document explains the database seeders available in RADTik v4 and how to use them for demo/production environments.

## Available Seeders

### 1. ComprehensiveDemoSeeder (Recommended for Demo)

**File:** `ComprehensiveDemoSeeder.php`

Creates a complete, realistic demo environment with:

-   **1 Superadmin** with full system access
-   **5 Admin users** from different cities in Bangladesh
-   **10 Reseller users** (8 active, 2 inactive)
-   **15-25 Zones** distributed across admins
-   **8 Subscription Packages** (monthly and annual)
-   **25-50 Routers** with realistic configurations
-   **Router-Reseller Assignments** (2-5 routers per reseller)
-   **8 Profile Types** per router (1 Hour to 30 Days)
-   **50-100 Vouchers** per router (various statuses)
-   **100-300 Activation Logs** per router
-   **10-30 Invoices** per user
-   **2-8 Support Tickets** per user with replies

**Demo Credentials:**

```
Superadmin:
- Email: superadmin@radtik.demo
- Password: password

Admins:
- admin1@radtik.demo to admin5@radtik.demo
- Password: password

Resellers:
- reseller1@radtik.demo to reseller10@radtik.demo
- Password: password
```

**Features:**
✅ Realistic Bangladesh locations and data
✅ Time-distributed data (activities over past 60-90 days)
✅ Various voucher statuses (inactive, active, expired, disabled)
✅ Router packages with start/end dates
✅ Reseller permissions properly assigned
✅ Invoice history with multiple categories
✅ Support ticket conversations

### 2. Basic Seeders (Production/Minimal Setup)

Individual seeders for minimal setup:

-   **PermissionSeed** - Creates roles and permissions
-   **UserSeed** - Creates 1 superadmin, 1 admin, 1 reseller
-   **PackageSeeder** - Creates subscription packages
-   **ZoneSeeder** - Creates demo zones
-   **RouterSeeder** - Creates routers with realistic configs
-   **VoucherSeeder** - Creates vouchers
-   **VoucherTemplateSeeder** - Creates voucher templates
-   **PaymentGatewaySeeder** - Configures payment gateways
-   **EmailSettingSeeder** - Email configuration
-   **KnowledgebaseArticleSeeder** - Help articles
-   **DocumentationArticleSeeder** - System documentation

## Usage

### Running Comprehensive Demo Seeder

```bash
# Fresh database with comprehensive demo data
php artisan migrate:fresh --seed
# Select "Yes" when prompted

# Or run directly
php artisan db:seed --class=ComprehensiveDemoSeeder
```

### Running Basic Seeders

```bash
# Fresh database with basic seeders
php artisan migrate:fresh --seed
# Select "No" when prompted

# Or run specific seeder
php artisan db:seed --class=UserSeed
```

### Running Individual Seeders

```bash
# Run only permission seeder
php artisan db:seed --class=PermissionSeed

# Run only router seeder
php artisan db:seed --class=RouterSeeder
```

## Data Characteristics

### ComprehensiveDemoSeeder Data:

**Users:**

-   All use Bangladesh phone numbers (+880...)
-   Realistic Bangladesh addresses (Dhaka, Chittagong, Sylhet, etc.)
-   Varied balance amounts (realistic for demo)
-   Last login dates distributed over past 48 hours
-   Some inactive users for testing

**Routers:**

-   IP addresses in private ranges (192.168.x.x, 10.x.x.x)
-   Realistic router names (Central Office, North Branch, etc.)
-   Monthly expenses: 800-3000 BDT
-   Active subscription packages with dates
-   Associated with zones and templates

**Vouchers:**

-   Usernames: user####xxxx format
-   Passwords: 8-character alphanumeric
-   MAC addresses: proper format (XX:XX:XX:XX:XX:XX)
-   Mixed statuses for realistic demo
-   Batch tracking enabled

**Profiles:**

-   8 standard profiles: 1 Hour, 3 Hours, 12 Hours, 1 Day, 3 Days, 7 Days, 15 Days, 30 Days
-   Prices: 20-2000 BDT
-   Speed limits: 5M/5M to 50M/50M
-   Bangladesh Taka (৳) pricing

**Invoices:**

-   Categories: subscription, balance_topup, subscription_renewal, commission_payment
-   80% completed, 20% pending/failed/cancelled
-   Amounts: 100-10,000 BDT
-   Distributed over past 90 days

**Tickets:**

-   15 realistic support subjects
-   Priorities: low, medium, high, urgent
-   Statuses: open, in_progress, waiting_reply, resolved, closed
-   Some with staff replies

## Environment Considerations

### For Demo/Development:

✅ Use `ComprehensiveDemoSeeder`
✅ Email domain: @radtik.demo
✅ All passwords: "password"
✅ Rich data for UI testing

### For Production:

✅ Use basic seeders only
✅ Create real admin accounts manually
✅ Set strong passwords
✅ Configure proper email settings

## Resetting Demo Data

```bash
# Complete reset with fresh demo data
php artisan migrate:fresh --seed

# Keep migrations, just refresh seeds
php artisan db:seed --class=ComprehensiveDemoSeeder
```

⚠️ **Warning:** `migrate:fresh` will drop all tables and data!

## Customization

To customize the ComprehensiveDemoSeeder:

1. Edit `database/seeders/ComprehensiveDemoSeeder.php`
2. Modify arrays at the top of the class:
    - `$bangladeshCities` - Add more cities
    - `$bangladeshAreas` - Add more areas
    - `$profileTemplates` - Change pricing/plans
3. Adjust counts in methods:
    - Router count per admin (line ~280)
    - Voucher count per router (line ~360)
    - Invoice count per user (line ~460)

## Troubleshooting

**Issue:** Seeder fails with foreign key constraint

-   **Solution:** Run `php artisan migrate:fresh` before seeding

**Issue:** Too much data / seeding takes long

-   **Solution:** Reduce counts in ComprehensiveDemoSeeder methods

**Issue:** Want to add more admins/resellers

-   **Solution:** Increase loop counts in `createUsers()` method

**Issue:** Need different country data

-   **Solution:** Modify `$bangladeshCities` and `$bangladeshAreas` arrays

## Next Steps

After seeding:

1. Login with any demo credential
2. Explore dashboard with realistic data
3. Test voucher generation
4. Check router assignments
5. View activation logs
6. Test support tickets

## Contributing

When adding new seeders:

1. Create seeder class in `database/seeders/`
2. Add to `DatabaseSeeder.php` call list
3. Document in this README
4. Use realistic data patterns
5. Add created_at/updated_at timestamps for historical data
