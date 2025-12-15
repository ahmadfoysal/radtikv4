# RADTik v4 - AI Coding Agent Instructions

## Project Overview

RADTik v4 is a comprehensive MikroTik router management system built with Laravel 12, providing WiFi hotspot services, voucher management, billing, and multi-tenant router administration.

## Tech Stack & Architecture

### Core Framework

-   **Laravel 12** with PHP 8.2+
-   **Livewire 3** for reactive UI components (primary frontend approach)
-   **MaryUI 2.4** component library with TailwindCSS 4.1 + DaisyUI 5.3
-   **Spatie Laravel Permission** for role-based access control
-   **Pest PHP 4.1** for testing

### Key Dependencies

-   **evilfreelancer/routeros-api-php**: MikroTik RouterOS API integration
-   **hasinhayder/tyro-login**: Two-factor authentication
-   Payment gateways: Cryptomus, PayStation (in `app/Gateway/`)

## Critical Architecture Patterns

### Livewire-First Development

All UI is built with Livewire components, not traditional controllers:

```php
// Livewire components are organized by domain in app/Livewire/
app/Livewire/
├── Router/         # Router management
├── Voucher/        # WiFi voucher system
├── HotspotUsers/   # User access management
├── Billing/        # Payment & invoicing
├── Tickets/        # Support system
```

### Permission-Based Authorization

Uses string-based permissions (NOT role checking) via Spatie Permission:

```php
// Standard pattern in Livewire components
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

public function mount(): void
{
    $this->authorize('view_router');  // Not hasRole('admin')!
}
```

**Key permissions**: `add_router`, `view_router`, `generate_vouchers`, `create_single_user`
See `database/seeders/PermissionSeed.php` for complete list.

### MikroTik Integration Layer

Custom abstraction for RouterOS API in `app/MikroTik/`:

-   `RouterClient`: Connection management with encrypted credentials
-   `Actions/RouterManager`: System resource queries
-   `Actions/HotspotUserManager`: User management operations

### Payment Gateway Contract Pattern

Payment providers implement `PaymentGatewayContract`:

```php
// app/Gateway/CryptomusGateway.php, PayStationGateway.php
public function createPayment(User $user, float $amount, array $meta = []): RedirectResponse|string
```

## Development Workflows

### Frontend Build

```bash
npm run dev     # Vite development server with hot reload
npm run build   # Production build (TailwindCSS + Vite)
```

### Testing

```bash
php artisan test                    # Run Pest test suite
php artisan test --filter=Ticket   # Test specific feature
```

Tests use RefreshDatabase and factory patterns. See `tests/Feature/` for examples.

### Database Management

```bash
php artisan migrate
php artisan db:seed                        # Seeds roles & permissions
php artisan db:seed --class=PermissionSeed # Permissions only
```

## Component Development Patterns

### Livewire Component Structure

```php
class Create extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;  // Standard traits

    #[Rule(['required', 'string', 'max:100'])]       // Livewire validation
    public string $name = '';

    public function mount(): void {
        $this->authorize('permission_name');          // Always check permissions
    }

    public function save() {
        $this->validate();                            // Standard validation
        // Business logic
        $this->success('Created successfully!');      // MaryUI toast
    }
}
```

### Form Validation

Use Livewire's `#[Rule]` attributes, not FormRequest classes:

```php
#[Rule(['required', 'email', 'max:255', 'unique:users,email'])]
public $email;
```

### MaryUI Component Usage

Standard UI patterns with MaryUI components:

```blade
<x-mary-card title="Create Router">
    <x-mary-form wire:submit="save">
        <x-mary-input label="Name" wire:model.live.debounce.500ms="name" />
        <x-mary-select label="Package" :options="$packages" wire:model.live="package_id" />
    </x-mary-form>
</x-mary-card>
```

### Router Operations

Always use the MikroTik layer, never direct API calls:

```php
// Correct approach
$routerManager = app(RouterManager::class);
$resource = $routerManager->getRouterResource($router);

// Through client for reachability
$client = app(RouterClient::class);
if (!$client->reachable($router)) { /* handle */ }
```

## Database & Model Conventions

### Model Traits

Key models use these traits:

-   `User`: `HasRoles, HasBilling, HasRouterBilling, LogsActivity`
-   Encrypted fields use `Crypt::encrypt()` for router passwords

### Factories for Testing

All models have factories in `database/factories/`. Router passwords are auto-encrypted:

```php
Router::factory()->create(['user_id' => $admin->id]);
```

## Security & Authorization

### Three-Tier Role System

1. **superadmin**: Full system access
2. **admin**: Manage routers, vouchers, users
3. **reseller**: Limited to assigned routers only

Admins automatically pass all permission checks via `Gate::before()`.

### Permission Assignment

Resellers need explicit permission assignment via `Admin/ResellerPermissions.php` component.

## File Structure Context

### Configuration

-   `config/livewire.php`: Layout set to `components.layouts.app`
-   `config/permission.php`: Spatie permission configuration
-   `routes/web.php`: All routes require authentication middleware

### Documentation

Project has extensive documentation:

-   `PROJECT_DOCUMENTATION.md`: Architecture overview
-   `PERMISSION_USAGE_GUIDE.md`: Authorization patterns
-   `ROUTER_PERMISSIONS_APPLICATION.md`: Permission implementation details
-   `AUDIT_REPORT.md`: Code quality findings

## Common Anti-Patterns to Avoid

1. **Role checking**: Use permissions, not `hasRole('admin')`
2. **Direct Eloquent in components**: Should use repositories (noted as technical debt)
3. **FormRequest validation**: Use Livewire `#[Rule]` attributes instead
4. **Traditional controllers**: Use Livewire components for UI interactions
5. **Direct RouterOS API calls**: Always go through MikroTik abstraction layer

## Testing Patterns

-   Use `RefreshDatabase` in all feature tests
-   Create roles in `beforeEach()`: `Role::create(['name' => 'admin'])`
-   Test Livewire components with `Livewire::test(Component::class)`
-   Factory pattern for model creation in tests
