# FreeRADIUS - Laravel Integration Plan

## Overview

This document outlines the integration between FreeRADIUS (with SQLite backend) and Laravel RADTik v4 for automated voucher synchronization, MAC address binding, and user lifecycle management.

## Current System State

### Laravel Database Schema

**Vouchers Table:**

- `username`, `password` - User credentials
- `status` - Enum: active, inactive, expired, disabled
- `mac_address` - Nullable, stores bound MAC
- `activated_at` - First login timestamp
- `expires_at` - Expiration datetime
- `user_profile_id` - Links to user profile
- `router_id` - Associated router
- `bytes_in`, `bytes_out` - Data usage tracking

**User Profiles Table:**

- `name` - Profile name
- `rate_limit` - Bandwidth limit (e.g., "10M/10M")
- `validity` - Session duration (e.g., "1d", "30d")
- `mac_binding` - Boolean flag for MAC binding
- `shared_users` - Concurrent connections allowed
- `price` - Profile cost

### FreeRADIUS Database Schema

**radcheck Table:**

- Stores user credentials
- Attributes: `Cleartext-Password`, `Auth-Type`

**radreply Table:**

- Stores per-user reply attributes
- Used for: rate limits, session timeouts, idle timeouts

**radacct Table:**

- Accounting records (session start/stop, data usage)

**radpostauth Table:**

- Post-authentication logs
- Captures: `username`, `calling_station_id` (MAC), `nas_identifier`, `authdate`, `reply`

---

## Integration Architecture

### Three-Tier Synchronization Flow

```
┌─────────────────┐       ┌──────────────────┐       ┌──────────────────┐
│  Laravel DB     │◄─────►│  Python Sync     │◄─────►│  FreeRADIUS DB   │
│  (MySQL/Pgsql)  │       │  Service         │       │  (SQLite)        │
└─────────────────┘       └──────────────────┘       └──────────────────┘
        │                         │                           │
        │                         │                           │
    Vouchers                 Periodic Sync              radcheck/radreply
    Profiles                 API Calls                  radpostauth
```

---

## Implementation Components

### 1. Laravel API Endpoints

Create new API routes for FreeRADIUS synchronization:

**File:** `routes/api.php` (create if not exists)

```php
Route::middleware('api')->prefix('radius')->group(function () {
    // Sync vouchers to FreeRADIUS
    Route::post('/sync/vouchers', [RadiusApiController::class, 'syncVouchers']);

    // Update voucher MAC and activation
    Route::post('/voucher/activate', [RadiusApiController::class, 'activateVoucher']);

    // Report user accounting data
    Route::post('/voucher/accounting', [RadiusApiController::class, 'updateAccounting']);

    // Check if voucher should be deleted
    Route::get('/sync/deleted-vouchers', [RadiusApiController::class, 'getDeletedVouchers']);
});
```

**Authentication:** Use API token in `X-RADIUS-SECRET` header (configured in `.env`)

---

### 2. Laravel API Controller

**File:** `app/Http/Controllers/Api/RadiusApiController.php`

#### Endpoint 1: Sync Vouchers

**Purpose:** FreeRADIUS pulls all active/inactive vouchers and their profiles

**Request:** `POST /api/radius/sync/vouchers`

```json
{
    "last_sync": "2026-02-14 10:00:00" // Optional: only get changes since
}
```

**Response:**

```json
{
    "vouchers": [
        {
            "username": "TEST123",
            "password": "TEST123",
            "status": "inactive",
            "rate_limit": "10M/10M",
            "validity": "86400", // seconds
            "shared_users": 1,
            "mac_binding": true,
            "mac_address": null,
            "idle_timeout": "600",
            "router_id": 1
        }
    ],
    "deleted": ["USER001", "USER002"] // Usernames to remove
}
```

#### Endpoint 2: Activate Voucher

**Purpose:** FreeRADIUS reports first successful login with MAC address

**Request:** `POST /api/radius/voucher/activate`

```json
{
    "username": "TEST123",
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "nas_identifier": "mikrotik-router1",
    "activated_at": "2026-02-14 12:30:45"
}
```

**Response:**

```json
{
    "success": true,
    "should_bind_mac": true, // If profile.mac_binding = true
    "expires_at": "2026-02-15 12:30:45"
}
```

**Laravel Logic:**

```php
// Update voucher
$voucher->update([
    'mac_address' => $request->mac_address,
    'activated_at' => $request->activated_at,
    'status' => 'active',
    'expires_at' => $this->calculateExpiryDate($voucher->profile->validity)
]);

// Return MAC binding instruction
return ['should_bind_mac' => $voucher->profile->mac_binding];
```

#### Endpoint 3: Update Accounting

**Purpose:** FreeRADIUS periodically sends usage data

**Request:** `POST /api/radius/voucher/accounting`

```json
{
    "username": "TEST123",
    "bytes_in": 1048576,
    "bytes_out": 2097152,
    "session_time": 3600
}
```

#### Endpoint 4: Get Deleted Vouchers

**Purpose:** FreeRADIUS pulls list of deleted users

**Request:** `GET /api/radius/sync/deleted-vouchers?since=2026-02-14T10:00:00Z`

**Response:**

```json
{
    "deleted_users": ["TEST001", "TEST002"],
    "timestamp": "2026-02-14T12:00:00Z"
}
```

---

### 3. Python Synchronization Service

**File:** `scripts/radius-sync.py`

#### Purpose

Bidirectional sync between Laravel API and FreeRADIUS SQLite database

#### Installation

```bash
pip install requests schedule
```

#### Configuration

**File:** `scripts/radius-sync-config.ini`

```ini
[laravel]
api_url = https://radtik.example.com/api/radius
api_secret = your-shared-secret-token

[radius]
db_path = /etc/freeradius/3.0/sqlite/radius.db

[sync]
interval_seconds = 60
full_sync_hours = 6
```

#### Core Functions

##### 1. Sync Vouchers from Laravel → FreeRADIUS

```python
def sync_vouchers_to_radius():
    # Get vouchers from Laravel API
    response = requests.post(
        f"{LARAVEL_API}/sync/vouchers",
        headers={"X-RADIUS-SECRET": API_SECRET},
        json={"last_sync": last_sync_time}
    )

    vouchers = response.json()['vouchers']
    deleted = response.json()['deleted']

    for voucher in vouchers:
        # Update radcheck (credentials)
        update_radcheck(voucher)

        # Update radreply (attributes based on profile)
        update_radreply(voucher)

    # Remove deleted users
    for username in deleted:
        delete_user_from_radius(username)
```

##### 2. Update radcheck Table

```python
def update_radcheck(voucher):
    conn = sqlite3.connect(RADIUS_DB)
    cursor = conn.cursor()

    # Clear existing entries
    cursor.execute("DELETE FROM radcheck WHERE username = ?", (voucher['username'],))

    # Insert password
    cursor.execute("""
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES (?, 'Cleartext-Password', ':=', ?)
    """, (voucher['username'], voucher['password']))

    # If MAC binding enabled and MAC exists, add check
    if voucher['mac_binding'] and voucher['mac_address']:
        cursor.execute("""
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES (?, 'Calling-Station-Id', '==', ?)
        """, (voucher['username'], voucher['mac_address']))

    conn.commit()
    conn.close()
```

##### 3. Update radreply Table

```python
def update_radreply(voucher):
    conn = sqlite3.connect(RADIUS_DB)
    cursor = conn.cursor()

    # Clear existing entries
    cursor.execute("DELETE FROM radreply WHERE username = ?", (voucher['username'],))

    # Parse rate_limit (e.g., "10M/10M")
    if voucher['rate_limit']:
        upload, download = parse_rate_limit(voucher['rate_limit'])

        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES
                (?, 'WISPr-Bandwidth-Max-Up', ':=', ?),
                (?, 'WISPr-Bandwidth-Max-Down', ':=', ?)
        """, (voucher['username'], upload, voucher['username'], download))

    # Session timeout (validity in seconds)
    if voucher['validity']:
        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES (?, 'Session-Timeout', ':=', ?)
        """, (voucher['username'], voucher['validity']))

    # Idle timeout
    if voucher.get('idle_timeout'):
        cursor.execute("""
            INSERT INTO radreply (username, attribute, op, value)
            VALUES (?, 'Idle-Timeout', ':=', ?)
        """, (voucher['username'], voucher['idle_timeout']))

    # Simultaneous-Use (shared_users)
    cursor.execute("""
        INSERT INTO radreply (username, attribute, op, value)
        VALUES (?, 'Simultaneous-Use', ':=', ?)
    """, (voucher['username'], voucher['shared_users']))

    conn.commit()
    conn.close()
```

##### 4. Monitor radpostauth for First Login

```python
def check_new_activations():
    conn = sqlite3.connect(RADIUS_DB)
    cursor = conn.cursor()

    # Get successful auth records not yet processed
    cursor.execute("""
        SELECT username, calling_station_id, nas_identifier, authdate
        FROM radpostauth
        WHERE reply = 'Access-Accept'
        AND processed = 0  -- Add this column
        ORDER BY authdate ASC
    """)

    for row in cursor.fetchall():
        username, mac, nas, authdate = row

        # Call Laravel API to activate voucher
        response = requests.post(
            f"{LARAVEL_API}/voucher/activate",
            headers={"X-RADIUS-SECRET": API_SECRET},
            json={
                "username": username,
                "mac_address": mac,
                "nas_identifier": nas,
                "activated_at": authdate
            }
        )

        result = response.json()

        # If MAC binding required, update radcheck
        if result['should_bind_mac']:
            bind_mac_to_user(username, mac)

        # Mark as processed
        cursor.execute("""
            UPDATE radpostauth SET processed = 1
            WHERE username = ? AND authdate = ?
        """, (username, authdate))

    conn.commit()
    conn.close()
```

##### 5. Bind MAC Address

```python
def bind_mac_to_user(username, mac_address):
    conn = sqlite3.connect(RADIUS_DB)
    cursor = conn.cursor()

    # Check if MAC check already exists
    cursor.execute("""
        SELECT COUNT(*) FROM radcheck
        WHERE username = ? AND attribute = 'Calling-Station-Id'
    """, (username,))

    if cursor.fetchone()[0] == 0:
        cursor.execute("""
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES (?, 'Calling-Station-Id', '==', ?)
        """, (username, mac_address))

        conn.commit()
        print(f"MAC {mac_address} bound to user {username}")

    conn.close()
```

##### 6. Sync Deleted Users

```python
def sync_deleted_users():
    response = requests.get(
        f"{LARAVEL_API}/sync/deleted-vouchers",
        headers={"X-RADIUS-SECRET": API_SECRET},
        params={"since": last_delete_check}
    )

    deleted_users = response.json()['deleted_users']

    conn = sqlite3.connect(RADIUS_DB)
    cursor = conn.cursor()

    for username in deleted_users:
        cursor.execute("DELETE FROM radcheck WHERE username = ?", (username,))
        cursor.execute("DELETE FROM radreply WHERE username = ?", (username,))
        # Keep radacct and radpostauth for historical records

    conn.commit()
    conn.close()
```

#### Service Scheduler

```python
import schedule
import time

def main():
    # Sync vouchers every 60 seconds
    schedule.every(60).seconds.do(sync_vouchers_to_radius)

    # Check for new activations every 30 seconds
    schedule.every(30).seconds.do(check_new_activations)

    # Sync deleted users every 5 minutes
    schedule.every(5).minutes.do(sync_deleted_users)

    # Full sync every 6 hours
    schedule.every(6).hours.do(lambda: sync_vouchers_to_radius(full_sync=True))

    print("RADTik-FreeRADIUS Sync Service Started")

    while True:
        schedule.run_pending()
        time.sleep(1)

if __name__ == "__main__":
    main()
```

---

### 4. FreeRADIUS Database Modifications

Add `processed` column to `radpostauth` table:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db
```

```sql
-- Add processed flag
ALTER TABLE radpostauth ADD COLUMN processed INTEGER DEFAULT 0;

-- Create index for performance
CREATE INDEX idx_radpostauth_processed ON radpostauth(processed, authdate);
CREATE INDEX idx_radpostauth_username ON radpostauth(username);
```

---

### 5. Systemd Service for Python Sync

**File:** `/etc/systemd/system/radtik-sync.service`

```ini
[Unit]
Description=RADTik FreeRADIUS Synchronization Service
After=network.target freeradius.service
Wants=freeradius.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/radtik-sync
ExecStart=/usr/bin/python3 /opt/radtik-sync/radius-sync.py
Restart=on-failure
RestartSec=10
StandardOutput=append:/var/log/radtik-sync.log
StandardError=append:/var/log/radtik-sync-error.log

[Install]
WantedBy=multi-user.target
```

**Installation:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable radtik-sync
sudo systemctl start radtik-sync
sudo systemctl status radtik-sync
```

---

## Complete Workflow Scenarios

### Scenario 1: Generate Voucher in Laravel

1. **Admin generates voucher via Laravel UI**
    - Voucher created with status `inactive`
    - Username: `VOUCHER001`, Password: `VOUCHER001`
    - Profile: 10Mbps, 24h validity, MAC binding enabled

2. **Python sync service picks it up** (within 60 seconds)
    - Inserts into `radcheck`:
        ```sql
        INSERT INTO radcheck (username, attribute, op, value)
        VALUES ('VOUCHER001', 'Cleartext-Password', ':=', 'VOUCHER001');
        ```
    - Inserts into `radreply`:
        ```sql
        INSERT INTO radreply (username, attribute, op, value) VALUES
        ('VOUCHER001', 'WISPr-Bandwidth-Max-Up', ':=', '10000000'),
        ('VOUCHER001', 'WISPr-Bandwidth-Max-Down', ':=', '10000000'),
        ('VOUCHER001', 'Session-Timeout', ':=', '86400'),
        ('VOUCHER001', 'Simultaneous-Use', ':=', '1');
        ```

3. **FreeRADIUS is now ready** to authenticate the user

---

### Scenario 2: First User Login (MAC Binding)

1. **User connects to WiFi hotspot**
    - Enters credentials: `VOUCHER001` / `VOUCHER001`
    - MAC address: `AA:BB:CC:DD:EE:FF`

2. **FreeRADIUS authenticates**
    - Checks `radcheck` → Password matches
    - Returns attributes from `radreply`
    - Inserts into `radpostauth`:
        ```sql
        INSERT INTO radpostauth (username, pass, reply, authdate, calling_station_id, nas_identifier)
        VALUES ('VOUCHER001', 'VOUCHER001', 'Access-Accept', '2026-02-14 12:30:45', 'AA:BB:CC:DD:EE:FF', 'mikrotik-router1');
        ```

3. **Python sync service detects new auth** (within 30 seconds)
    - Calls Laravel API: `POST /api/radius/voucher/activate`
    - Sends: `username`, `mac_address`, `activated_at`

4. **Laravel updates voucher**

    ```php
    $voucher->update([
        'status' => 'active',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'activated_at' => '2026-02-14 12:30:45',
        'expires_at' => '2026-02-15 12:30:45'  // +24 hours
    ]);
    ```

5. **Laravel responds** with `should_bind_mac: true`

6. **Python sync binds MAC in FreeRADIUS**

    ```sql
    INSERT INTO radcheck (username, attribute, op, value)
    VALUES ('VOUCHER001', 'Calling-Station-Id', '==', 'AA:BB:CC:DD:EE:FF');
    ```

7. **User is now MAC-locked** - only this device can authenticate

---

### Scenario 3: User Deletion in Laravel

1. **Admin deletes voucher in Laravel UI**
    - Voucher soft/hard deleted from database
    - Username: `VOUCHER001`

2. **Python sync service detects deletion** (within 5 minutes)
    - Calls: `GET /api/radius/sync/deleted-vouchers`
    - Laravel returns: `["VOUCHER001"]`

3. **Python removes from FreeRADIUS**

    ```sql
    DELETE FROM radcheck WHERE username = 'VOUCHER001';
    DELETE FROM radreply WHERE username = 'VOUCHER001';
    -- Keep radacct/radpostauth for audit
    ```

4. **User can no longer authenticate**

---

### Scenario 4: Voucher Without MAC Binding

1. **Voucher created with profile:**
    - MAC binding: `false`
    - Shared users: `5`

2. **First login**
    - MAC address stored in Laravel for tracking
    - NO MAC check added to `radcheck`
    - `Simultaneous-Use: 5` in `radreply`

3. **User can connect from any device** (up to 5 simultaneous)

---

## Security Considerations

### 1. API Authentication

```php
// Middleware: app/Http/Middleware/ValidateRadiusApiToken.php
public function handle($request, Closure $next)
{
    $token = $request->header('X-RADIUS-SECRET');

    if ($token !== config('services.radius.api_secret')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    return $next($request);
}
```

### 2. Environment Configuration

```env
# .env
RADIUS_API_SECRET=your-long-random-secret-key-here
RADIUS_SYNC_ENABLED=true
```

### 3. Rate Limiting

```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // Radius API routes
});
```

### 4. IP Whitelisting (Optional)

```php
// Only allow requests from FreeRADIUS server IP
if (!in_array($request->ip(), config('services.radius.allowed_ips'))) {
    abort(403);
}
```

---

## Monitoring & Logging

### Laravel Logs

```php
Log::channel('radius')->info('Voucher activated', [
    'username' => $username,
    'mac_address' => $mac,
    'ip_address' => $request->ip()
]);
```

### Python Logs

```python
import logging

logging.basicConfig(
    filename='/var/log/radtik-sync.log',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

logging.info(f"Synced {count} vouchers to FreeRADIUS")
logging.error(f"Failed to bind MAC for {username}: {error}")
```

### Health Check Endpoint

```php
// routes/api.php
Route::get('/radius/health', function () {
    return [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'vouchers_count' => Voucher::count()
    ];
});
```

---

## Deployment Checklist

### Laravel Setup

- [ ] Create `RadiusApiController.php`
- [ ] Add API routes to `routes/api.php`
- [ ] Create API authentication middleware
- [ ] Add `RADIUS_API_SECRET` to `.env`
- [ ] Test endpoints with Postman/curl

### FreeRADIUS Setup

- [ ] Modify `radpostauth` table schema (add `processed` column)
- [ ] Create indexes for performance
- [ ] Verify SQLite permissions for Python script

### Python Script Setup

- [ ] Install Python dependencies: `requests`, `schedule`
- [ ] Create `/opt/radtik-sync/` directory
- [ ] Copy `radius-sync.py` and `radius-sync-config.ini`
- [ ] Test script manually: `python3 radius-sync.py`
- [ ] Create systemd service
- [ ] Enable and start service

### Testing

- [ ] Generate test voucher in Laravel
- [ ] Wait 60 seconds, verify user in `radcheck`
- [ ] Test authentication with `radtest`
- [ ] Verify MAC binding after first login
- [ ] Delete voucher, confirm removal from FreeRADIUS
- [ ] Check logs for errors

---

## Troubleshooting

### Issue: Sync not working

```bash
# Check service status
sudo systemctl status radtik-sync

# View logs
sudo tail -f /var/log/radtik-sync.log

# Test Laravel API manually
curl -X POST https://radtik.example.com/api/radius/sync/vouchers \
  -H "X-RADIUS-SECRET: your-secret" \
  -H "Content-Type: application/json"
```

### Issue: MAC binding not applied

```bash
# Check radcheck table
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
  "SELECT * FROM radcheck WHERE username='VOUCHER001';"

# Should see Calling-Station-Id check
```

### Issue: User can't authenticate

```bash
# Check FreeRADIUS logs
sudo tail -f /var/log/freeradius/radius.log

# Run FreeRADIUS in debug mode
sudo systemctl stop freeradius
sudo freeradius -X
```

---

## Performance Optimization

### 1. Batch Processing

Sync multiple vouchers in one transaction:

```python
with conn:
    for voucher in vouchers:
        update_radcheck(cursor, voucher)
        update_radreply(cursor, voucher)
```

### 2. Incremental Sync

Only sync changed vouchers:

```php
// Laravel: Track last modification
$vouchers = Voucher::where('updated_at', '>', $lastSync)->get();
```

### 3. Database Indexes

```sql
CREATE INDEX idx_vouchers_updated ON vouchers(updated_at);
CREATE INDEX idx_radcheck_username ON radcheck(username);
CREATE INDEX idx_radreply_username ON radreply(username);
```

---

## Future Enhancements

1. **Real-time WebSocket Sync** (instead of polling)
2. **Data Usage Sync** from radacct back to Laravel
3. **Session Management** - Real-time active sessions display
4. **Expiry Enforcement** - Disable expired users in FreeRADIUS
5. **Multi-Router Support** - Handle multiple FreeRADIUS instances
6. **Redis Queue** - Async job processing for API calls

---

## Conclusion

This integration provides:
✅ Automated voucher synchronization
✅ Dynamic MAC binding based on profile settings
✅ First-login activation tracking
✅ Centralized user management in Laravel
✅ Profile-based attribute assignment
✅ Seamless user deletion

The Python sync service acts as a bridge, ensuring FreeRADIUS remains in sync with Laravel's authoritative user database while maintaining performance through periodic batching and incremental updates.
