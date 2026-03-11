# MAC Binding Implementation

## Overview

This feature automatically binds vouchers to the first device (MAC address) that uses them, preventing the voucher from being used on other devices. This is controlled by the `mac_binding` boolean flag in the user profile.

## How It Works

### 1. Profile Configuration

When creating a user profile in Laravel, set `mac_binding = true` to enable MAC binding for vouchers using that profile.

**Database:** `user_profiles` table, `mac_binding` column (boolean)

### 2. First Authentication Flow

```
User connects to WiFi
    ↓
FreeRADIUS authenticates (checks radcheck table)
    ↓
Logs authentication to radpostauth table
    - username
    - calling_station_id (MAC address)
    - nas_identifier (router ID)
    - reply: 'Access-Accept'
    ↓
activation-sync.py (cron every 5 minutes)
    ↓
Fetches new authentications from radpostauth
    ↓
POSTs to Laravel: /api/radius/activations
    ↓
Laravel processes activations:
    - Sets activated_at timestamp
    - Sets expires_at based on profile validity
    - Stores MAC address
    - Returns mac_bindings array for profiles with mac_binding=true
    ↓
activation-sync.py receives response with mac_bindings
    ↓
Directly inserts into radcheck table (same database connection):
    - username
    - attribute: 'Calling-Station-Id'
    - op: '=='
    - value: MAC address
    ↓
User is now MAC-locked
```

### 3. Subsequent Authentications

When the same user tries to authenticate:
- FreeRADIUS checks `radcheck` table
- If MAC address matches → **Access-Accept** ✅
- If MAC address differs → **Access-Reject** ❌

## Database Changes

### Laravel (vouchers table)
```sql
UPDATE vouchers 
SET 
    activated_at = '2026-03-11 10:30:00',
    expires_at = '2026-03-12 10:30:00',
    mac_address = 'AA:BB:CC:DD:EE:FF',
    status = 'active'
WHERE username = 'VOUCHER001';
```

### FreeRADIUS (radcheck table)
```sql
-- Initial password check (created during sync)
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('VOUCHER001', 'Cleartext-Password', ':=', 'VOUCHER001');

-- NAS binding (created during sync)
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('VOUCHER001', 'NAS-Identifier', '==', 'mikrotik-router1');

-- MAC binding (added after first authentication) ← NEW
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('VOUCHER001', 'Calling-Station-Id', '==', 'AA:BB:CC:DD:EE:FF');
```

## API Response Format

### Activation Response (Laravel → Python)

**Endpoint:** `POST /api/radius/activations`

**Request:**
```json
{
    "activations": [
        {
            "username": "VOUCHER001",
            "nas_identifier": "mikrotik-router1",
            "calling_station_id": "AA:BB:CC:DD:EE:FF",
            "authenticated_at": "2026-03-11 10:30:00"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Activations processed",
    "received": 1,
    "processed_at": "2026-03-11T10:30:15Z",
    "mac_bindings": [
        {
            "username": "VOUCHER001",
            "mac_address": "AA:BB:CC:DD:EE:FF",
            "nas_identifier": "mikrotik-router1"
        }
    ]
}
```

**Note:** `mac_bindings` array is only included if:
1. Profile has `mac_binding = true`
2. This is the first activation (`activated_at` was null)
3. MAC address is provided

### Python Script - Direct Database Insert

After receiving the Laravel response with `mac_bindings`, the Python script directly inserts into the RADIUS database:

```python
# activation-sync.py function: sync_mac_bindings_to_radius()
conn = sqlite3.connect(RADIUS_DB_PATH)
cursor = conn.cursor()

for binding in mac_bindings:
    cursor.execute(
        "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, ?, ?, ?)",
        (binding['username'], 'Calling-Station-Id', '==', binding['mac_address'])
    )

conn.commit()
```

**Why Direct Database Access?**
- ✅ More efficient (no extra HTTP call)
- ✅ Simpler (one less network hop)
- ✅ More reliable (no Flask API dependency)
- ✅ Faster (direct SQLite access)

The script already has database access to read from `radpostauth`, so it reuses the same connection pattern to write to `radcheck`.

**Note:** The Flask API endpoint `/sync-mac-bindings` still exists for other use cases (like syncing from MikroTik systems), but is not used in the activation flow.

## Code Components

### 1. Laravel Controller
**File:** `app/Http/Controllers/Api/RadiusActivationController.php`

**Method:** `processActivationsAndGetBindings()`
- Processes each activation
- Checks if voucher profile has `mac_binding = true`
- Returns array of MAC bindings for first-time activations

### 2. Python Activation Sync
**File:** `radtik-radius/scripts/activation-sync.py`

**Functions:**
- `post_activations_to_laravel()` - POSTs activations, receives MAC bindings in response
- `sync_mac_bindings_to_radius()` - Directly inserts MAC bindings into radcheck table via SQLite

**Flow:**
```python
# Get activations from radpostauth
activations = fetch_unique_activations_last_24h()

# Send to Laravel and get MAC bindings
result = post_activations_to_laravel(activations)

# If MAC bindings returned, insert directly into database
if result.get('mac_bindings'):
    sync_mac_bindings_to_radius(result['mac_bindings'])
```

### 3. FreeRADIUS Database
**Table:** `radcheck`

The MAC binding entry prevents authentication from different devices:
```sql
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('VOUCHER001', 'Calling-Station-Id', '==', 'AA:BB:CC:DD:EE:FF');
```

When a user tries to authenticate, FreeRADIUS checks if their MAC matches this value.

## Testing

### 1. Create Profile with MAC Binding
```php
UserProfile::create([
    'name' => 'Test Profile',
    'rate_limit' => '5M/5M',
    'validity' => '1d',
    'mac_binding' => true, // Enable MAC binding
]);
```

### 2. Generate Voucher
Create a voucher using this profile in Laravel admin panel.

### 3. First Login
- Connect to WiFi hotspot
- Enter voucher credentials
- Check Laravel vouchers table: `mac_address` should be populated
- Check RADIUS radcheck table: Should have `Calling-Station-Id` entry

### 4. Verify MAC Lock
- Try connecting from a different device with same credentials
- Authentication should fail

### 5. Check Logs

**Laravel logs:**
```bash
tail -f storage/logs/laravel.log | grep "MAC binding"
```

**Python activation sync logs:**
```bash
tail -f /var/log/radtik-activation-sync.log
```

**Flask API logs:**
```bash
# Check systemd journal if running as service
journalctl -u radtik-radius-api -f
```

## SQL Verification Queries

### Check voucher activation
```sql
-- Laravel database
SELECT username, mac_address, activated_at, expires_at, status
FROM vouchers
WHERE username = 'VOUCHER001';
```

### Check RADIUS MAC binding
```sql
-- FreeRADIUS database
SELECT username, attribute, op, value
FROM radcheck
WHERE username = 'VOUCHER001'
AND attribute = 'Calling-Station-Id';
```

### Check authentication logs
```sql
-- FreeRADIUS database
SELECT username, calling_station_id, reply, authdate
FROM radpostauth
WHERE username = 'VOUCHER001'
ORDER BY authdate DESC
LIMIT 10;
```

## Troubleshooting

### MAC binding not working

1. **Check profile setting:**
   ```sql
   SELECT name, mac_binding FROM user_profiles WHERE id = ?;
   ```

2. **Check if activation was processed:**
   ```sql
   SELECT username, mac_address, activated_at FROM vouchers WHERE username = ?;
   ```

3. **Check if MAC binding exists in RADIUS:**
   ```sql
   SELECT * FROM radcheck 
   WHERE username = ? AND attribute = 'Calling-Station-Id';
   ```

4. **Check activation sync logs:**
   ```bash
   grep "MAC binding" /var/log/radtik-activation-sync.log
   ```

5. **Verify database access:**
   ```bash
   # Check if activation-sync.py can access database
   ls -la /var/lib/freeradius/radius.db
   
   # Test database connection
   sqlite3 /var/lib/freeradius/radius.db "SELECT COUNT(*) FROM radcheck;"
   ```

### MAC address format

FreeRADIUS may send MAC addresses in different formats:
- `AA:BB:CC:DD:EE:FF` (colon-separated)
- `AA-BB-CC-DD-EE-FF` (hyphen-separated)
- `AABBCCDDEEFF` (no separator)

The system stores MAC addresses as received. FreeRADIUS performs exact matching.

## Security Considerations

1. **MAC spoofing:** Note that MAC addresses can be spoofed. This feature prevents casual sharing but not determined attackers.

2. **Customer support:** Have a process to reset MAC bindings if customers legitimately change devices.

3. **Logging:** All MAC binding operations are logged for audit purposes.

## Related Documentation

- [MAC_BINDING_SYNC_QUICKREF.md](MAC_BINDING_SYNC_QUICKREF.md) - Quick reference
- [MAC_BINDING_SYNC.md](MAC_BINDING_SYNC.md) - Detailed sync documentation
- [ACTIVATION_SYNC_README.md](../radtik-radius/ACTIVATION_SYNC_README.md) - Activation sync system
- [FREERADIUS_LARAVEL_INTEGRATION_PLAN.md](FREERADIUS_LARAVEL_INTEGRATION_PLAN.md) - Integration architecture
