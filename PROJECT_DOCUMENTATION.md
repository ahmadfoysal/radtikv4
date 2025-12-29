# RADTik v4 - Project Documentation

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Architecture](#architecture)
4. [Core Features](#core-features)
5. [User Management & Authentication](#user-management--authentication)
6. [Router Management](#router-management)
7. [Voucher System](#voucher-system)
8. [Billing & Payment System](#billing--payment-system)
9. [Package & Subscription System](#package--subscription-system)
10. [Support Ticket System](#support-ticket-system)
11. [Knowledgebase & Documentation](#knowledgebase--documentation)
12. [Activity Logging](#activity-logging)
13. [Hotspot Management](#hotspot-management)
14. [RADIUS Integration](#radius-integration)
15. [API Integration](#api-integration)
16. [Database Schema](#database-schema)
17. [Testing](#testing)
18. [Deployment](#deployment)

---

## Project Overview

**RADTik v4** is a comprehensive MikroTik router management system built with Laravel. It provides a complete solution for managing routers, vouchers, billing, subscriptions, and user access control for WiFi hotspot services.

### Key Capabilities

-   **Router Management**: Add, configure, and manage MikroTik routers
-   **Voucher System**: Generate, manage, and print WiFi vouchers
-   **Billing System**: Handle payments, invoices, and balance management
-   **Subscription Management**: Package-based router subscriptions with auto-renewal
-   **Multi-tenant Support**: Superadmin, Admin, and Reseller roles
-   **MikroTik Integration**: Real-time synchronization with MikroTik routers
-   **RADIUS Integration**: Support for RADIUS authentication
-   **Support System**: Ticket management for customer support
-   **Knowledge Base**: Self-service help documentation

---

## Technology Stack

### Backend

-   **Framework**: Laravel 12
-   **PHP Version**: PHP 8.2+
-   **Database**: MySQL/SQLite compatible
-   **Authentication**: Laravel Fortify with Two-Factor Authentication
-   **Permissions**: Spatie Laravel Permission

### Frontend

-   **UI Framework**: Livewire 3
-   **Component Library**: MaryUI 2.4
-   **CSS Framework**: TailwindCSS 4.1
-   **UI Components**: DaisyUI 5.3
-   **JavaScript**: Vite 7.0

### Third-Party Integrations

-   **MikroTik API**: evilfreelancer/routeros-api-php 1.6
-   **Payment Gateways**:
    -   Cryptomus
    -   PayStation

### Development Tools

-   **Testing**: Pest PHP 4.1
-   **Code Quality**: Laravel Pint 1.18
-   **Logging**: Laravel Pail 1.2

---

## Architecture

### Project Structure

```
radtikv4/
├── app/
│   ├── Actions/              # Fortify actions
│   ├── Console/              # Artisan commands
│   ├── Gateway/              # Payment gateway implementations
│   ├── Http/
│   │   ├── Controllers/      # HTTP controllers
│   │   └── Middleware/       # Custom middleware
│   ├── Livewire/             # Livewire components
│   │   ├── Admin/           # Admin management
│   │   ├── Auth/            # Authentication
│   │   ├── Billing/         # Billing management
│   │   ├── HotspotUsers/    # Hotspot user management
│   │   ├── Knowledgebase/   # Knowledge base
│   │   ├── Package/         # Package management
│   │   ├── Profile/         # User profile management
│   │   ├── Router/          # Router management
│   │   ├── Settings/        # System settings
│   │   ├── Tickets/         # Support tickets
│   │   ├── User/            # User management
│   │   ├── Voucher/         # Voucher management
│   │   └── Zone/            # Zone management
│   ├── MikroTik/            # MikroTik integration
│   │   ├── Actions/         # MikroTik actions
│   │   ├── Client/          # API client
│   │   ├── Installer/       # Router installer
│   │   └── Scripts/         # Router scripts
│   ├── Models/              # Eloquent models
│   │   └── Traits/          # Model traits
│   ├── Policies/            # Authorization policies
│   ├── Providers/           # Service providers
│   ├── RadiusServer/        # RADIUS server client
│   ├── Services/            # Business logic services
│   │   ├── Radius/          # RADIUS services
│   │   └── Subscriptions/   # Subscription services
│   └── Support/             # Helper classes
├── database/
│   ├── factories/           # Model factories
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── resources/
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript
│   └── views/               # Blade templates
│       ├── components/      # Reusable components
│       ├── livewire/        # Livewire views
│       └── partials/        # Partial views
├── routes/                  # Route definitions
├── tests/                   # Test suites
└── docs/                    # Additional documentation
```

### Design Patterns

-   **Service Layer**: Business logic separated into service classes
-   **Repository Pattern**: Data access through Eloquent models
-   **Policy Pattern**: Authorization through Laravel policies
-   **Trait Pattern**: Reusable functionality via traits
-   **Observer Pattern**: Activity logging via model events

---

## Core Features

### 1. Dashboard

-   Overview of system statistics
-   Recent activity feed
-   Quick access to common tasks
-   Role-based dashboard content

### 2. User Management

-   Create, edit, and manage users
-   Role assignment (Superadmin, Admin, Reseller)
-   Permission management
-   User profile management
-   Balance and commission tracking

### 3. Router Management

-   Add and configure MikroTik routers
-   Router import functionality
-   Zone assignment
-   Subscription management
-   Router status monitoring
-   API key generation for router authentication

### 4. Voucher System

-   Individual voucher creation
-   Bulk voucher generation
-   Voucher templates
-   Voucher printing (single and bulk)
-   Voucher activation tracking
-   Voucher expiration management
-   Batch management

### 5. Billing System

-   Account balance management
-   Invoice generation and tracking
-   Payment gateway integration
-   Manual balance adjustments
-   Transaction history
-   Payment callbacks handling

### 6. Package & Subscription System

-   Package creation and management
-   Router subscription with packages
-   Auto-renewal functionality
-   Subscription expiry checking
-   Package upgrade/downgrade
-   Billing cycle management (monthly/yearly)

### 7. Support Ticket System

-   Ticket creation and management
-   Status workflow (open → in_progress → solved → closed)
-   Ticket assignment
-   Priority levels
-   Role-based access control

### 8. Knowledgebase & Documentation

-   Article management
-   Category-based organization
-   Search functionality
-   Responsive design
-   Separate knowledgebase and documentation sections

### 9. Activity Logging

-   Automatic CRUD operation logging
-   User action tracking
-   IP address and user agent logging
-   Audit trail for compliance
-   Sensitive data sanitization

### 10. Hotspot Management

-   Hotspot user creation
-   Active session monitoring
-   Session cookie management
-   Hotspot logs viewing
-   User synchronization with MikroTik

### 11. RADIUS Integration

-   RADIUS server configuration
-   RADIUS profile management
-   User profile assignment
-   RADIUS authentication support

### 12. Zone Management

-   Multi-location router organization
-   Zone-based router grouping
-   Zone assignment for routers

---

## User Management & Authentication

### User Roles

#### Superadmin

-   Full system access
-   User management (all roles)
-   Router management
-   Package management
-   Payment gateway configuration
-   Ticket management (all tickets)
-   System settings

#### Admin

-   Router management (assigned routers)
-   Voucher management
-   Reseller management
-   Ticket creation (own tickets)
-   Limited system access

#### Reseller

-   Router management (assigned routers only)
-   Voucher management
-   Ticket creation (own tickets)
-   Limited access based on permissions

### Authentication Features

-   **Laravel Fortify**: Authentication system
-   **Two-Factor Authentication**: 2FA support via Fortify
-   **Password Hashing**: Secure password storage
-   **Session Management**: Laravel session handling
-   **Remember Me**: Persistent login option

### User Model Features

-   Balance tracking
-   Commission tracking
-   Subscription management
-   Profile image support
-   Phone verification
-   Last login tracking
-   Account expiration dates

---

## Router Management

### Router Features

-   **Connection Settings**: IP address, port, SSH port
-   **Authentication**: Username and encrypted password storage
-   **Zone Assignment**: Organize routers by location
-   **RADIUS Integration**: Enable/disable RADIUS usage
-   **Voucher Template**: Assign voucher templates
-   **API Key**: Unique app_key for API authentication
-   **Logo Upload**: Custom router branding
-   **Notes**: Additional information storage

### Router Operations

-   **Create Router**: Add new MikroTik router
-   **Edit Router**: Update router configuration
-   **Import Routers**: Bulk import from file
-   **View Router Details**: Detailed router information
-   **Delete Router**: Remove router from system

### Router Subscription

Routers can be subscribed to packages with:

-   Subscription start and end dates
-   Auto-renewal option
-   Package snapshot storage
-   Subscription expiry checking via middleware

### MikroTik Integration

-   **API Communication**: Real-time router communication
-   **User Synchronization**: Sync hotspot users
-   **Profile Synchronization**: Sync user profiles
-   **Script Installation**: Automated script deployment
-   **Connection Testing**: Verify router connectivity

---

## Voucher System

### Voucher Features

-   **Username/Password**: Voucher credentials
-   **Router Assignment**: Link vouchers to routers
-   **Profile Assignment**: Assign user profiles
-   **Expiration Dates**: Set voucher expiry
-   **Status Tracking**: Active, expired, used status
-   **Activation Tracking**: Track first-time activation
-   **Batch Management**: Organize vouchers in batches
-   **MAC Address**: Optional MAC binding

### Voucher Operations

-   **Create Voucher**: Individual voucher creation
-   **Bulk Generate**: Generate multiple vouchers
-   **Edit Voucher**: Update voucher details
-   **Delete Voucher**: Remove vouchers
-   **Print Vouchers**: Single and bulk printing
-   **Bulk Manager**: Manage voucher batches

### Voucher Templates

-   Customizable voucher designs
-   Template assignment to routers
-   Print formatting options

### Voucher Logging

-   Activation logs
-   Usage tracking
-   Batch reports
-   User activity tracking

---

## Billing & Payment System

### Billing Features

-   **Account Balance**: User balance tracking
-   **Invoice Management**: Generate and track invoices
-   **Transaction History**: Complete transaction log
-   **Manual Adjustments**: Admin balance adjustments
-   **Payment Gateways**: Multiple gateway support

### Payment Gateways

#### Cryptomus

-   Cryptocurrency payments
-   Callback handling
-   Transaction verification

#### PayStation

-   Traditional payment processing
-   Callback handling
-   Payment status tracking

### Invoice System

-   **Invoice Types**: Debit and credit invoices
-   **Invoice Categories**:
    -   `subscription`: User subscription to package
    -   `subscription_renewal`: User subscription renewal
    -   `manual_adjustment`: Manual balance changes
    -   `payment`: Payment received
-   **Invoice Linking**: Link invoices to users
-   **Invoice Tracking**: Complete invoice history

### Balance Management

-   **Add Balance**: Users can add balance via payment gateways
-   **Manual Adjustment**: Admins can adjust balances
-   **Balance Validation**: Check balance before operations
-   **Transaction Logging**: All balance changes logged

---

## Package & Subscription System

### Package Features

-   **Package Name**: Descriptive package name
-   **Pricing**: Monthly and yearly pricing
-   **User Limits**: Maximum users per router
-   **Billing Cycle**: Monthly or yearly
-   **Early Pay Discount**: Discount for early payment
-   **Auto-Renewal**: Allow/disallow auto-renewal
-   **Description**: Package details

### Subscription Service

User subscription management handles:

-   Balance validation before subscription
-   Subscription creation with billing
-   Subscription renewal
-   Package tracking via subscriptions table
-   Subscription date management

### Subscription Flow

1. **Check Balance**: Validate user has sufficient balance
2. **Deduct Balance**: Charge package price
3. **Create Invoice**: Generate invoice record
4. **Create Subscription**: Store subscription record with package details
5. **Set Dates**: Set subscription start and end dates
6. **Enable Auto-Renewal**: If configured

### Auto-Renewal

-   **Command**: `subscriptions:renew`
-   **Scheduling**: Can be scheduled via Laravel scheduler
-   **Expiry Window**: Configurable days before expiry
-   **Process**: Automatically renews user subscriptions with sufficient balance
-   **Auto-Renewal Check**: Only renews if `auto_renew` is true
-   **Error Handling**: Logs failures but continues processing

### Subscription Middleware

`CheckRouterSubscription` middleware:

-   Validates router token (app_key)
-   Checks if router owner has active subscription
-   Blocks access for users without active subscriptions
-   Applied to all MikroTik API routes

---

## Support Ticket System

### Ticket Features

-   **Subject**: Ticket title
-   **Description**: Detailed issue description
-   **Status**: open, in_progress, solved, closed
-   **Priority**: low, normal, high
-   **Owner**: User who owns the ticket
-   **Assignee**: User assigned to handle ticket
-   **Timestamps**: Created, solved, closed dates

### Ticket Workflow

```
open → in_progress → solved → closed
```

### Role-Based Access

-   **Admin/Reseller**: Create and view own tickets
-   **Superadmin**: Full access to all tickets
    -   View all tickets
    -   Create tickets for any user
    -   Assign tickets
    -   Update status
    -   Delete tickets

### Ticket Messages

-   Thread-based messaging
-   User and admin communication
-   Message history tracking

---

## Knowledgebase & Documentation

### Knowledgebase Articles

-   **Title**: Article title
-   **Slug**: URL-friendly identifier (auto-generated)
-   **Category**: Article category
-   **Content**: Article body
-   **Status**: Active/inactive

### Documentation Articles

Same structure as knowledgebase articles but for technical documentation.

### Features

-   **Search**: Live search with debounce
-   **Category Filter**: Filter by category
-   **Pagination**: 12 items per page
-   **Responsive Design**: Mobile-friendly layout
-   **SEO-Friendly URLs**: Slug-based routing

### Seeded Content

-   **Knowledgebase**: 7 articles covering common topics
-   **Documentation**: 6 technical documentation articles

---

## Activity Logging

### Logged Actions

-   **Created**: Model creation
-   **Updated**: Model updates (with old/new values)
-   **Deleted**: Model deletion
-   **Custom**: Custom action logging

### Logged Models

All major models have activity logging enabled:

-   User, Router, Voucher, Package
-   Invoice, Ticket, TicketMessage
-   RadiusServer, RadiusProfile, UserProfile
-   Zone, VoucherTemplate, VoucherBatch
-   PaymentGateway, ResellerRouter
-   KnowledgebaseArticle, DocumentationArticle

### Security Features

-   **Sensitive Data Sanitization**: Passwords, tokens automatically redacted
-   **IP Address Tracking**: Request IP logging
-   **User Agent Tracking**: Browser/device information
-   **User Context**: Links actions to authenticated users

### Activity Log UI

-   Search functionality
-   Action type filtering
-   Pagination
-   Human-readable descriptions
-   Time-ago formatting

---

## Hotspot Management

### Hotspot User Operations

-   **Create Hotspot User**: Add new hotspot user
-   **Active Sessions**: View active user sessions
-   **Session Cookies**: Manage session cookies
-   **Hotspot Logs**: View connection logs
-   **User Sync**: Synchronize with MikroTik routers

### MikroTik API Endpoints

-   `GET /mikrotik/api/pull-inactive-users`: Pull inactive vouchers
-   `GET /mikrotik/api/pull-active-users`: Pull active users
-   `POST /mikrotik/api/push-active-users`: Receive usage data
-   `GET /mikrotik/api/sync-orphans`: Clean up orphaned users
-   `GET /mikrotik/api/pull-profiles`: Pull user profiles
-   `GET /mikrotik/api/pull-updated-profiles`: Pull updated profiles

All endpoints protected by subscription middleware.

---

## RADIUS Integration

### RADIUS Server

-   **Server Configuration**: IP, port, secret
-   **Server Assignment**: Assign to routers
-   **Authentication**: User authentication via RADIUS
-   **Accounting**: Usage tracking

### RADIUS Profiles

-   **Profile Name**: Profile identifier
-   **Bandwidth Limits**: Upload/download limits
-   **Session Timeout**: Maximum session duration
-   **Idle Timeout**: Idle session timeout
-   **Shared Users**: Maximum concurrent users

### User Profiles

-   **Profile Management**: Create and manage profiles
-   **Profile Assignment**: Assign to vouchers/users
-   **Profile Sync**: Synchronize with MikroTik

---

## API Integration

### MikroTik API

-   **Authentication**: Token-based (app_key)
-   **Subscription Check**: Middleware validation
-   **User Sync**: Bidirectional user synchronization
-   **Profile Sync**: Profile synchronization
-   **Script Deployment**: Automated script installation

### Payment Gateway APIs

-   **Cryptomus API**: Cryptocurrency payments
-   **PayStation API**: Traditional payments
-   **Callback Handling**: Secure callback processing
-   **Webhook Verification**: Payment verification

### API Security

-   **CSRF Protection**: Excluded for callbacks
-   **Token Validation**: Router token verification
-   **Subscription Validation**: Expiry checking
-   **Rate Limiting**: Laravel rate limiting

---

## Database Schema

### Core Tables

-   `users`: User accounts and authentication
-   `routers`: MikroTik router configurations
-   `vouchers`: WiFi voucher credentials
-   `packages`: Subscription packages
-   `invoices`: Billing invoices
-   `zones`: Router zones/locations
-   `user_profiles`: User profile configurations
-   `radius_servers`: RADIUS server configurations
-   `radius_profiles`: RADIUS profile definitions
-   `voucher_templates`: Voucher print templates
-   `voucher_batches`: Voucher batch management
-   `voucher_logs`: Voucher activation logs
-   `tickets`: Support tickets
-   `ticket_messages`: Ticket message threads
-   `payment_gateways`: Payment gateway configurations
-   `reseller_router`: Reseller-router assignments
-   `knowledgebase_articles`: Knowledge base articles
-   `documentation_articles`: Documentation articles
-   `activity_logs`: System activity logs

### Relationships

-   Users → Routers (one-to-many)
-   Users → Vouchers (one-to-many)
-   Routers → Vouchers (one-to-many)
-   Routers → Zones (many-to-one)
-   Routers → Packages (JSON storage)
-   Users → Invoices (one-to-many)
-   Routers → Invoices (one-to-many)
-   Resellers → Routers (many-to-many)
-   Users → Tickets (multiple relationships)

---

## Testing

### Test Framework

-   **Pest PHP**: Modern PHP testing framework
-   **Laravel Testing**: Laravel test utilities
-   **Feature Tests**: Full application testing
-   **Unit Tests**: Component testing

### Test Coverage

-   Activity logging tests
-   Router subscription tests
-   Billing service tests
-   Middleware tests
-   Subscription renewal command tests

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=ActivityLoggerTest

# Run with coverage
php artisan test --coverage
```

---

## Deployment

### Environment Setup

1. **Install Dependencies**:

    ```bash
    composer install
    npm install
    ```

2. **Environment Configuration**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Database Setup**:

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

4. **Build Assets**:
    ```bash
    npm run build
    ```

### Production Considerations

-   **Queue Workers**: Set up queue workers for background jobs
-   **Scheduler**: Configure Laravel scheduler for auto-renewal
-   **Caching**: Enable route and config caching
-   **Logging**: Configure proper log rotation
-   **Backups**: Set up database backups
-   **SSL**: Enable HTTPS for payment callbacks

### Commands

-   `routers:renew-subscriptions`: Renew expiring router subscriptions
-   `queue:work`: Process queued jobs
-   `schedule:run`: Run scheduled tasks

---

## Additional Documentation

For detailed documentation on specific features, see:

-   `docs/TICKET_SYSTEM.md` - Support ticket system details
-   `ACTIVITY_LOGGING.md` - Activity logging system
-   `KNOWLEDGEBASE_DOCUMENTATION.md` - Knowledge base implementation
-   `PR_DESCRIPTION.md` - Subscription middleware implementation
-   `ACTIVITY_LOG_EXAMPLES.md` - Activity log usage examples

---

## Future Enhancements

### Planned Features

-   [ ] Voucher template design improvements (10 templates)
-   [ ] Enhanced permission system for resellers
-   [ ] Voucher log reporting and analytics
-   [ ] Advanced voucher log filtering
-   [ ] Email notifications for tickets
-   [ ] Real-time ticket updates
-   [ ] File attachments for tickets
-   [ ] Admin UI for knowledge base articles
-   [ ] WYSIWYG editor for articles
-   [ ] Article versioning
-   [ ] Multi-language support
-   [ ] Advanced analytics dashboard
-   [ ] Export functionality (CSV, Excel, PDF)
-   [ ] API documentation
-   [ ] Mobile app support

---

## Support & Maintenance

### Log Files

-   Application logs: `storage/logs/laravel.log`
-   Activity logs: Database table `activity_logs`
-   Voucher logs: Database table `voucher_logs`

### Troubleshooting

1. **Clear Caches**:

    ```bash
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    ```

2. **Check Logs**:

    ```bash
    tail -f storage/logs/laravel.log
    ```

3. **Database Issues**:
    ```bash
    php artisan migrate:status
    php artisan db:seed
    ```

---

## License

This project follows the RADTik project license.

---

## Version History

### Version 4.0 (Current)

**Major Features**:

-   ✅ Complete router management system
-   ✅ Voucher generation and management
-   ✅ Billing and payment integration
-   ✅ Package subscription system
-   ✅ Support ticket system
-   ✅ Knowledge base and documentation
-   ✅ Activity logging system
-   ✅ Hotspot user management
-   ✅ RADIUS integration
-   ✅ Multi-role user system
-   ✅ MikroTik API integration
-   ✅ Payment gateway integration (Cryptomus, PayStation)

**Technical Stack**:

-   Laravel 12
-   Livewire 3
-   MaryUI 2.4
-   TailwindCSS 4.1
-   Pest PHP 4.1

---

_Last Updated: December 2025_
