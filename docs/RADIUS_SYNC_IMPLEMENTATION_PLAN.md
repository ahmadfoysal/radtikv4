# RADIUS Sync Implementation Plan

## Overview

Implement queue-based voucher synchronization from RADTik database to RADIUS server SQLite databases using Laravel Jobs.

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

---

## Task Checklist

### 1. Database Updates

- [ ] **Update vouchers table migration**
    - Add `radius_sync_status` enum: 'pending', 'synced', 'failed'
    - Add `radius_synced_at` timestamp (nullable)
    - Add `radius_sync_error` text (nullable)
    - File: `database/migrations/2025_10_25_095712_create_vouchers_table.php`

- [ ] **Update radius_servers table migration**
    - Add `api_base_url` string (for Python API endpoint)
    - Note: `auth_token` already exists for authentication
    - File: `database/migrations/2025_10_25_090949_create_radius_servers_table.php`

### 2. Model Updates

- [ ] **Update Voucher model**
    - Add new fields to `$fillable` array
    - Add `$casts` for enum and datetime
    - Add helper methods: `isPendingSync()`, `isSynced()`, `markAsSynced()`, `markAsFailed()`
    - File: `app/Models/Voucher.php`

- [ ] **Update RadiusServer model**
    - Add `api_base_url` to `$fillable`
    - Add `getApiUrlAttribute()` accessor (auto-format URL)
    - Add `getDecryptedTokenAttribute()` accessor
    - File: `app/Models/RadiusServer.php`

### 3. Service Layer

- [ ] **Create RadiusApiService**
    - Method: `__construct(RadiusServer $server)`
    - Method: `syncVoucher(Voucher $voucher)` - sends single voucher
    - Method: `syncBatch(Collection $vouchers)` - sends batch (up to 250)
    - Method: `deleteVoucher(string $username)` - removes from RADIUS
    - Uses Laravel HTTP client with Bearer token authentication
    - File: `app/Services/RadiusApiService.php`

### 4. Job Queue

- [ ] **Create SyncVouchersToRadiusJob**
    - Property: `$batchId` (string) - voucher batch identifier
    - Property: `$routerId` (int) - router ID to get RADIUS server
    - Implements: `ShouldQueue` interface
    - Method: `handle(RadiusApiService $radiusApi)`
    - Logic:
        1. Get vouchers where batch = $batchId and status = 'pending'
        2. Get RadiusServer from Router relationship
        3. Chunk vouchers (250 per batch)
        4. For each chunk, call `RadiusApiService::syncBatch()`
        5. Update voucher `radius_sync_status` to 'synced' or 'failed'
    - Handle exceptions and retry logic (3 attempts)
    - File: `app/Jobs/SyncVouchersToRadiusJob.php`

### 5. Livewire Component Update

- [ ] **Update Voucher/Generate component**
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
    - Endpoint: `POST /import` or `POST /batch`
    - Authentication: Bearer token (from `radius_servers.auth_token`)
    - Request payload format:
        ```json
        {
            "vouchers": [
                {
                    "username": "ABC12345",
                    "password": "pass123",
                    "profile": "1day-1gb",
                    "time_limit": 86400,
                    "data_limit": 1073741824,
                    "simultaneous_use": 1
                }
            ]
        }
        ```
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
6. **Dynamic API URL**: Fetched from database (radius_servers.api_base_url)
7. **Token Authentication**: Uses existing auth_token field (encrypted)

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
// Vouchers 1-250 → API Call 1
// Vouchers 251-500 → API Call 2
// Vouchers 501-750 → API Call 3
// Vouchers 751-1000 → API Call 4
```

**Step 4: Each voucher gets status**

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
    'host' => '192.168.1.100',
    'api_base_url' => 'http://192.168.1.100:5000',
    'auth_token' => encrypt('your-secure-token-here'),
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

| Action | File Path                                                               | Description               |
| ------ | ----------------------------------------------------------------------- | ------------------------- |
| Modify | `database/migrations/2025_10_25_095712_create_vouchers_table.php`       | Add sync fields           |
| Modify | `database/migrations/2025_10_25_090949_create_radius_servers_table.php` | Add api_base_url          |
| Modify | `app/Models/Voucher.php`                                                | Add sync methods          |
| Modify | `app/Models/RadiusServer.php`                                           | Add API URL accessor      |
| Create | `app/Services/RadiusApiService.php`                                     | API client for RADIUS     |
| Create | `app/Jobs/SyncVouchersToRadiusJob.php`                                  | Background sync job       |
| Modify | `app/Livewire/Voucher/Generate.php`                                     | Dispatch job after insert |

**Total**: 4 files to modify, 2 files to create

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
✅ RADIUS server receives batch API call  
✅ Voucher sync_status updates to 'synced'  
✅ RADIUS SQLite contains radcheck/radreply entries  
✅ Failed syncs retry 3 times and log errors

---

## Notes

- This plan uses existing Laravel features (queue, jobs table)
- No new migrations for jobs (using Laravel default)
- Python API endpoint must be implemented separately
- RADIUS server must be running and accessible
- Queue worker must be running (via systemd in production)
