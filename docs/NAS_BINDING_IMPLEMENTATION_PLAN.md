# NAS/MikroTik Binding Implementation Plan

## Overview

Implement flexible voucher-to-MikroTik binding system with three binding strategies:
1. **Pre-bind**: Bind voucher to specific MikroTik during generation
2. **Auto-bind on first use**: Bind voucher to first MikroTik it authenticates from
3. **NAS Device Groups**: Child MikroTik devices inherit parent's NAS identifier

## Current Architecture Analysis

### Existing Components
- **Router Management**: `app/Livewire/Router/` - Router CRUD operations
- **Voucher System**: `app/Livewire/Voucher/` - Voucher generation and management
- **RADIUS Integration**: `radtik-radius/` - FreeRADIUS with SQLite backend
- **MikroTik API**: `app/MikroTik/RouterClient.php` - RouterOS API communication
- **Database Tables**:
  - `routers` - MikroTik router records
  - `vouchers` - Generated voucher codes
  - `radcheck` (RADIUS) - Authentication credentials
  - `radreply` (RADIUS) - Response attributes

### Current Flow
```
Voucher Generation → radcheck (username/password) 
                  → radreply (session limits, bandwidth)
                  → Any MikroTik can authenticate
```

## Feature Requirements

### 1. NAS Identifier Management

#### Database Schema Changes

**A. Update `routers` table**
```php
- Add: nas_identifier (string, unique, indexed) - Unique identifier for RADIUS
- Add: parent_router_id (foreignId, nullable) - For NAS device grouping
- Add: is_nas_device (boolean, default false) - Mark as child NAS device
- Add: nas_group_enabled (boolean, default false) - Enable NAS grouping
- Add: radius_configured (boolean, default false) - Auto-config status
```

**B. Create `router_nas_config` table**
```php
- id
- router_id (foreignId)
- radius_server_ip
- radius_port (default 1812)
- shared_secret
- accounting_enabled (boolean)
- accounting_port (default 1813)
- configured_at (timestamp)
- last_sync_at (timestamp)
```

**C. Update `vouchers` table**
```php
- Add: binding_strategy (enum: 'none', 'pre_bind', 'auto_bind')
- Add: bound_router_id (foreignId, nullable) - Pre-bound or auto-bound router
- Add: bound_at (timestamp, nullable) - When auto-binding occurred
- Add: allow_nas_group (boolean, default false) - Allow parent's NAS children
```

**D. Create `voucher_authentication_log` table**
```php
- id
- voucher_id
- router_id
- nas_identifier
- mac_address
- nas_ip_address
- authenticated_at
- binding_applied (boolean) - Whether this auth created a binding
```

### 2. NAS Identifier Auto-Generation

**Algorithm**: 
```
Format: radtik-{router_id}-{random_8chars}
Example: radtik-42-a7f9k2m1
```

**Generation Points**:
- When router is created
- When router is edited (if empty)
- API endpoint to regenerate

**Implementation Location**: `app/MikroTik/Actions/NasIdentifierGenerator.php`

### 3. MikroTik Auto-Configuration

#### Features to Configure via API
```
/radius
- Add RADIUS server entry
- Set IP address (from settings or server IP)
- Set shared secret
- Enable RADIUS service
- Set authentication port (1812)
- Set accounting port (1813)

/ip hotspot profile
- Set RADIUS authentication
- Set RADIUS accounting
- Enable RADIUS MAC authentication

/system identity
- Set NAS identifier as system identity
```

**Implementation Location**: `app/MikroTik/Actions/RadiusConfigurator.php`

### 4. Binding Strategy Implementation

#### Strategy 1: Pre-Bind (during generation)

**UI Changes** (Voucher Generation):
```
[ ] Bind to specific MikroTik
    ↓ (if checked)
    [Dropdown: Select MikroTik]
    ↳ Show only accessible MikroTiks
    [ ] Allow use on NAS device group (if parent)
```

**Backend Logic**:
- Set `binding_strategy = 'pre_bind'`
- Set `bound_router_id = selected_router`
- Add NAS identifier check to radcheck table
- If NAS group allowed: Use parent's NAS identifier

**RADIUS Implementation**:
```sql
-- radcheck entry format
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('voucher123', 'Calling-Station-Id', '==', 'nas-identifier-value');
```

#### Strategy 2: Auto-Bind on First Use

**UI Changes** (Voucher Generation):
```
[ ] Auto-bind to first MikroTik used
    [ ] Allow use on NAS device group (if applicable)
```

**Backend Logic**:
- Set `binding_strategy = 'auto_bind'`
- Leave `bound_router_id = null` initially
- Monitor via Python sync script

**Python Sync Script Enhancement** (`check-activations.py`):
```python
if voucher.binding_strategy == 'auto_bind' and not voucher.bound_router_id:
    # First authentication detected
    nas_identifier = radpostauth.nas_identifier
    router = find_router_by_nas_identifier(nas_identifier)
    
    # Bind voucher
    voucher.bound_router_id = router.id
    voucher.bound_at = now()
    
    # Add NAS check to RADIUS
    add_nas_check_to_radcheck(username, nas_identifier)
    
    # Log binding
    log_authentication(voucher, router, nas_identifier, binding_applied=True)
```

#### Strategy 3: NAS Device Groups

**UI Changes** (Router Management):
```
Router Details:
- NAS Identifier: radtik-42-a7f9k2m1 [Regenerate]
- [ ] Enable NAS Device Grouping
      ↓ (if enabled)
      Child NAS Devices:
      + Add Child Device
      
      List of Children:
      - Child Router 1 (inherits: radtik-42-a7f9k2m1)
      - Child Router 2 (inherits: radtik-42-a7f9k2m1)
```

**Backend Logic**:
- Parent router: Set `nas_group_enabled = true`
- Child routers: Set `parent_router_id`, `is_nas_device = true`
- Child routers use parent's NAS identifier in their RADIUS config
- Vouchers bound to parent work on all children

**Database Query Example**:
```php
// Get effective NAS identifier (parent if child)
function getEffectiveNasIdentifier(Router $router) {
    if ($router->is_nas_device && $router->parent) {
        return $router->parent->nas_identifier;
    }
    return $router->nas_identifier;
}
```

### 5. Admin Panel Features

#### A. Router Management Enhancements

**Location**: `app/Livewire/Router/Edit.php`

**Features**:
- View/Edit NAS identifier
- Regenerate NAS identifier button
- Enable/disable NAS grouping
- Manage child NAS devices
- RADIUS configuration status
- Test RADIUS connectivity
- Re-configure RADIUS settings

#### B. Voucher Generation Enhancements

**Location**: `app/Livewire/Voucher/Generate.php`

**Features**:
- Binding strategy selector (radio buttons)
- MikroTik selection dropdown (conditional)
- NAS group option (conditional)
- Preview binding details before generation

#### C. Voucher Management Enhancements

**Location**: `app/Livewire/Voucher/Index.php`, `Show.php`

**Features**:
- Display binding status (None, Pre-bound, Auto-bound)
- Show bound MikroTik name
- Show binding date
- "Rebind" action (if admin)
- "Unbind" action (if admin)
- Authentication log viewer

#### D. New: Voucher Binding Management

**Location**: `app/Livewire/Voucher/ManageBinding.php` (new)

**Features**:
- Change binding strategy
- Bind/unbind to MikroTik
- View authentication history
- See which MikroTiks voucher was used on
- Force rebind option

### 6. RADIUS Integration Updates

#### A. Enhanced radcheck Table Population

**When voucher is bound** (pre-bind or auto-bind):
```sql
-- Add username
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('voucher123', 'Cleartext-Password', ':=', 'password123');

-- Add NAS identifier check
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('voucher123', 'NAS-Identifier', '==', 'radtik-42-a7f9k2m1');
```

**Without binding**:
```sql
-- Only add username/password (works on any NAS)
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('voucher123', 'Cleartext-Password', ':=', 'password123');
```

#### B. Python Sync Script Updates

**File**: `radtik-radius/scripts/sync-vouchers.py`

**Enhancements**:
```python
def sync_voucher_to_radius(cursor, voucher):
    username = voucher['username']
    password = voucher['password']
    
    # Clear existing entries
    cursor.execute("DELETE FROM radcheck WHERE username = ?", (username,))
    
    # Add password
    cursor.execute("""
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES (?, 'Cleartext-Password', ':=', ?)
    """, (username, password))
    
    # Add NAS binding if applicable
    if voucher.get('bound_router_nas_identifier'):
        cursor.execute("""
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES (?, 'NAS-Identifier', '==', ?)
        """, (username, voucher['bound_router_nas_identifier']))
    
    # ... rest of profile setup
```

**File**: `radtik-radius/scripts/check-activations.py`

**Enhancements**:
```python
def check_new_activations():
    # ... existing code ...
    
    for username, mac, nas_identifier, authdate in rows:
        # Call Laravel API
        response = requests.post(
            f"{LARAVEL_API}/voucher/activate",
            headers={"X-RADIUS-SECRET": API_SECRET},
            json={
                "username": username,
                "mac_address": mac,
                "nas_identifier": nas_identifier,  # NEW
                "activated_at": authdate
            }
        )
        
        result = response.json()
        
        # Handle auto-binding
        if result.get('should_bind_nas'):
            bind_nas_to_user(cursor, username, nas_identifier)
        
        # Handle MAC binding (existing)
        if result.get('should_bind_mac'):
            bind_mac_to_user(cursor, username, mac)
```

### 7. API Endpoints

#### A. Laravel API Routes

**File**: `routes/web.php` or `routes/api.php`

```php
// RADIUS Server Integration
Route::middleware(['auth:api'])->prefix('api/radius')->group(function () {
    // Existing
    Route::post('/sync/vouchers', [RadiusController::class, 'syncVouchers']);
    Route::post('/voucher/activate', [RadiusController::class, 'activateVoucher']);
    
    // NEW: NAS Binding
    Route::get('/router/nas-identifier/{nas_identifier}', [RadiusController::class, 'getRouterByNas']);
    Route::post('/voucher/check-binding', [RadiusController::class, 'checkVoucherBinding']);
});

// Admin API
Route::middleware(['auth', 'permission:manage_routers'])->prefix('admin/routers')->group(function () {
    Route::post('/{router}/configure-radius', [RouterController::class, 'configureRadius']);
    Route::post('/{router}/regenerate-nas', [RouterController::class, 'regenerateNasIdentifier']);
    Route::post('/{router}/test-radius', [RouterController::class, 'testRadiusConnection']);
});
```

#### B. Controller Methods

**New Controller**: `app/Http/Controllers/Api/RadiusController.php`

```php
class RadiusController extends Controller
{
    public function activateVoucher(Request $request)
    {
        // Existing activation logic
        // + Check binding strategy
        // + Apply auto-bind if needed
        // + Return binding instructions
    }
    
    public function getRouterByNas(string $nasIdentifier)
    {
        // Find router by NAS identifier
        // Return router details for binding
    }
    
    public function checkVoucherBinding(Request $request)
    {
        // Check if voucher can authenticate from this NAS
        // Return allowed/denied + reason
    }
}
```

### 8. Settings Integration

#### Add to General Settings

**Location**: `app/Models/Setting.php` or settings table

**New Settings**:
```php
'radius_server_ip' => '192.168.1.100',  // Default RADIUS server IP
'radius_shared_secret' => 'auto_generated',  // Default shared secret
'radius_auth_port' => 1812,
'radius_acct_port' => 1813,
'nas_identifier_prefix' => 'radtik',  // Prefix for auto-generated identifiers
'auto_configure_mikrotik' => true,  // Auto-configure on router add
'nas_binding_enabled' => true,  // Enable NAS binding feature
```

## Implementation Task List

### Phase 1: Database & Models (Priority 1)

- [ ] **Task 1.1**: Create migration for `routers` table updates
  - Add NAS identifier columns
  - Add parent/child relationship columns
  - Add RADIUS configuration status
  
- [ ] **Task 1.2**: Create migration for `router_nas_config` table
  - Store RADIUS configuration details
  
- [ ] **Task 1.3**: Create migration for `vouchers` table updates
  - Add binding strategy columns
  - Add bound router relationship
  
- [ ] **Task 1.4**: Create migration for `voucher_authentication_log` table
  - Track authentication attempts
  - Log binding events
  
- [ ] **Task 1.5**: Update `Router` model
  - Add relationships (parent, children, nasConfig)
  - Add scopes (isParent, isChild, hasNasGroup)
  - Add accessors (effectiveNasIdentifier)
  
- [ ] **Task 1.6**: Update `Voucher` model
  - Add binding relationship
  - Add scopes (bound, unbound, prebound, autobound)
  - Add methods (bind, unbind, canAuthenticateFrom)

### Phase 2: NAS Identifier System (Priority 1)

- [ ] **Task 2.1**: Create `NasIdentifierGenerator` service
  - Generate unique identifiers
  - Validation logic
  - Regeneration with conflict checking
  
- [ ] **Task 2.2**: Auto-generate NAS identifier on router creation
  - Hook into router creation event
  - Update existing routers (migration/command)
  
- [ ] **Task 2.3**: Create admin interface for NAS management
  - View NAS identifier
  - Regenerate button
  - Copy to clipboard

### Phase 3: MikroTik Auto-Configuration (Priority 2)

- [ ] **Task 3.1**: Create `RadiusConfigurator` service
  - Configure RADIUS settings via API
  - Set NAS identifier as system identity
  - Enable hotspot RADIUS
  
- [ ] **Task 3.2**: Create router RADIUS configuration UI
  - Configuration status display
  - Manual trigger button
  - Test connectivity button
  
- [ ] **Task 3.3**: Auto-configuration on router add
  - Background job for configuration
  - Retry logic on failure
  - Status notifications

### Phase 4: Voucher Binding Strategies (Priority 1)

- [ ] **Task 4.1**: Update voucher generation UI
  - Add binding strategy selector
  - Conditional MikroTik dropdown
  - NAS group option
  
- [ ] **Task 4.2**: Implement pre-bind logic
  - Validate router selection
  - Set binding in database
  - Update RADIUS sync
  
- [ ] **Task 4.3**: Implement auto-bind logic
  - Update activation API endpoint
  - Handle first authentication
  - Apply binding retroactively
  
- [ ] **Task 4.4**: Implement NAS group logic
  - Parent/child relationship UI
  - Inherit NAS identifier
  - Cascade binding rules

### Phase 5: RADIUS Integration Updates (Priority 1)

- [ ] **Task 5.1**: Update `sync-vouchers.py`
  - Include NAS-Identifier check when bound
  - Handle NAS group inheritance
  - Sync binding changes
  
- [ ] **Task 5.2**: Update `check-activations.py`
  - Detect auto-bind triggers
  - Call Laravel API with NAS info
  - Apply binding to RADIUS
  
- [ ] **Task 5.3**: Update Laravel RADIUS API
  - Handle NAS identifier in activation
  - Return binding instructions
  - Validate binding constraints

### Phase 6: Admin Management Features (Priority 2)

- [ ] **Task 6.1**: Update Router Index/Show pages
  - Display NAS info
  - Show child/parent relationships
  - RADIUS config status
  
- [ ] **Task 6.2**: Create Router NAS management component
  - Edit NAS identifier
  - Manage NAS group
  - Add/remove child devices
  
- [ ] **Task 6.3**: Update Voucher Index/Show pages
  - Display binding status
  - Show bound router
  - Authentication log preview
  
- [ ] **Task 6.4**: Create Voucher Binding management component
  - Change binding strategy
  - Manual bind/unbind
  - View auth history
  - Rebind actions

### Phase 7: Settings & Configuration (Priority 2)

- [ ] **Task 7.1**: Add RADIUS settings to general settings
  - Server IP configuration
  - Shared secret management
  - Port settings
  
- [ ] **Task 7.2**: Create NAS binding settings section
  - Enable/disable feature
  - Default binding strategy
  - NAS identifier prefix
  
- [ ] **Task 7.3**: Add feature toggle
  - Conditional UI elements
  - Permission checks
  - Feature documentation

### Phase 8: Authentication Logging (Priority 3)

- [ ] **Task 8.1**: Create authentication log repository
  - Log all authentication attempts
  - Track NAS identifier
  - Link to router
  
- [ ] **Task 8.2**: Create authentication log viewer UI
  - Per-voucher log view
  - Filter by router/NAS
  - Timeline visualization
  
- [ ] **Task 8.3**: Analytics and reporting
  - Usage by router
  - Binding patterns
  - Authentication success/failure rates

### Phase 9: Testing & Validation (Priority 1)

- [ ] **Task 9.1**: Unit tests for NAS identifier generation
- [ ] **Task 9.2**: Feature tests for binding strategies
- [ ] **Task 9.3**: Integration tests for RADIUS sync
- [ ] **Task 9.4**: API tests for activation with binding
- [ ] **Task 9.5**: End-to-end test scenarios:
  - Pre-bind during generation → Authenticate → Verify
  - Auto-bind on first use → Verify binding created
  - NAS group → Child auth → Verify parent binding works
  - Unbound voucher → Multiple routers → Verify works on all

### Phase 10: Documentation (Priority 2)

- [ ] **Task 10.1**: Update user documentation
  - How to use binding strategies
  - NAS group setup guide
  - Troubleshooting guide
  
- [ ] **Task 10.2**: Update admin documentation
  - NAS identifier management
  - RADIUS auto-configuration
  - Binding management
  
- [ ] **Task 10.3**: API documentation
  - New endpoints
  - RADIUS sync changes
  - Python script updates
  
- [ ] **Task 10.4**: Update RADIUS server documentation
  - NAS-Identifier attribute
  - Binding logic
  - Database schema

## Database Migration Order

1. `add_nas_identifier_to_routers_table.php`
2. `create_router_nas_config_table.php`
3. `add_binding_to_vouchers_table.php`
4. `create_voucher_authentication_log_table.php`
5. `populate_nas_identifiers_for_existing_routers.php` (data migration)

## Security Considerations

1. **Permission Checks**:
   - Only admins can modify NAS bindings
   - Resellers can only bind to their own routers
   - View-only users cannot change bindings

2. **Validation**:
   - NAS identifier uniqueness
   - Parent-child circular reference prevention
   - Binding strategy consistency

3. **RADIUS Security**:
   - Shared secret strength validation
   - Secure storage of secrets (encrypted)
   - API token validation for sync scripts

4. **Audit Trail**:
   - Log all binding changes
   - Track who made changes
   - Authentication attempt logging

## Performance Considerations

1. **Database Indexes**:
   - Index on `routers.nas_identifier`
   - Index on `vouchers.bound_router_id`
   - Index on `vouchers.binding_strategy`
   - Composite index on `radcheck(username, attribute)`

2. **Caching**:
   - Cache router NAS identifiers
   - Cache parent-child relationships
   - Cache RADIUS configuration status

3. **RADIUS Sync Optimization**:
   - Batch updates for voucher sync
   - Only sync changed vouchers
   - Efficient NAS identifier lookups

## Rollback Plan

1. **Feature Toggle**: Can disable entire feature via settings
2. **Migration Rollbacks**: All migrations have `down()` methods
3. **RADIUS Fallback**: If no NAS-Identifier check, voucher works everywhere (backward compatible)
4. **Data Preservation**: Binding data remains in database even if feature disabled

## Estimated Timeline

- **Phase 1-2**: 3-5 days (Database & NAS system)
- **Phase 3**: 2-3 days (MikroTik auto-config)
- **Phase 4-5**: 4-6 days (Binding strategies & RADIUS)
- **Phase 6-7**: 3-4 days (Admin UI & settings)
- **Phase 8**: 2-3 days (Logging)
- **Phase 9**: 3-4 days (Testing)
- **Phase 10**: 2-3 days (Documentation)

**Total**: ~20-30 days for complete implementation

## Success Criteria

1. ✅ Admin can pre-bind voucher to specific router during generation
2. ✅ Voucher auto-binds to first router used (if strategy selected)
3. ✅ Parent router with NAS group shares identity with children
4. ✅ Bound vouchers only work on bound router(s)
5. ✅ Unbound vouchers work on any router (backward compatible)
6. ✅ MikroTik auto-configures RADIUS on addition to panel
7. ✅ Authentication attempts logged with NAS information
8. ✅ Admin can view, change, and manage bindings
9. ✅ Python sync scripts correctly handle binding logic
10. ✅ All existing vouchers continue working (backward compatible)

## Next Steps

1. Review and approve plan
2. Create GitHub issues/tasks for each phase
3. Set up development branch
4. Begin Phase 1 implementation
5. Implement incrementally with testing at each phase
