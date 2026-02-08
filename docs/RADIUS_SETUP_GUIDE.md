# FreeRADIUS + SQLite Setup Guide for RadTik

**Production-Ready Configuration for Ubuntu 22.04**

This guide provides a complete, tested setup for FreeRADIUS with SQLite backend, optimized for RadTik voucher authentication with zero lock issues.

---

## 📋 Table of Contents

1. [Requirements](#requirements)
2. [Quick Setup (Automated Script)](#quick-setup)
3. [Manual Setup Steps](#manual-setup)
4. [Testing & Verification](#testing--verification)
5. [Integration with RadTik](#integration-with-radtik)
6. [Troubleshooting](#troubleshooting)

---

## Requirements

Before starting, ensure you have:

- **Ubuntu 22.04 LTS** (clean installation recommended)
- **Root or sudo access**
- **Internet connection** for package downloads

---

## Quick Setup

### One-Command Installation

Download and run the automated setup script:

```bash
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius-setup/main/radius-setup.sh | sudo bash
```

Or download first, review, then execute:

```bash
wget https://raw.githubusercontent.com/ahmadfoysal/radtik-radius-setup/main/radius-setup.sh
chmod +x radius-setup.sh
sudo ./radius-setup.sh
```

The script will:
- ✅ Install FreeRADIUS and dependencies
- ✅ Create and configure SQLite database
- ✅ Set proper permissions
- ✅ Enable WAL mode for concurrency
- ✅ Configure RadTik client access
- ✅ Create test user
- ✅ Verify installation

**Setup completes in ~2 minutes.**

---

## Manual Setup

If you prefer manual installation or need to customize:

### Step 1: Install FreeRADIUS

Update system and install required packages:

```bash
sudo apt update
sudo apt install -y freeradius freeradius-utils sqlite3
```

Verify installation:

```bash
freeradius -v
```

Expected output: `FreeRADIUS Version 3.0.x`

---

### Step 2: Create SQLite Database

Create database directory:

```bash
sudo mkdir -p /etc/freeradius/3.0/sqlite
```

Initialize database file:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db ".quit"
```

Import FreeRADIUS schema:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db < /etc/freeradius/3.0/mods-config/sql/main/sqlite/schema.sql
```

---

### Step 3: Configure Database Permissions (CRITICAL)

**This step prevents "database locked" errors:**

```bash
sudo chown -R freerad:freerad /etc/freeradius/3.0/sqlite
sudo chmod 775 /etc/freeradius/3.0/sqlite
sudo chmod 664 /etc/freeradius/3.0/sqlite/radius.db
```

Verify ownership:

```bash
ls -la /etc/freeradius/3.0/sqlite/
```

Output should show `freerad freerad` as owner.

---

### Step 4: Enable SQLite WAL Mode

Write-Ahead Logging prevents lock contention:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "PRAGMA journal_mode=WAL;"
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "PRAGMA busy_timeout=30000;"
```

Verify WAL mode:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "PRAGMA journal_mode;"
```

Expected output: `wal`

---

### Step 5: Configure SQL Module

Edit SQL module configuration:

```bash
sudo nano /etc/freeradius/3.0/mods-available/sql
```

Find and set these values:

```conf
driver = "rlm_sql_sqlite"
dialect = "sqlite"

sqlite {
    filename = "/etc/freeradius/3.0/sqlite/radius.db"
    busy_timeout = 30000
}
```

Save and exit (`Ctrl+X`, `Y`, `Enter`).

---

### Step 6: Enable SQL Module

Create symbolic link:

```bash
sudo ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
```

---

### Step 7: Configure Client Access

Edit client configuration:

```bash
sudo nano /etc/freeradius/3.0/clients.conf
```

Add RadTik client (append at end of file):

```conf
client radtik {
    ipaddr = 0.0.0.0/0
    secret = ChangeThisSecretInProduction123
    require_message_authenticator = no
    nastype = other
}
```

**⚠️ Security Note:** Change `secret` to a strong random password in production!

---

### Step 8: Optimize SQLite Configuration (Optional)

Edit default site to disable unnecessary logging:

```bash
sudo nano /etc/freeradius/3.0/sites-enabled/default
```

Comment out `sql` in these sections to reduce writes:

```conf
post-auth {
    # sql  # ← Comment this line
}

Post-Auth-Type REJECT {
    # sql  # ← Comment this line
}
```

---

### Step 9: Configure Firewall

Allow RADIUS ports:

```bash
sudo ufw allow 1812/udp comment "RADIUS Authentication"
sudo ufw allow 1813/udp comment "RADIUS Accounting"
```

---

### Step 10: Restart FreeRADIUS

Apply all changes:

```bash
sudo systemctl restart freeradius
sudo systemctl enable freeradius
```

Check service status:

```bash
sudo systemctl status freeradius
```

Expected: `active (running)` in green.

---

## Testing & Verification

### Create Test User

Add a test account to database:

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
"INSERT INTO radcheck (username, attribute, op, value) VALUES ('testuser','Cleartext-Password',':=','testpass');"
```

---

### Test Authentication

Test from localhost:

```bash
radtest testuser testpass 127.0.0.1 0 ChangeThisSecretInProduction123
```

**Expected successful output:**

```
Sent Access-Request Id 123 from 0.0.0.0:12345 to 127.0.0.1:1812 length 73
Received Access-Accept Id 123 from 127.0.0.1:1812 to 127.0.0.1:12345 length 20
```

Test from remote server:

```bash
radtest testuser testpass <RADIUS_SERVER_IP> 0 ChangeThisSecretInProduction123
```

---

### Debug Mode

If authentication fails, run FreeRADIUS in debug mode:

```bash
sudo systemctl stop freeradius
sudo freeradius -X
```

Press `Ctrl+C` to stop, then restart service:

```bash
sudo systemctl start freeradius
```

---

## Integration with RadTik

### Database Structure

RadTik uses these tables:

**radcheck** - User credentials
```sql
username | attribute          | op | value
---------|-------------------|----|---------
user001  | Cleartext-Password | := | pass123
```

**radreply** - User attributes (optional)
```sql
username | attribute       | op | value
---------|----------------|----|---------
user001  | Session-Timeout | := | 3600
```

---

### Python Sync Script

Your Python script should:

1. Query RadTik main database for active vouchers
2. Insert/update users in RADIUS `radcheck` table
3. Remove expired/deleted vouchers
4. Run every 1-5 minutes via cron

**Sample sync query:**

```python
# Get active vouchers from RadTik
active_vouchers = db.query("""
    SELECT username, password 
    FROM vouchers 
    WHERE status = 'active' 
    AND (expires_at IS NULL OR expires_at > NOW())
""")

# Sync to RADIUS
for voucher in active_vouchers:
    radius_db.execute("""
        INSERT OR REPLACE INTO radcheck 
        (username, attribute, op, value)
        VALUES (?, 'Cleartext-Password', ':=', ?)
    """, (voucher.username, voucher.password))
```

---

### Capturing First Login

To track activation time, use one of these methods:

**Option A: API Callback (Recommended)**
- Configure FreeRADIUS post-auth exec module
- Call RadTik API on successful auth
- Update `activated_at` timestamp

**Option B: Read radpostauth Table**
- Enable SQL logging in post-auth section
- Python script reads and processes new logins
- Updates RadTik main database

---

## Troubleshooting

### "database is locked" Error

**Cause:** Incorrect permissions or WAL mode not enabled

**Solution:**
```bash
sudo chown -R freerad:freerad /etc/freeradius/3.0/sqlite
sudo chmod 775 /etc/freeradius/3.0/sqlite
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "PRAGMA journal_mode=WAL;"
sudo systemctl restart freeradius
```

---

### "Access-Reject" Response

**Cause:** Wrong password or user not in database

**Solution:**
```bash
# Check if user exists
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
"SELECT * FROM radcheck WHERE username='testuser';"

# Verify secret matches
grep "secret" /etc/freeradius/3.0/clients.conf
```

---

### Service Won't Start

**Check logs:**
```bash
sudo journalctl -u freeradius -n 50 --no-pager
```

**Common issues:**
- Syntax error in config files
- SQL module not properly enabled
- Database file permissions

---

### High CPU Usage

**Cause:** Too many SQL writes (post-auth logging)

**Solution:** Disable radpostauth logging (see Step 8)

---

## Security Best Practices

1. **Change default secret** in `/etc/freeradius/3.0/clients.conf`
2. **Restrict client IP** - Replace `0.0.0.0/0` with specific IPs
3. **Enable firewall** - Only allow RADIUS ports from trusted sources
4. **Use SSL/TLS** for API callbacks
5. **Regular backups** of SQLite database
6. **Monitor logs** for suspicious activity

---

## Performance Tuning

For high-traffic deployments:

### Increase Connection Pool

Edit `/etc/freeradius/3.0/mods-available/sql`:

```conf
pool {
    start = 5
    min = 4
    max = 20
    spare = 3
}
```

### Consider MySQL/PostgreSQL

SQLite is suitable for:
- ✅ < 1000 concurrent users
- ✅ < 100 auth requests/second

For larger deployments, migrate to MySQL or PostgreSQL.

---

## Maintenance

### Backup Database

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db ".backup /backup/radius-$(date +%Y%m%d).db"
```

### Cleanup Old Logs

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "DELETE FROM radpostauth WHERE authdate < datetime('now', '-30 days');"
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "VACUUM;"
```

### View Database Size

```bash
du -h /etc/freeradius/3.0/sqlite/radius.db*
```

---

## Additional Resources

- [FreeRADIUS Documentation](https://freeradius.org/documentation/)
- [SQLite WAL Mode](https://www.sqlite.org/wal.html)
- [RadTik Documentation](https://docs.radtik.com)

---

## Support

If you encounter issues:

1. Check [Troubleshooting](#troubleshooting) section
2. Run debug mode: `sudo freeradius -X`
3. Review logs: `sudo journalctl -u freeradius`
4. Open issue on [GitHub](https://github.com/ahmadfoysal/radtik-radius-setup/issues)

---

**Setup Complete!** 🎉

Your FreeRADIUS server is now ready for RadTik authentication.

## Useful Commands

Restart service:

```bash
sudo systemctl restart freeradius
```

Open database:

```bash
sqlite3 /etc/freeradius/3.0/sqlite/radius.db
```

View users:

```sql
SELECT * FROM radcheck;
```

---

End of guide.
