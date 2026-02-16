# RADIUS Sync Implementation Plan

## Overview

Implement queue-based voucher synchronization from RADTik database to RADIUS server SQLite databases using Laravel Jobs.

**Key Points:**
- Syncs voucher credentials (username, password) to RADIUS authentication
- Sends MikroTik rate limits directly to RADIUS (no profile table needed)
- Uses NAS identifier binding for multi-router support
- Shared user limits managed in MikroTik hotspot profile, not RADIUS
- Background processing via Laravel queue for non-blocking UX

---

## Architecture Flow

```
User Generates Vouchers (Livewire)
    ↓
Insert to RADTik Database (vouchers table)
    ↓
Dispatch Job: SyncVouchersToRadiusJob
    ↓
Job Processes in Background (Queue Worker)
    ↓
Call RADIUS Server API (Python FastAPI)
    ↓
Insert into RADIUS SQLite (radcheck, radreply tables)
```

### Sync Data Mapping

**From RADTik to RADIUS:**

| Source                  | Destination              | Data                                          |
| ----------------------- | ------------------------ | --------------------------------------------- |
| `vouchers.username`     | `radcheck.username`      | Username for authentication                   |
| `vouchers.password`     | `radcheck.value`         | Cleartext password (attribute: 'Cleartext-Password') |
| `routers.nas_identifier`| `radcheck.value`         | MikroTik identifier (attribute: 'NAS-Identifier') |
| `profiles.rate_limit`   | `radreply.value`         | MikroTik rate limit (e.g., "512k/512k")       |

**RADIUS Tables Structure:**
- **radcheck**: Stores authentication credentials (2 rows per voucher)
  - Row 1: `username`, attribute='Cleartext-Password', op=':=', value='password'
  - Row 2: `username`, attribute='NAS-Identifier', op='==', value='nas_identifier'
- **radreply**: Stores authorization attributes (1 row per voucher)
  - Row 1: `username`, attribute='Mikrotik-Rate-Limit', op=':=', value='rate_limit'

---

## Task Checklist

### 1. Database Updates

- [x] **Update vouchers table migration**
    - Add `radius_sync_status` enum: 'pending', 'synced', 'failed'
    - Add `radius_synced_at` timestamp (nullable)
    - Add `radius_sync_error` text (nullable)
    - File: `database/migrations/2025_10_25_095712_create_vouchers_table.php`

- [x] **radius_servers table** (No changes needed)
    - Uses existing `host` field for API base URL (constructs `http://{host}:5000`)
    - Uses existing `auth_token` field for authentication (encrypted)
    - File: `database/migrations/2025_10_25_090949_create_radius_servers_table.php`

### 2. Model Updates

- [x] **Update Voucher model**
    - Add new fields to `$fillable` array
    - Add `$casts` for enum and datetime
    - Add helper methods: `isPendingSync()`, `isSynced()`, `isSyncFailed()`, `markAsSynced()`, `markAsFailed()`, `resetSyncStatus()`
    - File: `app/Models/Voucher.php`

- [x] **Update RadiusServer model**
    - Add `auth_token` to `$fillable`
    - Add `getApiUrlAttribute()` accessor (constructs URL from host field)
    - Add `getSyncEndpointAttribute()` accessor (full endpoint URL)
    - Add `getAuthTokenAttribute()` and `setAuthTokenAttribute()` for encryption
    - File: `app/Models/RadiusServer.php`

### 3. Service Layer

- [x] **Create RadiusApiService**
    - Method: `__construct(RadiusServer $server)`
    - Method: `syncBatch(Collection $vouchers, Router $router)` - sends batch (up to 250)
        - Extracts: `username`, `password` from voucher
        - Gets `mikrotik_rate_limit` from voucher's profile relationship
        - Gets `nas_identifier` from router
        - Sends simplified payload to RADIUS API
    - Method: `deleteVoucher(string $username)` - removes from RADIUS (optional)
    - Uses Laravel HTTP client with Bearer token authentication
    - File: `app/Services/RadiusApiService.php`

### 4. Job Queue

- [x] **Create SyncVouchersToRadiusJob**
    - Property: `$batchId` (string) - voucher batch identifier
    - Property: `$routerId` (int) - router ID
    - Implements: `ShouldQueue` interface
    - Method: `handle(RadiusApiService $radiusApi)`
    - Logic:
        1. Get vouchers where batch = $batchId and status = 'pending'
        2. Load voucher relationships: `profile`, `router` with `radius_server`
        3. Get Router and its RadiusServer
        4. Chunk vouchers (250 per batch)
        5. For each chunk:
            - Map vouchers to format: `[username, password, mikrotik_rate_limit, nas_identifier]`
            - Call `RadiusApiService::syncBatch($vouchers, $router)`
            - Update voucher `radius_sync_status` to 'synced' or 'failed'
    - Handle exceptions and retry logic (3 attempts)
    - File: `app/Jobs/SyncVouchersToRadiusJob.php`

### 5. Livewire Component Update

- [x] **Update Voucher/Generate component**
    - After `Voucher::insert($rows)` success
    - Add: `SyncVouchersToRadiusJob::dispatch($batchId, $this->router_id)`
    - Update success message: "Vouchers generated! Syncing to RADIUS server..."
    - File: `app/Livewire/Voucher/Generate.php`

### 6. Configuration

- [ ] **Queue Configuration**
    - Ensure `database/migrations/*_create_jobs_table.php` exists (Laravel default)
    - Update `.env`: `QUEUE_CONNECTION=database`
    - No new migration needed (use Laravel default jobs table)

### 7. Python API Integration

- [ ] **RADIUS Server API Endpoint Requirements**
    - Endpoint: `POST /sync/vouchers`
    - Authentication: Bearer token (from `radius_servers.auth_token`)
    - Request payload format:
        ```json
        {
            "vouchers": [
                {
                    "username": "ABC12345",
                    "password": "pass123",
                    "mikrotik_rate_limit": "512k/512k",
                    "nas_identifier": "mikrotik-router-1"
                }
            ]
        }
        ```
    - RADIUS server handles:
        - Inserts **2 rows** into `radcheck` table per voucher:
            - Row 1: `(username, 'Cleartext-Password', ':=', password)`
            - Row 2: `(username, 'NAS-Identifier', '==', nas_identifier)`
        - Inserts **1 row** into `radreply` table per voucher:
            - Row 1: `(username, 'Mikrotik-Rate-Limit', ':=', mikrotik_rate_limit)`
        - **Total**: 3 database rows per voucher
    - Response format:
        ```json
        {
            "success": true,
            "synced": 250,
            "failed": 0,
            "errors": []
        }
        ```

### 8. Testing & Validation

- [ ] **Manual Testing Steps**
    1. Run migration: `php artisan migrate:fresh --seed`
    2. Create RADIUS server record with `api_base_url` and `auth_token`
    3. Link router to RADIUS server
    4. Start queue worker: `php artisan queue:work`
    5. Generate vouchers via Livewire component
    6. Check `jobs` table for dispatched job
    7. Monitor job execution in queue worker output
    8. Verify vouchers table `radius_sync_status` updated to 'synced'
    9. Check RADIUS SQLite database for inserted records

- [ ] **Error Handling Test**
    1. Stop Python API service
    2. Generate vouchers
    3. Verify job fails and retries
    4. After 3 attempts, verify `radius_sync_status` = 'failed'
    5. Check `radius_sync_error` field contains error message

### 9. Queue Worker Deployment

- [ ] **Production Setup**
    - Create systemd service for queue worker
    - File: `/etc/systemd/system/radtik-queue-worker.service`
    - Command: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
    - Enable service: `sudo systemctl enable radtik-queue-worker`
    - Start service: `sudo systemctl start radtik-queue-worker`

---

## Implementation Order

**Phase 1: Database & Models (Foundation)**

1. Update vouchers table migration (add sync fields)
2. Update radius_servers table migration (add api_base_url)
3. Update Voucher model
4. Update RadiusServer model

**Phase 2: Service & Job (Core Logic)** 5. Create RadiusApiService 6. Create SyncVouchersToRadiusJob

**Phase 3: Integration (Connect Components)** 7. Update Generate Livewire component 8. Configure queue connection

**Phase 4: Testing & Deployment** 9. Manual testing 10. Deploy queue worker

---

## Key Design Decisions

1. **Async Processing**: User gets instant feedback, sync happens in background
2. **Batch Processing**: Group 250 vouchers per API call for efficiency
3. **Retry Logic**: 3 automatic retries with exponential backoff
4. **Status Tracking**: Each voucher tracks its sync status independently
5. **Error Logging**: Failed syncs store error message for debugging
6. **Simplified Payload**: Only sends username, password, rate_limit, nas_identifier
7. **No Profile Table**: Rate limits passed directly, no RADIUS profile management
8. **Shared User Control**: Managed by MikroTik hotspot profile (not RADIUS simultaneous_use)
9. **NAS Binding**: Uses nas_identifier from router for proper device binding

---

## Data Flow Example

**Step 1: User generates 1000 vouchers**

```php
// Livewire Component
Voucher::insert($rows); // 1000 vouchers with batch = 'ABC-12345'
```

**Step 2: Job dispatched**

```php
SyncVouchersToRadiusJob::dispatch('ABC-12345', 1); // batch_id, router_id
```

**Step 3: Job processes in chunks**

```php
// Get vouchers with relationships
$vouchers = Voucher::with(['profile', 'router.radiusServer'])
    ->where('batch', 'ABC-12345')
    ->where('radius_sync_status', 'pending')
    ->get();

// Transform to API format
$payload = [
    'vouchers' => $vouchers->map(fn($v) => [
        'username' => $v->username,
        'password' => $v->password,
        'mikrotik_rate_limit' => $v->profile->rate_limit, // e.g., "512k/512k"
        'nas_identifier' => $v->router->nas_identifier    // e.g., "mikrotik-router-1"
    ])
];

// Send to RADIUS server
// Batch 1: Vouchers 1-250 → API Call 1
// Batch 2: Vouchers 251-500 → API Call 2
// Batch 3: Vouchers 501-750 → API Call 3
// Batch 4: Vouchers 751-1000 → API Call 4
```

**Step 4: RADIUS server processes**

```sql
-- radcheck table (authentication) - 2 rows per voucher
-- Password row
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('ABC12345', 'Cleartext-Password', ':=', 'pass123');

-- NAS Identifier row (for device binding)
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('ABC12345', 'NAS-Identifier', '==', 'mikrotik-router-1');

-- radreply table (authorization) - 1 row per voucher
INSERT INTO radreply (username, attribute, op, value)
VALUES ('ABC12345', 'Mikrotik-Rate-Limit', ':=', '512k/512k');
```

**Result**: Each voucher creates **3 database rows** (2 in radcheck + 1 in radreply)

**Step 5: Each voucher gets status**

```
radius_sync_status = 'synced'
radius_synced_at = '2026-02-16 10:30:45'
```

---

## Configuration Requirements

### RADTik Server (.env)

```env
QUEUE_CONNECTION=database
```

### RADIUS Server Configuration (Database)

```php
RadiusServer::create([
    'name' => 'Main RADIUS Server',
    'host' => '192.168.1.100',  // Used to construct API URL: http://192.168.1.100:5000
    'auth_token' => 'your-secure-token-here',  // Will be auto-encrypted
    'is_active' => true,
]);
```

### Router Configuration (Database)

```php
Router::create([
    'name' => 'MikroTik Router 1',
    'radius_server_id' => 1, // Links to RADIUS server
    // ...other fields
]);
```

---

## Benefits of This Approach

✅ **Non-Blocking UI**: Users don't wait for RADIUS sync  
✅ **Scalable**: Handles 1000+ vouchers efficiently  
✅ **Reliable**: Automatic retries on failure  
✅ **Observable**: Track sync status per voucher  
✅ **Debuggable**: Error messages stored in database  
✅ **Flexible**: Easy to add more RADIUS servers  
✅ **Follows RADTik Patterns**: Uses Livewire, Jobs, Service layer

---

## Future Enhancements (Optional)

- [ ] Add admin dashboard to view sync status
- [ ] Add retry button for failed vouchers
- [ ] Add batch sync status page
- [ ] Add webhook to notify RADTik when Python API is down
- [ ] Add sync progress indicator (Livewire polling)
- [ ] Add bulk delete sync (when vouchers expire)

---

## Files to Create/Modify

| Status | Action | File Path                                                               | Description               |
| ------ | ------ | ----------------------------------------------------------------------- | ------------------------- |
| ✅     | Modify | `database/migrations/2025_10_25_095712_create_vouchers_table.php`       | Add sync fields           |
| ✅     | Modify | `app/Models/Voucher.php`                                                | Add sync methods          |
| ✅     | Modify | `app/Models/RadiusServer.php`                                           | Add API URL accessor      |
| ✅     | Create | `app/Services/RadiusApiService.php`                                     | API client for RADIUS     |
| ✅     | Create | `app/Jobs/SyncVouchersToRadiusJob.php`                                  | Background sync job       |
| ✅     | Modify | `app/Livewire/Voucher/Generate.php`                                     | Dispatch job after insert |

**Progress**: 6/6 files completed ✅

---

## Estimated Implementation Time

- Database updates: 15 minutes
- Model updates: 15 minutes
- Service creation: 30 minutes
- Job creation: 30 minutes
- Livewire integration: 15 minutes
- Testing: 30 minutes

**Total: ~2.5 hours**

---

## Success Criteria

✅ Vouchers generated instantly in RADTik DB  
✅ Job dispatched to queue successfully  
✅ Queue worker processes job in background  
✅ RADIUS server receives batch API call with correct format  
✅ Voucher sync_status updates to 'synced'  
✅ RADIUS `radcheck` table contains **2 rows per voucher**:
   - Password row: `(username, 'Cleartext-Password', ':=', password)`
   - NAS row: `(username, 'NAS-Identifier', '==', nas_identifier)`
✅ RADIUS `radreply` table contains **1 row per voucher**:
   - Rate limit row: `(username, 'Mikrotik-Rate-Limit', ':=', rate_limit)`  
✅ Failed syncs retry 3 times and log errors

---

## Database Row Calculation

For batch generation:
- **100 vouchers** = 300 RADIUS rows (200 radcheck + 100 radreply)
- **500 vouchers** = 1,500 RADIUS rows (1,000 radcheck + 500 radreply)
- **1000 vouchers** = 3,000 RADIUS rows (2,000 radcheck + 1,000 radreply)

---

## Notes

- This plan uses existing Laravel features (queue, jobs table)
- No new migrations for jobs (using Laravel default)
- Python API endpoint must be implemented separately
- RADIUS server must be running and accessible
- Queue worker must be running (via systemd in production)
- **Shared user management** is handled by MikroTik hotspot profile, not RADIUS
- **No profile table** in RADIUS - rate limits passed directly to radreply

---

## Python RADIUS Server Implementation Reference

```python
# Example Python endpoint implementation
@app.post("/sync/vouchers")
async def sync_vouchers(request: Request):
    data = await request.json()
    vouchers = data.get('vouchers', [])
    
    synced = 0
    failed = 0
    errors = []
    
    for voucher in vouchers:
        try:
            username = voucher['username']
            password = voucher['password']
            rate_limit = voucher['mikrotik_rate_limit']
            nas_id = voucher['nas_identifier']
            
            # Insert 2 rows into radcheck
            cursor.execute("""
                INSERT INTO radcheck (username, attribute, op, value) VALUES
                (?, 'Cleartext-Password', ':=', ?),
                (?, 'NAS-Identifier', '==', ?)
            """, (username, password, username, nas_id))
            
            # Insert 1 row into radreply
            cursor.execute("""
                INSERT INTO radreply (username, attribute, op, value) VALUES
                (?, 'Mikrotik-Rate-Limit', ':=', ?)
            """, (username, rate_limit))
            
            synced += 1
            
        except Exception as e:
            failed += 1
            errors.append(f"{username}: {str(e)}")
    
    conn.commit()
    
    return {
        "success": failed == 0,
        "synced": synced,
        "failed": failed,
        "errors": errors
    }
```
