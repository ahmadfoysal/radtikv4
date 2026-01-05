# Permission Usage Guide

## Overview
This guide explains how to use the Gate system for permission checks in your Laravel application. The Gate::before() callback ensures that admins can do everything, while resellers need specific permissions.

## How It Works

The `Gate::before()` callback in `AppServiceProvider`:
- **Admins/Superadmins**: Automatically allowed for all actions
- **Resellers**: Must have specific permissions assigned

## Usage Methods

### 1. In Livewire Components

#### Method A: Using `Gate::allows()` or `Gate::denies()`

```php
use Illuminate\Support\Facades\Gate;

public function generateVouchers()
{
    // Check permission
    if (!Gate::allows('generate_vouchers')) {
        abort(403, 'You do not have permission to generate vouchers.');
    }
    
    // Your code here
}
```

#### Method B: Using `$this->authorize()` (Recommended)

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GenerateVouchers extends Component
{
    use AuthorizesRequests;

    public function mount()
    {
        // Automatically aborts with 403 if permission denied
        $this->authorize('generate_vouchers');
    }

    public function save()
    {
        $this->authorize('generate_vouchers');
        // Your save logic
    }
}
```

#### Method C: Using `abort_unless()` with `can()`

```php
public function mount()
{
    abort_unless(auth()->user()->can('generate_vouchers'), 403);
    // Your code
}
```

### 2. In Blade Views

#### Using `@can` and `@cannot` directives

```blade
@can('generate_vouchers')
    <x-mary-button label="Generate Vouchers" wire:click="generate" />
@endcan

@cannot('delete_vouchers')
    <p class="text-warning">You don't have permission to delete vouchers.</p>
@endcannot
```

#### Using `@if` with `Gate::allows()`

```blade
@if(Gate::allows('view_router_details'))
    <x-mary-button label="View Details" href="/router/{{ $router->id }}" />
@endif
```

### 3. In Controllers

```php
use Illuminate\Support\Facades\Gate;

public function index()
{
    Gate::authorize('view_vouchers');
    // Or use:
    $this->authorize('view_vouchers');
    
    return view('vouchers.index');
}
```

### 4. In Route Middleware

You can create middleware for specific permissions:

```php
// In routes/web.php
Route::middleware(['auth', 'can:generate_vouchers'])->group(function () {
    Route::get('/vouchers/generate', App\Livewire\Voucher\Generate::class);
});
```

## Examples for Your Application

### Example 1: Voucher Generation

```php
// app/Livewire/Voucher/Generate.php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Generate extends Component
{
    use AuthorizesRequests;

    public function mount()
    {
        $this->authorize('generate_vouchers');
    }

    public function save()
    {
        $this->authorize('generate_vouchers');
        // Generate vouchers
    }
}
```

### Example 2: Router Actions

```php
// app/Livewire/Router/Show.php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Show extends Component
{
    use AuthorizesRequests;

    public function deleteRouter()
    {
        $this->authorize('delete_assigned_routers');
        // Delete router
    }

    public function pingRouter()
    {
        $this->authorize('ping_assigned_routers');
        // Ping router
    }
}
```

### Example 3: Hotspot User Management

```php
// app/Livewire/HotspotUsers/Create.php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Create extends Component
{
    use AuthorizesRequests;

    public function mount()
    {
        $this->authorize('create_single_user');
    }
}
```

### Example 4: Conditional UI Elements

```blade
{{-- resources/views/livewire/router/show.blade.php --}}
@can('delete_assigned_routers')
    <x-mary-button label="Delete Router" wire:click="deleteRouter" class="btn-error" />
@endcan

@can('ping_assigned_routers')
    <x-mary-button label="Ping Router" wire:click="pingRouter" class="btn-info" />
@endcan

@can('view_router_statistics')
    <x-mary-card>
        <x-slot name="title">Statistics</x-slot>
        <!-- Statistics content -->
    </x-mary-card>
@endcan
```

## Permission Names Reference

### Router Permissions
- `view_assigned_routers`
- `view_router_details`
- `edit_assigned_routers`
- `delete_assigned_routers`
- `ping_assigned_routers`
- `view_router_status`
- `view_router_logs`
- `view_router_statistics`
- `manage_router_vouchers`
- `manage_router_profiles`
- `sync_router_data`
- `import_router_configs`

### Voucher Permissions
- `view_vouchers`
- `generate_vouchers`
- `generate_voucher_batches`
- `print_vouchers`
- `print_single_voucher`
- `reset_voucher`
- `bulk_delete_vouchers`

### Hotspot User Permissions
- `create_single_user`
- `view_active_sessions`
- `view_session_cookies`
- `view_hotspot_logs`

## Important Notes

1. **Admins automatically pass all checks** - No need to assign permissions to admin role
2. **Resellers need explicit permissions** - Assign via Reseller Permissions page
3. **Always check permissions** - Don't rely on UI hiding alone for security
4. **Use `authorize()` in Livewire** - It automatically handles abort(403)
5. **Gate::before() runs first** - Admin check happens before permission lookup

## Testing Permissions

```php
// In a test or tinker
$reseller = User::role('reseller')->first();

// Assign permission
$reseller->givePermissionTo('generate_vouchers');

// Check permission
$reseller->can('generate_vouchers'); // true
$reseller->can('delete_vouchers'); // false (not assigned)

// Admin check
$admin = User::role('admin')->first();
$admin->can('generate_vouchers'); // true (admin bypass)
$admin->can('any_permission'); // true (admin bypass)
```
