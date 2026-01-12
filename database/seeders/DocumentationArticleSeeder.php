<?php

namespace Database\Seeders;

use App\Models\DocumentationArticle;
use Illuminate\Database\Seeder;

class DocumentationArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates technical documentation articles.
     */
    public function run(): void
    {
        $this->command->info('📖 Creating documentation articles...');

        $articles = [
            [
                'title' => 'API Authentication',
                'category' => 'API',
                'content' => <<<'MD'
# API Authentication

## Overview
RADTik uses token-based authentication for API access.

## Getting API Token

### Generate Token
```bash
POST /api/auth/token
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "your_password"
}
```

### Response
```json
{
    "token": "1|abcdef123456...",
    "expires_at": "2026-02-12T00:00:00.000000Z"
}
```

## Using the Token

Include the token in all API requests:

```bash
Authorization: Bearer 1|abcdef123456...
```

## Token Expiration
- Tokens expire after 30 days
- Refresh token before expiration
- Store tokens securely

## Security Best Practices
- Never commit tokens to version control
- Use environment variables
- Rotate tokens regularly
- Implement rate limiting
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Router API Integration',
                'category' => 'Integration',
                'content' => <<<'MD'
# Router API Integration

## MikroTik RouterOS API

### Connection Parameters
```php
[
    'host' => '192.168.1.1',
    'user' => 'admin',
    'pass' => 'password',
    'port' => 8728
]
```

### Available Operations

#### Get Router Resource
```php
GET /api/routers/{id}/resource
```

Returns CPU, memory, uptime, and other system info.

#### Get Active Users
```php
GET /api/routers/{id}/active-users
```

Lists currently connected hotspot users.

#### Create Hotspot User
```php
POST /api/routers/{id}/hotspot-users
{
    "name": "user123",
    "password": "pass123",
    "profile": "default"
}
```

#### Disconnect User
```php
POST /api/routers/{id}/disconnect/{username}
```

### Error Handling
- Connection timeout: 10 seconds
- Retry failed connections up to 3 times
- Log all API errors for debugging

## Rate Limits
- 60 requests per minute per router
- 1000 requests per hour per account
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Database Schema',
                'category' => 'Development',
                'content' => <<<'MD'
# Database Schema

## Core Tables

### users
Main user table with authentication and profile data.
- Supports multi-tenancy (admin_id)
- Role-based permissions via Spatie
- Balance and commission tracking

### routers
MikroTik router configurations.
- Encrypted password storage
- Zone assignment
- Voucher template association
- Package subscription link

### vouchers
WiFi access vouchers.
- Generated in batches
- Profile-based configuration
- Status tracking (active, expired, used)

### hotspot_users
Direct hotspot user accounts (not vouchers).
- Created individually or imported
- Custom profiles per user

### invoices
Payment records and transactions.
- Links to payment gateways
- Subscription renewals
- Balance top-ups

## Relationships

### User → Routers
- Admin users own routers
- Resellers assigned via reseller_router pivot

### Router → Vouchers
- One-to-many relationship
- Vouchers belong to specific routers

### Router → Zone
- Many-to-one relationship
- Groups routers by location

### User → Subscriptions
- Users subscribe to packages
- Package defines router/voucher limits

## Migrations
Run migrations in order:
```bash
php artisan migrate
```

All migrations are in `database/migrations/`
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Permission System',
                'category' => 'Security',
                'content' => <<<'MD'
# Permission System

## Overview
RADTik uses Spatie Laravel Permission package for role-based access control.

## Roles

### Superadmin
- Full system access
- Cannot be restricted
- Bypasses all permission checks

### Admin
- Manage own routers and resources
- Create resellers
- Generate vouchers
- View reports

### Reseller
- Limited to assigned routers
- Generate vouchers (with permissions)
- View assigned data only
- Cannot create other users

## Key Permissions

### Router Management
- `add_router` - Create new routers
- `edit_router` - Modify router settings
- `delete_router` - Remove routers
- `view_router` - View router details

### Voucher Management
- `generate_vouchers` - Create voucher batches
- `print_vouchers` - Print vouchers
- `view_vouchers` - View voucher lists
- `delete_vouchers` - Remove vouchers

### User Management
- `create_single_user` - Create hotspot users
- `edit_hotspot_users` - Modify users
- `delete_hotspot_users` - Remove users
- `view_active_sessions` - See connected users

## Checking Permissions

### In Code
```php
// Check permission
if ($user->can('view_router')) {
    // Allow access
}

// Authorize or fail
$this->authorize('generate_vouchers');
```

### In Blade
```blade
@can('view_router')
    <!-- Content visible only with permission -->
@endcan
```

## Assigning Permissions
```php
// To user
$user->givePermissionTo('view_router');

// To role
$role->givePermissionTo(['view_router', 'edit_router']);

// Sync (replaces all)
$user->syncPermissions(['view_router', 'view_vouchers']);
```
MD,
                'is_active' => true,
            ],
            [
                'title' => 'Deployment Guide',
                'category' => 'Deployment',
                'content' => <<<'MD'
# Deployment Guide

## Production Deployment

### Prerequisites
- PHP 8.2 or higher
- MySQL/MariaDB 8.0+
- Composer
- Node.js & NPM
- Web server (Apache/Nginx)

### Installation Steps

1. **Clone Repository**
```bash
git clone https://github.com/yourrepo/radtik.git
cd radtik
```

2. **Install Dependencies**
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

3. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your settings:
- Database credentials
- Mail server settings
- Payment gateway keys
- APP_ENV=production
- APP_DEBUG=false

4. **Database Setup**
```bash
php artisan migrate
php artisan db:seed
```

5. **Optimize for Production**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. **Set Permissions**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Web Server Configuration

#### Nginx
```nginx
server {
    listen 80;
    server_name radtik.example.com;
    root /var/www/radtik/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Post-Deployment

1. **Change Default Password**
   - Login with superadmin@example.com
   - Immediately change password

2. **Configure Payment Gateways**
   - Add real API credentials
   - Test transactions

3. **Set Up Monitoring**
   - Configure error logging
   - Set up backups
   - Monitor disk space

4. **SSL Certificate**
```bash
certbot --nginx -d radtik.example.com
```

## Updates
```bash
git pull origin main
composer install --no-dev
npm install && npm run build
php artisan migrate
php artisan optimize:clear
```
MD,
                'is_active' => true,
            ],
        ];

        foreach ($articles as $article) {
            DocumentationArticle::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($article['title'])],
                $article
            );
        }

        $this->command->info('✅ Documentation articles created');
    }
}
