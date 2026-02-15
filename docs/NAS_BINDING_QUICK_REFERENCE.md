# NAS Binding System - Quick Reference

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         RADTIK LARAVEL APP                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐      ┌──────────────┐      ┌──────────────┐  │
│  │   Routers    │      │   Vouchers   │      │   Settings   │  │
│  │              │      │              │      │              │  │
│  │ nas_id       │◄─────┤ bound_router │      │ radius_ip    │  │
│  │ parent_id    │      │ strategy     │      │ shared_key   │  │
│  │ is_nas_dev   │      │ bound_at     │      └──────────────┘  │
│  └──────────────┘      └──────────────┘                        │
│         │                      │                                │
│         │                      │                                │
│  ┌──────▼──────────────────────▼────────┐                      │
│  │    VoucherBindingService     │                               │
│  │  - preBind()                 │                               │
│  │  - autoBind()                │                               │
│  │  - canAuthenticate()         │                               │
│  └──────────────┬───────────────┘                               │
│                 │                                                │
│  ┌──────────────▼───────────────┐                               │
│  │   RADIUS API Endpoints       │                               │
│  │  /api/radius/sync/vouchers   │                               │
│  │  /api/radius/voucher/activate│                               │
│  └──────────────┬───────────────┘                               │
└─────────────────┼──────────────────────────────────────────────┘
                  │ JSON API
                  │
┌─────────────────▼──────────────────────────────────────────────┐
│                   FREERADIUS SERVER                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────┐          │
│  │     Python Sync Scripts (Cron Jobs)              │          │
│  │                                                   │          │
│  │  sync-vouchers.py (every 2 min)                  │          │
│  │  ├─ Fetch vouchers from Laravel API              │          │
│  │  ├─ Update radcheck (username, password)         │          │
│  │  └─ Add NAS-Identifier if bound ◄────────────┐   │          │
│  │                                               │   │          │
│  │  check-activations.py (every 1 min)          │   │          │
│  │  ├─ Read radpostauth (unprocessed)           │   │          │
│  │  ├─ Extract NAS identifier                   │   │          │
│  │  ├─ Call Laravel activation API               │   │          │
│  │  └─ Apply auto-bind if instructed ────────────┘   │          │
│  └──────────────────────────────────────────────────┘          │
│                                                                 │
│  ┌──────────────────────────────────────────────────┐          │
│  │         SQLite Database (radius.db)              │          │
│  │                                                   │          │
│  │  radcheck                                        │          │
│  │  ├─ username                                     │          │
│  │  ├─ password (Cleartext-Password)               │          │
│  │  └─ nas_identifier (NAS-Identifier) ◄───────────┼──────┐   │
│  │                                                   │      │   │
│  │  radpostauth                                     │      │   │
│  │  ├─ username                                     │      │   │
│  │  ├─ calling_station_id (MAC)                    │      │   │
│  │  ├─ nas_identifier                              │      │   │
│  │  └─ processed (sync flag)                       │      │   │
│  └──────────────────────────────────────────────────┘      │   │
│                                                             │   │
└─────────────────────────────────────────────────────────────┼──┘
                                                               │
                                                               │
┌──────────────────────────────────────────────────────────────▼──┐
│                      MIKROTIK ROUTERS                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌────────────────────────┐       ┌────────────────────────┐   │
│  │  Parent Router         │       │  Child Router (NAS)    │   │
│  │  (Main Hotspot)        │       │  (Branch/AP)           │   │
│  │                        │       │                        │   │
│  │  NAS ID: radtik-1-abc  │◄──────┤  Inherits: radtik-1-abc│   │
│  │  /radius               │       │  /radius               │   │
│  │  - server: Laravel IP  │       │  - server: Laravel IP  │   │
│  │  - secret: shared_key  │       │  - secret: shared_key  │   │
│  │  /system identity      │       │  /system identity      │   │
│  │  - set: radtik-1-abc   │       │  - set: radtik-1-abc   │   │
│  └────────────────────────┘       └────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Binding Strategies Flow

### Strategy 1: Pre-Bind (During Generation)

```
Admin Panel
    │
    ├─ Generate Voucher
    │   ├─ Select: "Pre-bind to MikroTik"
    │   ├─ Choose: Router A (nas_id: radtik-1-abc)
    │   └─ Optional: Allow NAS group
    │
    ▼
Database (Laravel)
    voucher.binding_strategy = 'pre_bind'
    voucher.bound_router_id = Router A
    │
    ▼
RADIUS Sync (Python)
    radcheck: username='voucher123', password='pass123'
    radcheck: username='voucher123', attr='NAS-Identifier', value='radtik-1-abc'
    │
    ▼
Authentication
    User attempts login from Router A → ✅ Success (NAS matches)
    User attempts login from Router B → ❌ Fail (NAS doesn't match)
```

### Strategy 2: Auto-Bind (First Use)

```
Admin Panel
    │
    ├─ Generate Voucher
    │   └─ Select: "Auto-bind on first use"
    │
    ▼
Database (Laravel)
    voucher.binding_strategy = 'auto_bind'
    voucher.bound_router_id = NULL
    │
    ▼
RADIUS Sync (Python)
    radcheck: username='voucher123', password='pass123'
    (No NAS-Identifier check yet)
    │
    ▼
First Authentication (Router A)
    User logs in from Router A (nas_id: radtik-1-abc)
    │
    ▼
radpostauth Table
    username='voucher123'
    nas_identifier='radtik-1-abc'
    processed=0
    │
    ▼
check-activations.py (Cron)
    Detects unprocessed auth
    Calls Laravel API with NAS info
    │
    ▼
Laravel API Response
    {
        "should_bind_nas": true,
        "nas_identifier": "radtik-1-abc"
    }
    │
    ▼
Database Update (Laravel)
    voucher.bound_router_id = Router A
    voucher.bound_at = NOW()
    │
    ▼
RADIUS Update (Python)
    radcheck: ADD NAS-Identifier check
    radpostauth: processed=1
    │
    ▼
Subsequent Authentication
    User attempts from Router A → ✅ Success (now bound)
    User attempts from Router B → ❌ Fail (bound to Router A)
```

### Strategy 3: NAS Device Group

```
Admin Panel - Router Management
    │
    ├─ Parent Router (ID: 1, nas_id: radtik-1-abc)
    │   ├─ Enable NAS Grouping ✓
    │   └─ Add Child Devices:
    │       ├─ Child Router 1 (ID: 5) → Inherits: radtik-1-abc
    │       └─ Child Router 2 (ID: 7) → Inherits: radtik-1-abc
    │
    ▼
Database (Laravel)
    Router 1: nas_identifier='radtik-1-abc', nas_group_enabled=true
    Router 5: parent_router_id=1, is_nas_device=true
    Router 7: parent_router_id=1, is_nas_device=true
    │
    ▼
Voucher Generation
    Admin binds voucher to Router 1 (parent)
    voucher.bound_router_id = 1
    voucher.allow_nas_group = true
    │
    ▼
RADIUS Sync (Python)
    radcheck: NAS-Identifier = 'radtik-1-abc'
    (Parent's identifier used)
    │
    ▼
Authentication Options
    Router 1 (Parent)    → ✅ Success (nas_id: radtik-1-abc)
    Router 5 (Child)     → ✅ Success (inherited nas_id: radtik-1-abc)
    Router 7 (Child)     → ✅ Success (inherited nas_id: radtik-1-abc)
    Router X (Different) → ❌ Fail (different nas_id)
```

### Strategy 4: No Binding (Default/Backward Compatible)

```
Admin Panel
    │
    ├─ Generate Voucher
    │   └─ Select: "No binding" (or leave unchecked)
    │
    ▼
Database (Laravel)
    voucher.binding_strategy = 'none'
    voucher.bound_router_id = NULL
    │
    ▼
RADIUS Sync (Python)
    radcheck: username='voucher123', password='pass123'
    (No NAS-Identifier check)
    │
    ▼
Authentication
    Any Router A → ✅ Success
    Any Router B → ✅ Success
    Any Router C → ✅ Success
    (Works everywhere - traditional behavior)
```

## Database Schema Reference

### Laravel Tables

#### `routers` table
```sql
id                  bigint
name                varchar(255)
ip_address          varchar(45)
username            varchar(100)
password            varchar(255)  -- encrypted
nas_identifier      varchar(100)  🆕 UNIQUE
parent_router_id    bigint        🆕 nullable, FK to routers.id
is_nas_device       boolean       🆕 default false
nas_group_enabled   boolean       🆕 default false
radius_configured   boolean       🆕 default false
created_at          timestamp
updated_at          timestamp
```

#### `vouchers` table
```sql
id                  bigint
code                varchar(50)
password            varchar(50)
binding_strategy    enum          🆕 'none','pre_bind','auto_bind'
bound_router_id     bigint        🆕 nullable, FK to routers.id
bound_at            timestamp     🆕 nullable
allow_nas_group     boolean       🆕 default false
created_at          timestamp
updated_at          timestamp
```

#### `router_nas_config` table 🆕
```sql
id                  bigint
router_id           bigint FK
radius_server_ip    varchar(45)
radius_port         integer (default 1812)
shared_secret       varchar(255) encrypted
accounting_enabled  boolean
accounting_port     integer (default 1813)
configured_at       timestamp
last_sync_at        timestamp
```

#### `voucher_authentication_log` table 🆕
```sql
id                  bigint
voucher_id          bigint FK
router_id           bigint FK nullable
nas_identifier      varchar(100)
mac_address         varchar(17)
nas_ip_address      varchar(45)
authenticated_at    timestamp
binding_applied     boolean (auto-bind event)
success             boolean
```

### RADIUS Tables (SQLite)

#### `radcheck` table
```sql
id          INTEGER PRIMARY KEY
username    varchar(64)
attribute   varchar(64)  -- 'Cleartext-Password' OR 'NAS-Identifier' 🆕
op          char(2)      -- ':=' OR '=='
value       varchar(253) -- password OR nas_identifier
```

Example entries:
```sql
-- Unbound voucher (works everywhere)
(1, 'voucher123', 'Cleartext-Password', ':=', 'pass123')

-- Bound voucher (only works on specific NAS)
(1, 'voucher123', 'Cleartext-Password', ':=', 'pass123')
(1, 'voucher123', 'NAS-Identifier', '==', 'radtik-1-abc') 🆕
```

## Permission Structure

```
├─ manage_routers
│   ├─ create_router
│   ├─ edit_router
│   ├─ delete_router
│   └─ configure_router_radius 🆕
│
├─ manage_vouchers
│   ├─ generate_vouchers
│   │   └─ with_nas_binding 🆕 (optional separate permission)
│   ├─ edit_voucher
│   └─ manage_voucher_binding 🆕
│
└─ manage_nas_groups 🆕
    ├─ create_nas_group
    ├─ manage_nas_devices
    └─ configure_nas_hierarchy
```

## API Endpoints Summary

### New Endpoints

```php
// RADIUS Integration API (Python scripts)
POST /api/radius/sync/vouchers
    → Returns: Voucher list with binding info

POST /api/radius/voucher/activate
    Body: { username, nas_identifier, mac_address }
    → Returns: { should_bind_nas, should_bind_mac, nas_identifier }

GET /api/radius/router/nas-identifier/{nas}
    → Returns: Router details for NAS identifier

// Admin API (Frontend)
POST /admin/routers/{router}/configure-radius
    → Triggers MikroTik RADIUS auto-configuration

POST /admin/routers/{router}/regenerate-nas
    → Generates new NAS identifier

POST /admin/routers/{router}/test-radius
    → Tests RADIUS connectivity

POST /admin/vouchers/{voucher}/bind
    Body: { router_id, strategy }
    → Manually bind voucher

DELETE /admin/vouchers/{voucher}/unbind
    → Remove binding

GET /admin/vouchers/{voucher}/auth-log
    → Returns authentication history
```

## Configuration Files

### `.env` additions
```bash
# RADIUS Server Configuration
RADIUS_SERVER_IP=192.168.1.100
RADIUS_AUTH_PORT=1812
RADIUS_ACCT_PORT=1813
RADIUS_SHARED_SECRET=your-secret-key
RADIUS_API_SECRET=token-for-python-scripts

# NAS Binding Feature
NAS_BINDING_ENABLED=true
NAS_IDENTIFIER_PREFIX=radtik
AUTO_CONFIGURE_MIKROTIK=true
```

### `config/radtik.php` additions
```php
'radius' => [
    'server_ip' => env('RADIUS_SERVER_IP', '127.0.0.1'),
    'auth_port' => env('RADIUS_AUTH_PORT', 1812),
    'acct_port' => env('RADIUS_ACCT_PORT', 1813),
    'shared_secret' => env('RADIUS_SHARED_SECRET'),
    'api_secret' => env('RADIUS_API_SECRET'),
],

'nas_binding' => [
    'enabled' => env('NAS_BINDING_ENABLED', true),
    'identifier_prefix' => env('NAS_IDENTIFIER_PREFIX', 'radtik'),
    'auto_configure' => env('AUTO_CONFIGURE_MIKROTIK', true),
    'default_strategy' => 'none', // 'none', 'pre_bind', 'auto_bind'
],
```

## Testing Scenarios

### Test Case 1: Pre-Bind Voucher
```
1. Generate voucher with pre-bind to Router A
2. Attempt auth from Router A → Expect: Success
3. Attempt auth from Router B → Expect: Failure
4. Check radcheck has NAS-Identifier entry
5. Verify voucher.bound_router_id = Router A
```

### Test Case 2: Auto-Bind Voucher
```
1. Generate voucher with auto-bind strategy
2. Verify voucher.bound_router_id = NULL
3. First auth from Router A → Expect: Success
4. Check radpostauth has entry with nas_identifier
5. Wait for check-activations.py cron
6. Verify voucher.bound_router_id = Router A
7. Verify radcheck has NAS-Identifier entry
8. Attempt auth from Router B → Expect: Failure
```

### Test Case 3: NAS Group
```
1. Setup Router 1 as parent with NAS grouping
2. Add Router 5 and 7 as children
3. Verify children inherit parent's nas_identifier
4. Generate voucher bound to Router 1 with NAS group enabled
5. Attempt auth from Router 1 → Expect: Success
6. Attempt auth from Router 5 → Expect: Success
7. Attempt auth from Router 7 → Expect: Success
8. Attempt auth from Router 9 (different) → Expect: Failure
```

### Test Case 4: No Binding (Backward Compatible)
```
1. Generate voucher with no binding
2. Verify voucher.binding_strategy = 'none'
3. Attempt auth from Router A → Expect: Success
4. Attempt auth from Router B → Expect: Success
5. Verify no NAS-Identifier in radcheck
```

## Common Issues & Solutions

### Issue: Voucher not working on bound router
**Check:**
- NAS identifier matches in router config and radcheck
- MikroTik is sending correct nas_identifier
- RADIUS sync completed successfully

### Issue: Auto-bind not triggering
**Check:**
- check-activations.py cron job running
- radpostauth has unprocessed entries
- Laravel API accessible from RADIUS server
- API token is correct

### Issue: Child router not inheriting parent NAS
**Check:**
- parent_router_id is set correctly
- Parent has nas_group_enabled = true
- Child has is_nas_device = true
- MikroTik configured with parent's NAS identifier

---

**Document Version**: 1.0
**Last Updated**: 2026-02-15
**Related Docs**: 
- `NAS_BINDING_IMPLEMENTATION_PLAN.md`
- `NAS_BINDING_CHECKLIST.md`
