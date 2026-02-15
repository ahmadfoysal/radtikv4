# Router NAS Identifier Implementation

## Overview

Implemented automatic generation of unique NAS (Network Access Server) identifiers when routers are created. The NAS identifier uses the router name in kebab-case as a prefix, followed by a timestamp to ensure uniqueness.

## Changes Made

### 1. Router Creation Component

**File:** `app/Livewire/Router/Create.php`

- Added `Illuminate\Support\Str` import for slug generation
- Created `generateUniqueNasIdentifier()` method that:
    - Converts router name to kebab-case using `Str::slug()`
    - Appends timestamp in format `YmdHis` (e.g., 20260215143022)
    - Ensures uniqueness by checking database and adding counter if needed
    - Returns format: `{kebab-case-name}-{timestamp}[-{counter}]`
- Updated `save()` method to generate and store NAS identifier during router creation

**Example NAS Identifiers:**

- Router name: "My Test Router" → `my-test-router-20260215143022`
- Router name: "Office WiFi #1" → `office-wifi-1-20260215143022`
- Duplicate creation → `my-test-router-20260215143022-1`

### 2. Router Factory

**File:** `database/factories/RouterFactory.php`

- Updated factory to generate NAS identifiers for test data
- Uses same kebab-case prefix + timestamp pattern
- Adds random 3-digit number to timestamp for additional uniqueness

### 3. Router Display View

**File:** `resources/views/livewire/router/show.blade.php`

- Added NAS identifier display in router details section
- Shows under "NAS ID" label with identification icon
- Uses monospace font for better readability
- Shows "N/A" if identifier is missing

### 4. Backfill Command

**File:** `app/Console/Commands/GenerateRouterNasIdentifiers.php`

Created artisan command to generate NAS identifiers for existing routers:

```bash
# Generate NAS identifiers only for routers without one
php artisan routers:generate-nas-identifiers

# Force regenerate for all routers (including those with existing identifiers)
php artisan routers:generate-nas-identifiers --force
```

Features:

- Progress bar display
- Skips routers that already have identifiers (unless --force used)
- Ensures uniqueness for each router
- Shows count of processed routers

### 5. Test Suite

**File:** `tests/Feature/RouterNasIdentifierTest.php`

Comprehensive test coverage:

- ✅ Router created with unique NAS identifier in kebab-case
- ✅ Multiple routers with same name have different identifiers
- ✅ Special characters properly handled (removed/converted)
- ✅ Factory-created routers have NAS identifiers

## Database Schema

The `nas_identifier` field already exists in the routers table:

- Type: `string(100)`
- Unique: Yes
- Nullable: Yes (for backward compatibility)

## Usage Examples

### Creating a New Router

```php
// Through Livewire component
$router = Router::create([
    'name' => 'Office Router',
    'address' => '192.168.1.1',
    // ... other fields
]);
// nas_identifier automatically generated: "office-router-20260215143022"
```

### Factory Usage

```php
// In tests or seeders
$router = Router::factory()->create([
    'user_id' => $admin->id,
    'name' => 'Test Router'
]);
// nas_identifier automatically generated with unique timestamp
```

### Backfilling Existing Data

```bash
# One-time command to update existing routers
php artisan routers:generate-nas-identifiers
```

## Design Decisions

1. **Kebab-case format:** Easy to read, URL-safe, standard convention
2. **Timestamp-based:** Ensures uniqueness even for simultaneous creation
3. **Immutable after creation:** Not updated when router name changes (maintains RADIUS consistency)
4. **Counter fallback:** Handles edge case of identical timestamps
5. **Database unique constraint:** Enforces uniqueness at database level

## Migration Path

For existing installations with routers:

1. Run the backfill command: `php artisan routers:generate-nas-identifiers`
2. All existing routers will get NAS identifiers
3. New routers automatically get identifiers on creation

## RADIUS Integration

The NAS identifier is stored in the `nas_identifier` field and can be used in FreeRADIUS configuration to uniquely identify each router/NAS device. This is particularly useful for:

- Accounting records
- Authorization policies
- Multi-router deployments
- Client-specific RADIUS configurations
