# Router Permissions Application Documentation

This document describes where and how the router permissions from `PermissionSeed.php` (lines 24-30) have been applied throughout the application.

## Permissions Applied

The following permissions have been implemented:

1. `add_router` - Permission to add/create new routers
2. `edit_router` - Permission to edit existing routers
3. `delete_router` - Permission to delete routers
4. `view_router` - Permission to view router details and list
5. `ping_router` - Permission to ping/test router connectivity
6. `sync_router_data` - Permission to synchronize router data (scripts, schedulers, profiles)
7. `import_router_configs` - Permission to import router configurations

---

## 1. `add_router` Permission

### Applied In:

-   **File**: `app/Livewire/Router/Create.php`
-   **Component**: `App\Livewire\Router\Create`

### Methods Protected:

1. **`mount()` method** (Line ~50)

    - Checks permission when the component loads
    - Prevents unauthorized users from accessing the router creation form

    ```php
    public function mount(): void
    {
        $this->authorize('add_router');
        // ... rest of the method
    }
    ```

2. **`save()` method** (Line ~57)
    - Checks permission before creating a new router
    - Prevents unauthorized users from submitting the form
    ```php
    public function save()
    {
        $this->authorize('add_router');
        // ... router creation logic
    }
    ```

### Route:

-   `/router/add` - Route: `routers.create`

---

## 2. `edit_router` Permission

### Applied In:

-   **File**: `app/Livewire/Router/Edit.php`
-   **Component**: `App\Livewire\Router\Edit`

### Methods Protected:

1. **`mount()` method** (Line ~52)

    - Checks permission when loading the edit form
    - Replaced previous role-based check with permission check

    ```php
    public function mount(Router $router): void
    {
        $this->authorize('edit_router');
        // ... rest of the method
    }
    ```

2. **`update()` method** (Line ~71)
    - Checks permission before updating router data
    - Prevents unauthorized users from saving changes
    ```php
    public function update(): void
    {
        $this->authorize('edit_router');
        // ... router update logic
    }
    ```

### Route:

-   `/router/{router}/edit` - Route: `routers.edit`

---

## 3. `delete_router` Permission

### Applied In:

-   **File**: `app/Livewire/Router/Show.php`
-   **Component**: `App\Livewire\Router\Show`

### Methods Protected:

1. **`deleteRouter()` method** (Line ~485)
    - Checks permission before deleting a router
    - Includes confirmation step (user must type "delete")
    ```php
    public function deleteRouter(): void
    {
        $this->authorize('delete_router');
        // ... deletion logic with confirmation
    }
    ```

### Route:

-   `/router/{router}` - Route: `routers.show` (delete action within the show page)

---

## 4. `view_router` Permission

### Applied In:

1. **File**: `app/Livewire/Router/Show.php`

    - **Component**: `App\Livewire\Router\Show`
    - **Method**: `mount()` (Line ~68)
    - Replaced previous admin role check with permission check

    ```php
    public function mount(Router $router): void
    {
        $this->authorize('view_router');
        // ... rest of the method
    }
    ```

2. **File**: `app/Livewire/Router/Index.php`
    - **Component**: `App\Livewire\Router\Index`
    - **Method**: `mount()` (Line ~31)
    - Replaced previous admin role check with permission check
    ```php
    public function mount(): void
    {
        $this->authorize('view_router');
    }
    ```

### Routes:

-   `/routers` - Route: `routers.index` (router list)
-   `/router/{router}` - Route: `routers.show` (router details)

---

## 5. `ping_router` Permission

### Applied In:

-   **File**: `app/Livewire/Router/Index.php`
-   **Component**: `App\Livewire\Router\Index`

### Methods Protected:

1. **`ping()` method** (Line ~72)
    - Checks permission before pinging a router
    - Tests router connectivity via RouterOS API
    ```php
    public function ping(int $id): void
    {
        $this->authorize('ping_router');
        // ... ping logic
    }
    ```

### Route:

-   `/routers` - Route: `routers.index` (ping action button in the router list)

---

## 6. `sync_router_data` Permission

### Applied In:

-   **File**: `app/Livewire/Router/Show.php`
-   **Component**: `App\Livewire\Router\Show`

### Methods Protected:

1. **`syncScripts()` method** (Line ~377)

    - Checks permission before syncing router scripts
    - Installs/updates RADTik scripts on the router

    ```php
    public function syncScripts(): void
    {
        $this->authorize('sync_router_data');
        // ... script sync logic
    }
    ```

2. **`syncSchedulers()` method** (Line ~394)

    - Checks permission before syncing router schedulers
    - Updates scheduled tasks on the router

    ```php
    public function syncSchedulers(): void
    {
        $this->authorize('sync_router_data');
        // ... scheduler sync logic
    }
    ```

3. **`syncProfiles()` method** (Line ~442)
    - Checks permission before syncing router profiles
    - Pulls hotspot profiles from the router
    ```php
    public function syncProfiles(): void
    {
        $this->authorize('sync_router_data');
        // ... profile sync logic
    }
    ```

### Route:

-   `/router/{router}` - Route: `routers.show` (sync action buttons in the router details page)

---

## 7. `import_router_configs` Permission

### Applied In:

-   **File**: `app/Livewire/Router/Import.php`
-   **Component**: `App\Livewire\Router\Import`

### Methods Protected:

1. **`mount()` method** (New method added)

    - Checks permission when the import page loads
    - Prevents unauthorized users from accessing the import form

    ```php
    public function mount(): void
    {
        $this->authorize('import_router_configs');
    }
    ```

2. **`import()` method** (Line ~51)
    - Checks permission before importing router configurations
    - Supports importing from Mikhmon config files
    ```php
    public function import(): void
    {
        $this->authorize('import_router_configs');
        // ... import logic
    }
    ```

### Route:

-   `/router/import` - Route: `routers.import`

---

## Implementation Details

### Authorization Method Used

All permissions are checked using Laravel's `$this->authorize()` method, which:

-   Automatically aborts with HTTP 403 if permission is denied
-   Works seamlessly with the `Gate::before()` callback in `AppServiceProvider`
-   Allows admins to bypass all checks (as configured in `AppServiceProvider`)
-   Requires resellers to have specific permissions assigned

### Traits Added

All affected components now use the `AuthorizesRequests` trait:

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ComponentName extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;
    // ...
}
```

### Permission Flow

1. User attempts to access a protected method
2. `$this->authorize('permission_name')` is called
3. `Gate::before()` in `AppServiceProvider` intercepts:
    - If user is admin → Returns `true` (allowed)
    - If user is reseller → Checks if they have the permission
    - If permission exists → Returns `true` (allowed)
    - If permission doesn't exist → Returns `false` (denied)
4. If denied, Laravel automatically returns HTTP 403

---

## Summary Table

| Permission              | Component     | Methods                                               | Route                   |
| ----------------------- | ------------- | ----------------------------------------------------- | ----------------------- |
| `add_router`            | Router\Create | `mount()`, `save()`                                   | `/router/add`           |
| `edit_router`           | Router\Edit   | `mount()`, `update()`                                 | `/router/{router}/edit` |
| `delete_router`         | Router\Show   | `deleteRouter()`                                      | `/router/{router}`      |
| `view_router`           | Router\Show   | `mount()`                                             | `/router/{router}`      |
| `view_router`           | Router\Index  | `mount()`                                             | `/routers`              |
| `ping_router`           | Router\Index  | `ping()`                                              | `/routers`              |
| `sync_router_data`      | Router\Show   | `syncScripts()`, `syncSchedulers()`, `syncProfiles()` | `/router/{router}`      |
| `import_router_configs` | Router\Import | `mount()`, `import()`                                 | `/router/import`        |

---

## Testing Recommendations

1. **Admin Access**: Verify that admins can access all router operations without permission assignments
2. **Reseller Access**: Test that resellers can only access operations for which they have permissions
3. **Unauthorized Access**: Verify that users without permissions receive 403 errors
4. **Permission Assignment**: Test the Reseller Permissions page to assign/revoke permissions

---

## Notes

-   All previous role-based checks (`hasRole('admin')`) have been replaced with permission checks
-   The `Gate::before()` callback ensures admins automatically pass all permission checks
-   Resellers must have permissions explicitly assigned via the Reseller Permissions management page
-   Permission checks are performed at both the component mount level (page access) and method level (action access)
