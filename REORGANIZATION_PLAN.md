# RADTik v4 - Reorganization Plan

**Date**: 2025-01-27  
**Phase**: 2 - Reorganization Plan  
**Status**: Draft - Awaiting Approval

---

## Overview

This plan reorganizes the codebase to strictly follow the Project Rules while maintaining all existing business behavior. Changes are grouped by module/feature and designed to be implemented incrementally.

**Strategy**: 
- Start with foundational changes (strict typing, base classes)
- Then reorganize one module at a time
- Test after each module
- Keep changes small and reviewable

---

## Table of Contents

1. [Foundation Changes](#1-foundation-changes)
2. [User Management Module](#2-user-management-module)
3. [Router Management Module](#3-router-management-module)
4. [Voucher System Module](#4-voucher-system-module)
5. [Billing & Payment Module](#5-billing--payment-module)
6. [Package & Subscription Module](#6-package--subscription-module)
7. [Ticket System Module](#7-ticket-system-module)
8. [Knowledgebase & Documentation Module](#8-knowledgebase--documentation-module)
9. [Hotspot Management Module](#9-hotspot-management-module)
10. [Zone Management Module](#10-zone-management-module)
11. [Settings & Admin Module](#11-settings--admin-module)
12. [Directory Structure Changes](#12-directory-structure-changes)
13. [UI/CSS Cleanup](#13-uicss-cleanup)
14. [Implementation Order](#14-implementation-order)

---

## 1. Foundation Changes

### 1.1 Add Strict Typing to All PHP Files
**Priority**: CRITICAL  
**Effort**: ~4-6 hours

**Action**: Add `declare(strict_types=1);` as the first line in all PHP files.

**Files Affected**: ~80+ PHP files
- All files in `app/` directory
- Exclude vendor files

**Implementation**:
- Use automated script/bulk find-replace
- Verify no breaking changes
- Run tests after each batch

---

### 1.2 Create Base Repository Class
**Priority**: CRITICAL  
**Effort**: ~2 hours

**Action**: Create abstract base repository class.

**New File**: `app/Repositories/BaseRepository.php`

```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    // Common methods: find, findAll, create, update, delete, paginate, etc.
}
```

---

### 1.3 Create Base Form Request Class
**Priority**: MEDIUM  
**Effort**: ~1 hour

**Action**: Create base form request if needed for common validation.

**New File**: `app/Http/Requests/BaseFormRequest.php` (optional)

---

### 1.4 Create Actions Directory Structure
**Priority**: MEDIUM  
**Effort**: ~1 hour

**Action**: Create top-level Actions directory for general application actions.

**New Structure**:
```
app/
├── Actions/
│   ├── Auth/
│   │   └── LogoutAction.php          # Move from Livewire/Actions
│   ├── User/
│   │   ├── CreateUserAction.php
│   │   ├── UpdateUserAction.php
│   │   └── DeleteUserAction.php
│   ├── Voucher/
│   │   └── GenerateVouchersAction.php
│   └── ...
```

**Move**:
- `app/Livewire/Actions/Logout.php` → `app/Actions/Auth/LogoutAction.php`

---

## 2. User Management Module

### 2.1 Create User Repository
**Priority**: CRITICAL  
**Effort**: ~3 hours

**New File**: `app/Repositories/UserRepository.php`

**Methods**:
- `find(int $id): ?User`
- `findByEmail(string $email): ?User`
- `search(string $term, array $filters = []): LengthAwarePaginator`
- `create(array $data): User`
- `update(User $user, array $data): User`
- `delete(User $user): bool`
- `getByRole(string $role): Collection`

**Refactor From**:
- `app/Livewire/User/Index.php` - `filteredQuery()` method
- `app/Livewire/User/Create.php` - User creation logic
- `app/Livewire/User/Edit.php` - User update logic

---

### 2.2 Create User Service
**Priority**: CRITICAL  
**Effort**: ~4 hours

**New File**: `app/Services/UserService.php`

**Methods**:
- `createUser(array $data, ?User $creator = null): User` - Handles role assignment logic
- `updateUser(User $user, array $data): User`
- `deleteUser(User $user): bool`
- `assignRole(User $user, string $role): void`
- `updatePassword(User $user, string $password): void`

**Extract From**:
- `app/Livewire/User/Create.php` - Role assignment logic (lines 50-65)
- `app/Livewire/User/Edit.php` - Update logic (lines 47-66)

---

### 2.3 Create User Actions
**Priority**: MEDIUM  
**Effort**: ~3 hours

**New Files**:
- `app/Actions/User/CreateUserAction.php`
- `app/Actions/User/UpdateUserAction.php`
- `app/Actions/User/DeleteUserAction.php`

**Purpose**: Single-use actions that orchestrate Service + Repository calls.

---

### 2.4 Create User Form Requests
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New Files**:
- `app/Http/Requests/User/CreateUserRequest.php`
- `app/Http/Requests/User/UpdateUserRequest.php`

**Extract Validation From**:
- `app/Livewire/User/Create.php` - `#[Validate]` attributes
- `app/Livewire/User/Edit.php` - `#[Validate]` attributes

**Note**: Livewire components can still use `#[Validate]` for simple cases, but complex validation should use Form Requests.

---

### 2.5 Refactor Livewire Components
**Priority**: CRITICAL  
**Effort**: ~4 hours

**Files to Refactor**:
- `app/Livewire/User/Index.php`
  - Replace `filteredQuery()` with `UserRepository::search()`
  - Keep pagination and sorting logic in component
  
- `app/Livewire/User/Create.php`
  - Call `UserService::createUser()` instead of inline logic
  - Use `CreateUserAction` if needed
  
- `app/Livewire/User/Edit.php`
  - Call `UserService::updateUser()` instead of inline logic
  - Use `UpdateUserAction` if needed

---

## 3. Router Management Module

### 3.1 Create Router Repository
**Priority**: CRITICAL  
**Effort**: ~3 hours

**New File**: `app/Repositories/RouterRepository.php`

**Methods**:
- `find(int $id): ?Router`
- `findByUser(User $user, array $filters = []): LengthAwarePaginator`
- `search(string $term, ?User $user = null): LengthAwarePaginator`
- `create(array $data): Router`
- `update(Router $router, array $data): Router`
- `delete(Router $router): bool`
- `getByZone(int $zoneId): Collection`

**Refactor From**:
- `app/Livewire/Router/Index.php` - `paginatedRouters()` method
- `app/Livewire/Router/Create.php` - Router creation
- `app/Livewire/Router/Edit.php` - Router update

---

### 3.2 Create Router Service
**Priority**: CRITICAL  
**Effort**: ~4 hours

**New File**: `app/Services/RouterService.php`

**Methods**:
- `createRouter(array $data, User $user): Router` - Handles API key generation, zone assignment
- `updateRouter(Router $router, array $data): Router`
- `deleteRouter(Router $router): bool`
- `importRouters(array $routers, User $user): array` - Bulk import logic
- `pingRouter(Router $router): bool` - Move from RouterManager if needed

**Extract From**:
- `app/Livewire/Router/Create.php` - Router creation logic
- `app/Livewire/Router/Edit.php` - Router update logic
- `app/Livewire/Router/Import.php` - Import logic

---

### 3.3 Create Router Form Requests
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New Files**:
- `app/Http/Requests/Router/CreateRouterRequest.php`
- `app/Http/Requests/Router/UpdateRouterRequest.php`
- `app/Http/Requests/Router/ImportRoutersRequest.php`

---

### 3.4 Refactor Livewire Components
**Priority**: CRITICAL  
**Effort**: ~4 hours

**Files to Refactor**:
- `app/Livewire/Router/Index.php`
  - Use `RouterRepository::findByUser()` instead of `paginatedRouters()`
  - Keep ping logic (delegates to MikroTik\Actions\RouterManager)
  
- `app/Livewire/Router/Create.php`
  - Call `RouterService::createRouter()`
  
- `app/Livewire/Router/Edit.php`
  - Call `RouterService::updateRouter()`
  
- `app/Livewire/Router/Import.php`
  - Call `RouterService::importRouters()`

**Note**: MikroTik-specific actions stay in `app/MikroTik/Actions/` - no changes needed.

---

## 4. Voucher System Module

### 4.1 Create Voucher Repository
**Priority**: CRITICAL  
**Effort**: ~3 hours

**New File**: `app/Repositories/VoucherRepository.php`

**Methods**:
- `find(int $id): ?Voucher`
- `search(array $filters): LengthAwarePaginator` - Handles q, status, routerFilter
- `create(array $data): Voucher`
- `bulkCreate(array $vouchers): int` - For bulk generation
- `update(Voucher $voucher, array $data): Voucher`
- `delete(Voucher $voucher): bool`
- `getByBatch(string $batch): Collection`
- `getByRouter(int $routerId, array $filters = []): LengthAwarePaginator`

**Refactor From**:
- `app/Livewire/Voucher/Index.php` - `vouchers()` method
- `app/Livewire/Voucher/Create.php` - Voucher creation
- `app/Livewire/Voucher/Generate.php` - Bulk creation logic

---

### 4.2 Create Voucher Service
**Priority**: CRITICAL  
**Effort**: ~5 hours

**New File**: `app/Services/VoucherService.php`

**Methods**:
- `createVoucher(array $data, User $user): Voucher`
- `generateVouchers(int $quantity, array $config, User $user): array` - Extract from Generate.php
- `generateCodes(int $quantity, int $length, string $prefix, ?int $serialStart, string $charType): array` - Extract from Generate.php
- `buildVoucherRows(array $codes, array $config, User $user): array` - Extract from Generate.php
- `activateVoucher(Voucher $voucher): Voucher`
- `expireVoucher(Voucher $voucher): Voucher`

**Extract From**:
- `app/Livewire/Voucher/Generate.php` - `generateCodes()`, `buildRows()` methods
- `app/Livewire/Voucher/Create.php` - Voucher creation logic
- `app/Livewire/Voucher/BulkManager.php` - Bulk operations

---

### 4.3 Create Voucher Actions
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New Files**:
- `app/Actions/Voucher/GenerateVouchersAction.php` - Orchestrates VoucherService::generateVouchers()

---

### 4.4 Create Voucher Form Requests
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New Files**:
- `app/Http/Requests/Voucher/CreateVoucherRequest.php`
- `app/Http/Requests/Voucher/GenerateVouchersRequest.php`

---

### 4.5 Refactor Livewire Components
**Priority**: CRITICAL  
**Effort**: ~5 hours

**Files to Refactor**:
- `app/Livewire/Voucher/Index.php`
  - Use `VoucherRepository::search()` instead of `vouchers()`
  
- `app/Livewire/Voucher/Create.php`
  - Call `VoucherService::createVoucher()`
  
- `app/Livewire/Voucher/Generate.php`
  - Call `VoucherService::generateVouchers()` or `GenerateVouchersAction`
  - Remove `generateCodes()` and `buildRows()` methods
  
- `app/Livewire/Voucher/BulkManager.php`
  - Use `VoucherService` methods

---

## 5. Billing & Payment Module

### 5.1 Create Invoice Repository
**Priority**: CRITICAL  
**Effort**: ~2 hours

**New File**: `app/Repositories/InvoiceRepository.php`

**Methods**:
- `find(int $id): ?Invoice`
- `findByUser(User $user, array $filters = []): LengthAwarePaginator`
- `findByRouter(Router $router, array $filters = []): LengthAwarePaginator`
- `create(array $data): Invoice`
- `findByTransactionId(string $transactionId): ?Invoice`

**Refactor From**:
- `app/Livewire/Billing/Invoices.php` - Invoice listing queries

---

### 5.2 Enhance BillingService
**Priority**: MEDIUM  
**Effort**: ~2 hours

**File**: `app/Services/BillingService.php` (already exists)

**Review & Enhance**:
- Already has `credit()` and `debit()` methods - ✅ Good
- Add helper methods if needed:
  - `getUserBalance(User $user): float`
  - `getUserInvoices(User $user, array $filters = []): LengthAwarePaginator`

---

### 5.3 Create Billing Form Requests
**Priority**: MEDIUM  
**Effort**: ~1 hour

**New Files**:
- `app/Http/Requests/Billing/AddBalanceRequest.php`
- `app/Http/Requests/Billing/ManualAdjustmentRequest.php`

---

### 5.4 Refactor Livewire Components
**Priority**: MEDIUM  
**Effort**: ~3 hours

**Files to Refactor**:
- `app/Livewire/Billing/Invoices.php`
  - Use `InvoiceRepository::findByUser()` instead of direct queries
  
- `app/Livewire/Billing/AddBalance.php`
  - Already uses `BillingService` - ✅ Good
  - Add Form Request validation if needed
  
- `app/Livewire/Billing/ManualAdjustment.php`
  - Already uses `BillingService` - ✅ Good
  - Add Form Request validation if needed

**Note**: Payment gateway logic is already well-structured - no changes needed.

---

## 6. Package & Subscription Module

### 6.1 Create Package Repository
**Priority**: CRITICAL  
**Effort**: ~2 hours

**New File**: `app/Repositories/PackageRepository.php`

**Methods**:
- `find(int $id): ?Package`
- `findAll(array $filters = []): Collection`
- `findActive(): Collection`
- `create(array $data): Package`
- `update(Package $package, array $data): Package`
- `delete(Package $package): bool`

**Refactor From**:
- `app/Livewire/Package/Index.php` - Package listing

---

### 6.2 Create Package Service
**Priority**: CRITICAL  
**Effort**: ~3 hours

**New File**: `app/Services/PackageService.php`

**Methods**:
- `createPackage(array $data): Package`
- `updatePackage(Package $package, array $data): Package`
- `deletePackage(Package $package): bool`
- `calculatePrice(Package $package, string $cycle): float` - Monthly/yearly calculation

**Extract From**:
- `app/Livewire/Package/Create.php` - Package creation logic
- `app/Livewire/Package/Edit.php` - Package update logic

---

### 6.3 Create Package Form Requests
**Priority**: MEDIUM  
**Effort**: ~1 hour

**New Files**:
- `app/Http/Requests/Package/CreatePackageRequest.php`
- `app/Http/Requests/Package/UpdatePackageRequest.php`

---

### 6.4 Refactor Livewire Components
**Priority**: CRITICAL  
**Effort**: ~3 hours

**Files to Refactor**:
- `app/Livewire/Package/Index.php`
  - Use `PackageRepository::findAll()` or `findActive()`
  
- `app/Livewire/Package/Create.php`
  - Call `PackageService::createPackage()`
  
- `app/Livewire/Package/Edit.php`
  - Call `PackageService::updatePackage()`

**Note**: `RouterSubscriptionService` already exists and is well-structured - no changes needed.

---

## 7. Ticket System Module

### 7.1 Create Ticket Repository
**Priority**: CRITICAL  
**Effort**: ~3 hours

**New File**: `app/Repositories/TicketRepository.php`

**Methods**:
- `find(int $id): ?Ticket`
- `findByUser(User $user, array $filters = []): LengthAwarePaginator`
- `findByStatus(string $status, ?User $user = null): LengthAwarePaginator`
- `create(array $data): Ticket`
- `update(Ticket $ticket, array $data): Ticket`
- `delete(Ticket $ticket): bool`

**Refactor From**:
- `app/Livewire/Tickets/Index.php` - `filteredQuery()` method

---

### 7.2 Create Ticket Service
**Priority**: CRITICAL  
**Effort**: ~4 hours

**New File**: `app/Services/TicketService.php`

**Methods**:
- `createTicket(array $data, User $user): Ticket`
- `updateTicket(Ticket $ticket, array $data): Ticket` - Handles status changes, timestamps
- `updateStatus(Ticket $ticket, string $status): Ticket` - Handles solved_at, closed_at
- `assignTicket(Ticket $ticket, ?User $user): Ticket`
- `addMessage(Ticket $ticket, string $message, User $user): TicketMessage`
- `deleteTicket(Ticket $ticket): bool`

**Extract From**:
- `app/Livewire/Tickets/Index.php` - Ticket creation logic
- `app/Livewire/Tickets/Show.php` - Status update logic, message creation

---

### 7.3 Create Ticket Form Requests
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New Files**:
- `app/Http/Requests/Ticket/CreateTicketRequest.php`
- `app/Http/Requests/Ticket/UpdateTicketRequest.php`
- `app/Http/Requests/Ticket/SendMessageRequest.php`

---

### 7.4 Refactor Livewire Components
**Priority**: CRITICAL  
**Effort**: ~4 hours

**Files to Refactor**:
- `app/Livewire/Tickets/Index.php`
  - Use `TicketRepository::findByUser()` instead of `filteredQuery()`
  - Call `TicketService::createTicket()` for creation
  
- `app/Livewire/Tickets/Show.php`
  - Call `TicketService::updateStatus()` instead of inline logic
  - Call `TicketService::addMessage()` for message creation
  - Call `TicketService::updateTicket()` for general updates

---

## 8. Knowledgebase & Documentation Module

### 8.1 Create Knowledgebase Repository
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New File**: `app/Repositories/KnowledgebaseRepository.php`

**Methods**:
- `find(int $id): ?KnowledgebaseArticle`
- `search(string $term, ?string $category = null): LengthAwarePaginator`
- `getCategories(): array`
- `findActive(): Collection`

**Refactor From**:
- `app/Livewire/Knowledgebase/Index.php` - `articles()`, `categories()` methods

---

### 8.2 Create Documentation Repository
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New File**: `app/Repositories/DocumentationRepository.php`

**Methods**:
- `find(int $id): ?DocumentationArticle`
- `search(string $term): LengthAwarePaginator`
- `findAll(): Collection`

**Refactor From**:
- `app/Livewire/Docs/Index.php` - Documentation listing

---

### 8.3 Refactor Livewire Components
**Priority**: MEDIUM  
**Effort**: ~2 hours

**Files to Refactor**:
- `app/Livewire/Knowledgebase/Index.php`
  - Use `KnowledgebaseRepository::search()` instead of `articles()`
  - Use `KnowledgebaseRepository::getCategories()` instead of `categories()`
  
- `app/Livewire/Docs/Index.php`
  - Use `DocumentationRepository::search()` or `findAll()`

**Note**: These are read-only modules, so no Service layer needed unless we add admin management later.

---

## 9. Hotspot Management Module

### 9.1 Create HotspotUser Repository
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New File**: `app/Repositories/HotspotUserRepository.php`

**Methods**:
- `findByRouter(int $routerId, array $filters = []): Collection`
- `create(array $data): HotspotUser` (if model exists)
- `syncWithRouter(Router $router): array` - Sync logic

**Note**: Hotspot users may be stored in MikroTik, not database. Repository may need to interface with MikroTik API.

---

### 9.2 Refactor Livewire Components
**Priority**: MEDIUM  
**Effort**: ~3 hours

**Files to Refactor**:
- `app/Livewire/HotspotUsers/Create.php`
  - Already uses `MikroTik\Actions\HotspotUserManager` - ✅ Good
  - May need minor cleanup
  
- `app/Livewire/HotspotUsers/ActiveSessions.php`
  - Uses MikroTik actions - ✅ Good
  
- `app/Livewire/HotspotUsers/Logs.php`
  - Uses MikroTik actions - ✅ Good

**Note**: Hotspot management is tightly coupled with MikroTik, so most logic stays in `app/MikroTik/Actions/` - minimal changes needed.

---

## 10. Zone Management Module

### 10.1 Create Zone Repository
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New File**: `app/Repositories/ZoneRepository.php`

**Methods**:
- `find(int $id): ?Zone`
- `findByUser(User $user, array $filters = []): Collection`
- `search(string $term, User $user): Collection`
- `create(array $data): Zone`
- `update(Zone $zone, array $data): Zone`
- `delete(Zone $zone): bool`

**Refactor From**:
- `app/Livewire/Zone/Index.php` - Zone queries

---

### 10.2 Create Zone Service
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New File**: `app/Services/ZoneService.php`

**Methods**:
- `createZone(array $data, User $user): Zone`
- `updateZone(Zone $zone, array $data): Zone`
- `deleteZone(Zone $zone): bool`

**Extract From**:
- `app/Livewire/Zone/Index.php` - Zone creation/update logic

---

### 10.3 Refactor Livewire Components
**Priority**: MEDIUM  
**Effort**: ~2 hours

**Files to Refactor**:
- `app/Livewire/Zone/Index.php`
  - Use `ZoneRepository::findByUser()` instead of `currentUserZones()`
  - Call `ZoneService::createZone()` and `updateZone()`

---

## 11. Settings & Admin Module

### 11.1 Create Profile Repository
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New File**: `app/Repositories/ProfileRepository.php`

**Methods**:
- `find(int $id): ?UserProfile`
- `findByUser(User $user, array $filters = []): LengthAwarePaginator`
- `search(string $term, User $user): LengthAwarePaginator`
- `create(array $data): UserProfile`
- `update(UserProfile $profile, array $data): UserProfile`
- `delete(UserProfile $profile): bool`

**Refactor From**:
- `app/Livewire/Profile/Index.php` - Profile queries

---

### 11.2 Create Profile Service
**Priority**: MEDIUM  
**Effort**: ~3 hours

**New File**: `app/Services/ProfileService.php`

**Methods**:
- `createProfile(array $data, User $user): UserProfile`
- `updateProfile(UserProfile $profile, array $data): UserProfile`
- `deleteProfile(UserProfile $profile): bool`
- `validateProfileName(string $name, ?UserProfile $exclude = null): bool`

**Extract From**:
- `app/Livewire/Profile/Create.php` - Profile creation logic
- `app/Livewire/Profile/Edit.php` - Profile update logic

---

### 11.3 Create Profile Form Requests
**Priority**: MEDIUM  
**Effort**: ~2 hours

**New Files**:
- `app/Http/Requests/Profile/CreateProfileRequest.php`
- `app/Http/Requests/Profile/UpdateProfileRequest.php`

---

### 11.4 Refactor Livewire Components
**Priority**: MEDIUM  
**Effort**: ~3 hours

**Files to Refactor**:
- `app/Livewire/Profile/Index.php`
  - Use `ProfileRepository::findByUser()` instead of `getProfilesProperty()`
  
- `app/Livewire/Profile/Create.php`
  - Call `ProfileService::createProfile()`
  
- `app/Livewire/Profile/Edit.php`
  - Call `ProfileService::updateProfile()`

**Admin Components** (minimal changes):
- `app/Livewire/Admin/*` - Review for any business logic extraction
- Most are configuration/settings - may not need Services

---

## 12. Directory Structure Changes

### 12.1 Rename Directories (Lowercase with Dashes)
**Priority**: MEDIUM  
**Effort**: ~2 hours

**Decision Required**: Project rule says "lowercase with dashes" but Laravel convention is PascalCase. 

**Options**:
1. **Keep PascalCase** (Laravel standard) - Update project rule
2. **Use lowercase-dashes** - Rename directories

**If choosing lowercase-dashes, rename**:
- `app/Livewire/ActivityLog/` → `app/Livewire/activity-log/`
- `app/Livewire/HotspotUsers/` → `app/Livewire/hotspot-users/`

**Also update**:
- Namespace declarations
- View paths
- Route references

**Recommendation**: Keep PascalCase (Laravel standard) and update project rule to clarify.

---

### 12.2 New Directory Structure
**Priority**: CRITICAL  
**Effort**: N/A (created during implementation)

**Final Structure**:
```
app/
├── Actions/                    # NEW - General application actions
│   ├── Auth/
│   ├── User/
│   ├── Voucher/
│   └── ...
├── Repositories/               # NEW - Data access layer
│   ├── BaseRepository.php
│   ├── UserRepository.php
│   ├── RouterRepository.php
│   ├── VoucherRepository.php
│   ├── TicketRepository.php
│   ├── PackageRepository.php
│   ├── InvoiceRepository.php
│   ├── ZoneRepository.php
│   ├── ProfileRepository.php
│   ├── KnowledgebaseRepository.php
│   └── DocumentationRepository.php
├── Services/                    # EXISTING - Enhanced
│   ├── ActivityLogger.php      # Keep
│   ├── BillingService.php       # Keep & enhance
│   ├── UserService.php          # NEW
│   ├── RouterService.php        # NEW
│   ├── VoucherService.php       # NEW
│   ├── TicketService.php        # NEW
│   ├── PackageService.php       # NEW
│   ├── ZoneService.php          # NEW
│   ├── ProfileService.php       # NEW
│   ├── Subscriptions/
│   │   └── RouterSubscriptionService.php  # Keep
│   └── VoucherLogger.php        # Keep
├── Http/
│   └── Requests/               # NEW - Form requests
│       ├── User/
│       ├── Router/
│       ├── Voucher/
│       ├── Ticket/
│       ├── Package/
│       ├── Billing/
│       └── Profile/
├── Livewire/                   # EXISTING - Refactored
│   └── [All existing components, but thinner]
├── MikroTik/                   # EXISTING - No changes
│   └── Actions/                # Keep as-is
└── Gateway/                    # EXISTING - No changes
    └── [Keep as-is]
```

---

## 13. UI/CSS Cleanup

### 13.1 Reduce Custom CSS
**Priority**: LOW  
**Effort**: ~3-4 hours

**File**: `resources/css/app.css`

**Actions**:
1. Review custom classes (lines 110-163)
2. Replace where possible with Tailwind utilities:
   - `.mary-card` - Could use inline classes, but may be needed for consistency
   - Table styling - Could use Tailwind table classes
3. Keep necessary overrides:
   - Theme configuration (lines 43-71) - ✅ Keep
   - DaisyUI overrides (lines 93-98) - ✅ Keep
   - Border radius overrides (lines 127-129) - ✅ Keep (design system requirement)

**Recommendation**: Most custom CSS is necessary for design system. Minimal changes needed.

---

### 13.2 Replace Arbitrary Tailwind Values
**Priority**: LOW  
**Effort**: ~1 hour

**Files**:
- `resources/views/livewire/router/show.blade.php`
- `resources/views/livewire/router/index.blade.php`

**Actions**:
- Replace `text-[11px]` with standard Tailwind size or add to theme config
- Replace `text-[10px]` with standard Tailwind size or add to theme config

---

## 14. Implementation Order

### Phase 1: Foundation (Week 1)
1. ✅ Add strict typing to all PHP files
2. ✅ Create BaseRepository class
3. ✅ Create Actions directory structure
4. ✅ Move Logout action

**Estimated Time**: 8-10 hours

---

### Phase 2: Core Modules - Part 1 (Week 2)
1. ✅ User Management Module (Repository, Service, Actions, Form Requests, Refactor)
2. ✅ Router Management Module (Repository, Service, Form Requests, Refactor)

**Estimated Time**: 20-25 hours

---

### Phase 3: Core Modules - Part 2 (Week 3)
1. ✅ Voucher System Module (Repository, Service, Actions, Form Requests, Refactor)
2. ✅ Billing & Payment Module (Repository, Enhance Service, Form Requests, Refactor)

**Estimated Time**: 18-22 hours

---

### Phase 4: Supporting Modules (Week 4)
1. ✅ Package & Subscription Module
2. ✅ Ticket System Module
3. ✅ Zone Management Module

**Estimated Time**: 18-22 hours

---

### Phase 5: Content & Settings (Week 5)
1. ✅ Knowledgebase & Documentation Module
2. ✅ Profile Management Module
3. ✅ Settings & Admin cleanup

**Estimated Time**: 12-15 hours

---

### Phase 6: Cleanup & Polish (Week 6)
1. ✅ Directory naming decision & implementation
2. ✅ UI/CSS cleanup
3. ✅ Error handling standardization
4. ✅ Final testing & verification

**Estimated Time**: 10-12 hours

---

## Total Estimated Effort

**Foundation**: 8-10 hours  
**Core Modules**: 38-47 hours  
**Supporting Modules**: 18-22 hours  
**Content & Settings**: 12-15 hours  
**Cleanup**: 10-12 hours  

**TOTAL**: **86-106 hours** (~11-13 working days)

---

## Risk Assessment

### Low Risk Changes:
- Adding strict typing (automated)
- Creating new Repository/Service classes
- Creating Form Request classes

### Medium Risk Changes:
- Refactoring Livewire components (need thorough testing)
- Extracting business logic (need to verify behavior)

### High Risk Changes:
- Directory renaming (affects namespaces, views, routes)
- Major refactoring of complex components (Voucher/Generate)

---

## Testing Strategy

After each module refactoring:
1. ✅ Run existing tests
2. ✅ Manual testing of affected features
3. ✅ Verify no regression
4. ✅ Check error handling

---

## Rollback Plan

- Use Git branches for each phase
- Tag stable versions after each phase
- Keep old code commented until verified

---

## Approval Checklist

Before proceeding to Phase 3 (Implementation), please confirm:

- [ ] Foundation changes approved
- [ ] Module reorganization order approved
- [ ] Directory naming decision made (PascalCase vs lowercase-dashes)
- [ ] Repository pattern approach approved
- [ ] Service layer approach approved
- [ ] Form Request usage strategy approved
- [ ] Implementation timeline acceptable
- [ ] Testing strategy approved

---

**End of Reorganization Plan**

**Next Step**: Await approval, then proceed to Phase 3 - Step-by-Step Reorganization
