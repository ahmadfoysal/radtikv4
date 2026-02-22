# MAC Binding Sync - Quick Reference

## Quick Start

### Run Manual Sync
```bash
# Sync all routers
php artisan radtik:sync-mac-bindings

# Sync specific router
php artisan radtik:sync-mac-bindings --router=5

# Preview without syncing
php artisan radtik:sync-mac-bindings --dry-run
```

### Automatic Sync
✅ **Already configured** - Runs every 30 minutes via Laravel scheduler

## How It Works

1. **Collects** MAC bindings from MikroTik hotspot users (users with `mac-address` set)
2. **Groups** by RADIUS server (each router → one RADIUS server)
3. **Sends** to RADIUS API: `POST /sync-mac-bindings`
4. **Updates** radcheck table with `Calling-Station-Id` attribute

## Key Points

✅ Each router links to ONE RADIUS server via `radius_server_id`  
✅ Only processes routers with active RADIUS servers  
✅ MAC format auto-normalized to `AA:BB:CC:DD:EE:FF`  
✅ Updates existing bindings, inserts new ones  
✅ Runs in background without overlapping  
✅ Logs all sync activities

## Requirements

- Router must have `radius_server_id` set
- RADIUS server must be active (`is_active = 1`)
- Router must be reachable
- Flask API must be running on RADIUS server

## RADIUS Database

### Check MAC Bindings
```sql
sqlite3 /etc/freeradius/3.0/sqlite/radius.db

SELECT username, value as mac 
FROM radcheck 
WHERE attribute = 'Calling-Station-Id'
LIMIT 10;
```

### Count MAC Bindings
```sql
SELECT COUNT(*) FROM radcheck 
WHERE attribute = 'Calling-Station-Id';
```

## Testing

### Test RADIUS Auth with MAC
```bash
# Should succeed (correct MAC)
radtest username password localhost 0 testing123 AA:BB:CC:DD:EE:FF

# Should fail (wrong MAC)
radtest username password localhost 0 testing123 11:22:33:44:55:66
```

### Test API Endpoint
```bash
curl -X POST http://localhost:5000/sync-mac-bindings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"bindings":[{"username":"TEST","mac_address":"AA:BB:CC:DD:EE:FF"}]}'
```

## Monitoring

### Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep "MAC binding"
```

### RADIUS API Logs
```bash
sudo journalctl -u radtik-radius-api -f | grep mac
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Router unreachable" | Check MikroTik is online, verify credentials |
| "RADIUS server not active" | Check `is_active = 1` in radius_servers table |
| "Failed to sync" (401) | Verify auth_token matches in Laravel & RADIUS |
| "No MAC bindings found" | Users must have `mac-address` field set in MikroTik |

## Configuration

### Schedule Frequency
Edit `routes/console.php`:
```php
Schedule::command('radtik:sync-mac-bindings')
    ->hourly()  // Change frequency here
```

### Disable Auto-Sync
Comment out in `routes/console.php`:
```php
// Schedule::command('radtik:sync-mac-bindings')...
```

## API Endpoint Details

**URL**: `https://radius-server:5000/sync-mac-bindings`  
**Method**: POST  
**Auth**: Bearer token from `radius_servers.auth_token`  
**Payload**:
```json
{
  "bindings": [
    {"username": "USER1", "mac_address": "AA:BB:CC:DD:EE:FF"}
  ]
}
```

**Response**:
```json
{
  "success": true,
  "synced": 10,
  "updated": 5,
  "failed": 0,
  "errors": []
}
```

## Files

| File | Purpose |
|------|---------|
| `app/Console/Commands/SyncMacBindings.php` | Laravel command |
| `radtik-radius/scripts/sync-vouchers.py` | Flask API with `/sync-mac-bindings` endpoint |
| `routes/console.php` | Scheduler configuration |
| `docs/MAC_BINDING_SYNC.md` | Full documentation |

## Security Note

⚠️ **MAC addresses can be spoofed!** Use MAC binding for convenience, not as sole security. Always require strong passwords.
