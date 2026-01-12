# Database Seeders

## Production Seeding

For production deployment, run:

```bash
php artisan db:seed
```

Or specifically:

```bash
php artisan db:seed --class=PermissionSeed
```

This will create:

-   **Roles**: superadmin, admin, reseller
-   **Permissions**: All system permissions (router, voucher, hotspot, etc.)
-   **Superadmin User**:
    -   Email: `superadmin@example.com`
    -   Password: `password`
    -   ⚠️ **Change the password immediately after first login!**
-   **Payment Gateways**: Cryptomus and PayStation (inactive by default)
-   **Voucher Templates**: 5 print templates for WiFi vouchers
-   **Knowledgebase Articles**: 5 help articles for users
-   **Documentation Articles**: 5 technical documentation articles

## Demo/Testing Seeding

For demo or testing environments, use:

```bash
php artisan db:seed
# Answer "yes" when prompted for demo data
```

Or set environment variable:

```bash
USE_DEMO_SEEDER=true php artisan db:seed
```

This runs `ComprehensiveDemoSeeder` which creates:

-   Multiple admin and reseller users
-   Sample routers and zones
-   Vouchers and templates
-   Payment gateway configurations
-   Support tickets
-   And more realistic test data

## Available Seeders

1. **PermissionSeed** - Production essentials (roles, permissions, superadmin)
2. **PaymentGatewaySeeder** - Payment gateway configurations
3. **VoucherTemplateSeeder** - Voucher print templates
4. **KnowledgebaseArticleSeeder** - Help articles for users
5. **DocumentationArticleSeeder** - Technical documentation
6. **ComprehensiveDemoSeeder** - Complete demo data for testing
7. **DatabaseSeeder** - Main seeder that orchestrates the others

## Environment Variables

-   `USE_DEMO_SEEDER`: Set to `true` to automatically use demo seeder without prompt
