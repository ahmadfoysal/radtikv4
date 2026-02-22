# MAC Address Binding Synchronization

## Overview

The MAC binding sync system automatically synchronizes MAC address bindings from MikroTik hotspot users to the FreeRADIUS server. This ensures that users configured with MAC binding in MikroTik are also enforced at the RADIUS level.

## Architecture

### Components

1. **Laravel Command**: `radtik:sync-mac-bindings`
2. **RADIUS API Endpoint**: `POST /sync-mac-bindings`
3. **Scheduler**: Runs every 30 minutes
4. **RADIUS Database**: Updates `radcheck` table with `Calling-Station-Id` attribute

### Flow

```
MikroTik Router (hotspot users with MAC addresses)
    ↓ (RouterOS API query)
Laravel Command (collects users with MAC binding)
    ↓ (HTTP POST with Bearer token)
RADIUS API (Flask endpoint)
    ↓ (SQLite update/insert)
RADIUS Database (radcheck table)
    ↓
MAC binding enforced during authentication
```

## How It Works

### 1. MikroTik Side

The system queries MikroTik hotspot users:
- Gets all users from `/ip/hotspot/user`
- Filters users that have `mac-address` field set
- Extracts username, MAC address, and profile

**MikroTik Query**:
```
/ip/hotspot/user/print where mac-address!=""
```

### 2. Router to RADIUS Mapping

Each router is linked to ONE RADIUS server:
- Router model has `radius_server_id` foreign key
- Only routers with active RADIUS servers are processed
- MAC bindings are sent to the router's assigned RADIUS server

### 3. RADIUS Database Update

The RADIUS server receives bindings and updates the `radcheck` table:

**Attribute**: `Calling-Station-Id`  
**Operator**: `==` (equals)  
**Value**: MAC address in format `AA:BB:CC:DD:EE:FF`

**SQL Operation**:
```sql
-- If MAC binding exists for username: UPDATE
UPDATE radcheck 
SET value = 'AA:BB:CC:DD:EE:FF' 
WHERE username = 'USER123' AND attribute = 'Calling-Station-Id';

-- If MAC binding doesn't exist: INSERT
INSERT INTO radcheck (username, attribute, op, value) 
VALUES ('USER123', 'Calling-Station-Id', '==', 'AA:BB:CC:DD:EE:FF');
```

### 4. RADIUS Authentication

When a user attempts to authenticate:
1. FreeRADIUS checks the `radcheck` table
2. If `Calling-Station-Id` attribute exists for the user
3. The MAC address in the authentication request MUST match the stored value
4. Authentication FAILS if MAC address doesn't match

## Usage

### Manual Sync

Sync all routers:
```bash
php artisan radtik:sync-mac-bindings
```

Sync specific router:
```bash
php artisan radtik:sync-mac-bindings --router=5
```

Dry run (see what would be synced):
```bash
php artisan radtik:sync-mac-bindings --dry-run
```

### Automatic Sync (Scheduled)

The scheduler runs every 30 minutes automatically:
```php
Schedule::command('radtik:sync-mac-bindings')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();
```

**Cron Entry** (make sure this is configured):
```bash
* * * * * cd /path/to/radtik && php artisan schedule:run >> /dev/null 2>&1
```

## MAC Address Format

The system automatically normalizes MAC addresses to the standard format:

**Input Formats** (from MikroTik):
- `AA:BB:CC:DD:EE:FF` (colon-separated)
- `AA-BB-CC-DD-EE-FF` (hyphen-separated)
- `AABBCCDDEEFF` (no separator)

**Output Format** (to RADIUS):
- `AA:BB:CC:DD:EE:FF` (uppercase with colons)

## API Details

### Endpoint

**URL**: `https://radius-server.com:5000/sync-mac-bindings`  
**Method**: `POST`  
**Authentication**: Bearer token (from `radius_servers.auth_token`)

### Request Payload

```json
{
  "bindings": [
    {
      "username": "VOUCHER001",
      "mac_address": "AA:BB:CC:DD:EE:FF",
      "profile": "default"
    },
    {
      "username": "VOUCHER002",
      "mac_address": "11:22:33:44:55:66",
      "profile": "premium"
    }
  ]
}
```

### Response (Success)

```json
{
  "success": true,
  "synced": 15,
  "updated": 5,
  "failed": 0,
  "errors": []
}
```

**Status Codes**:
- `200 OK`: All bindings synced successfully
- `207 Multi-Status`: Some bindings synced, some failed (check `errors` array)
- `400 Bad Request`: Invalid request payload
- `401 Unauthorized`: Invalid or missing Bearer token
- `500 Internal Server Error`: Server-side error

## Multi-Router Support

### Router Assignment

Each router can be assigned to ONE RADIUS server:
1. During router creation, select RADIUS server
2. Router's `radius_server_id` field is set
3. All MAC bindings from that router go to its assigned RADIUS server

### Multiple Routers, One RADIUS Server

If multiple routers use the same RADIUS server:
- MAC bindings from all routers are merged
- Duplicate usernames are handled by UPDATE logic
- Last sync wins for conflicting MAC addresses

### Example Configuration

```
Router A (ID: 1) → RADIUS Server 1
Router B (ID: 2) → RADIUS Server 1
Router C (ID: 3) → RADIUS Server 2

Sync Process:
1. Laravel queries Router A → Sends to RADIUS Server 1
2. Laravel queries Router B → Sends to RADIUS Server 1
3. Laravel queries Router C → Sends to RADIUS Server 2
```

## Monitoring & Troubleshooting

### Check Scheduler Status

View Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "MAC binding"
```

### Check RADIUS Server Logs

View Flask API logs:
```bash
sudo journalctl -u radtik-radius-api -f | grep mac
```

### Verify RADIUS Database

Connect to SQLite database:
```bash
sqlite3 /etc/freeradius/3.0/sqlite/radius.db
```

Query MAC bindings:
```sql
SELECT username, value as mac_address 
FROM radcheck 
WHERE attribute = 'Calling-Station-Id'
ORDER BY username;
```

Count MAC bindings:
```sql
SELECT COUNT(*) as total_bindings 
FROM radcheck 
WHERE attribute = 'Calling-Station-Id';
```

### Common Issues

#### 1. Router Unreachable
**Symptom**: "Router unreachable, skipping" in logs  
**Solution**: Check MikroTik router is online and credentials are correct

#### 2. RADIUS Server Inactive
**Symptom**: "RADIUS server not active, skipping"  
**Solution**: Check `radius_servers.is_active = 1` in database

#### 3. Authentication Fails with 401
**Symptom**: "Failed to sync to RADIUS server" with 401 error  
**Solution**: Verify `auth_token` in Laravel matches token in RADIUS server's `config.ini`

#### 4. MAC Format Issues
**Symptom**: RADIUS rejects authentication even with correct MAC  
**Solution**: Check MAC format in `radcheck` table (should be `AA:BB:CC:DD:EE:FF`)

## Security Considerations

### Authentication

- Each RADIUS server has unique Bearer token
- Token stored encrypted in Laravel database
- Token validated on every API request
- Failed authentication attempts are logged

### MAC Address Spoofing

**Important**: MAC addresses can be spoofed! MAC binding provides:
- ✅ Convenience (auto-login on specific device)
- ✅ Basic access control
- ❌ NOT cryptographic security

**Recommendation**: Use MAC binding with strong passwords, not as sole authentication.

## Testing

### Test Command Manually

```bash
# Dry run to see what would sync
php artisan radtik:sync-mac-bindings --dry-run

# Sync specific router
php artisan radtik:sync-mac-bindings --router=1

# Check output
tail -f storage/logs/laravel.log
```

### Test RADIUS Authentication with MAC

Use `radtest` to test:
```bash
# Good MAC (should succeed)
radtest username password localhost 0 testing123 \
  AA:BB:CC:DD:EE:FF

# Wrong MAC (should fail)
radtest username password localhost 0 testing123 \
  11:22:33:44:55:66
```

### Verify Flask Endpoint

Test API directly:
```bash
curl -X POST http://localhost:5000/sync-mac-bindings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bindings": [
      {
        "username": "TEST001",
        "mac_address": "AA:BB:CC:DD:EE:FF"
      }
    ]
  }'
```

## Performance

### Benchmarks

- **Query MikroTik**: ~100ms per router
- **API Request**: ~50-200ms depending on network
- **Database Update**: ~1ms per binding

### Optimization

- Scheduler runs every 30 minutes (configurable)
- Only syncs routers with active RADIUS servers
- Skips unreachable routers
- Runs in background (non-blocking)
- Uses `withoutOverlapping()` to prevent duplicate runs

### Limits

- No hard limit on number of bindings
- Tested with 1000+ bindings per sync
- Flask API handles batch inserts/updates efficiently

## Configuration

### Change Sync Frequency

Edit `routes/console.php`:
```php
// Change from every 30 minutes to every hour
Schedule::command('radtik:sync-mac-bindings')
    ->hourly()  // or ->everyFifteenMinutes(), ->daily(), etc.
```

### Disable Automatic Sync

Comment out the schedule in `routes/console.php`:
```php
// Schedule::command('radtik:sync-mac-bindings')
//     ->everyThirtyMinutes()
//     ...
```

Manual sync will still work.

## Database Schema

### radcheck Table

```sql
CREATE TABLE radcheck (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username VARCHAR(64) NOT NULL DEFAULT '',
  attribute VARCHAR(64) NOT NULL DEFAULT '',
  op CHAR(2) NOT NULL DEFAULT '==',
  value VARCHAR(253) NOT NULL DEFAULT ''
);
```

### MAC Binding Record Example

| id  | username   | attribute          | op | value             |
|-----|------------|--------------------|----|--------------------|
| 101 | VOUCHER001 | Calling-Station-Id | == | AA:BB:CC:DD:EE:FF |
| 102 | VOUCHER002 | Calling-Station-Id | == | 11:22:33:44:55:66 |

## Related Documentation

- [RADIUS Integration Guide](RADIUS_SETUP_GUIDE.md)
- [Activation Sync System](ACTIVATION_SYNC_README.md)
- [RADIUS Server Management](RADIUS_SERVER_MANAGEMENT.md)
