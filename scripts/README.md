# RADIUS Setup Scripts

This directory contains automated setup scripts for FreeRADIUS with SQLite backend.

## Quick Start

### One-Command Installation

```bash
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius-setup/main/radius-setup.sh | sudo bash
```

### Download and Run

```bash
# Download the script
wget https://raw.githubusercontent.com/ahmadfoysal/radtik-radius-setup/main/radius-setup.sh

# Make it executable
chmod +x radius-setup.sh

# Run with sudo
sudo ./radius-setup.sh
```

### From Local Copy

If you have the RadTik repository cloned:

```bash
cd radtikv4/scripts
sudo bash radius-setup.sh
```

## What the Script Does

The automated script performs the following steps:

1. ✅ **System Check** - Verifies OS and root access
2. ✅ **Install Packages** - FreeRADIUS, SQLite3, utilities
3. ✅ **Create Database** - Initialize SQLite database file
4. ✅ **Import Schema** - Load FreeRADIUS tables
5. ✅ **Fix Permissions** - Set proper ownership (freerad:freerad)
6. ✅ **Enable WAL Mode** - Prevent database lock issues
7. ✅ **Configure SQL** - Set driver and database path
8. ✅ **Enable Module** - Activate SQL module
9. ✅ **Configure Client** - Add RadTik client with random secret
10. ✅ **Optimize Settings** - Disable unnecessary logging
11. ✅ **Configure Firewall** - Open RADIUS ports
12. ✅ **Start Service** - Enable and start FreeRADIUS
13. ✅ **Create Test User** - Add testuser account
14. ✅ **Run Tests** - Verify authentication works

**Total time:** ~2-3 minutes

## Output Example

```
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║       FreeRADIUS + SQLite Setup Completed Successfully!           ║
║                                                                   ║
╚═══════════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 Configuration Details
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Database Location:      /etc/freeradius/3.0/sqlite/radius.db
  Client Name:            radtik
  Client IP Range:        0.0.0.0/0
  Shared Secret:          xyz123abc456...

  Test Username:          testuser
  Test Password:          testpass
```

## Requirements

- **OS:** Ubuntu 22.04 LTS
- **Access:** Root or sudo privileges
- **Network:** Internet connection for packages

## Configuration

### Change Default Values

Edit the script variables at the top:

```bash
# Default client configuration
DEFAULT_SECRET="your-custom-secret"    # Or leave blank for auto-generation
CLIENT_NAME="radtik"
CLIENT_IPADDR="0.0.0.0/0"             # Or restrict to specific IP

# Test user credentials  
TEST_USERNAME="testuser"
TEST_PASSWORD="testpass"
```

### Security Hardening

After installation, for production use:

1. **Change the shared secret** (saved in output)
2. **Restrict client IP** in `/etc/freeradius/3.0/clients.conf`
3. **Remove test user** from database
4. **Enable SSL/TLS** for API callbacks

## Troubleshooting

### Script Fails at Permissions Step

```bash
# Manually fix permissions
sudo chown -R freerad:freerad /etc/freeradius/3.0/sqlite
sudo chmod 775 /etc/freeradius/3.0/sqlite
sudo systemctl restart freeradius
```

### Service Won't Start

```bash
# Check logs
sudo journalctl -u freeradius -n 50

# Run in debug mode
sudo systemctl stop freeradius
sudo freeradius -X
```

### Authentication Test Fails

```bash
# Verify test user exists
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
"SELECT * FROM radcheck WHERE username='testuser';"

# Check secret in clients.conf
sudo grep -A 5 "client radtik" /etc/freeradius/3.0/clients.conf
```

## Manual Setup

If you prefer manual installation, see the complete guide:

📖 [RADIUS_SETUP_GUIDE.md](../docs/RADIUS_SETUP_GUIDE.md)

## Post-Installation

### Next Steps

1. **Save the generated secret** from script output
2. **Add RADIUS server** in RadTik admin panel
3. **Configure Python sync** to update SQLite database
4. **Test from MikroTik** router with RadTik vouchers

### Verify Installation

```bash
# Check service
sudo systemctl status freeradius

# Test authentication
radtest testuser testpass 127.0.0.1 0 <YOUR_SECRET>

# View database
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db "SELECT * FROM radcheck;"
```

## Backup & Restore

### Backup Database

```bash
sudo sqlite3 /etc/freeradius/3.0/sqlite/radius.db \
".backup /backup/radius-$(date +%Y%m%d).db"
```

### Restore Database

```bash
sudo systemctl stop freeradius
sudo cp /backup/radius-20260207.db /etc/freeradius/3.0/sqlite/radius.db
sudo chown freerad:freerad /etc/freeradius/3.0/sqlite/radius.db
sudo systemctl start freeradius
```

## Uninstall

To completely remove FreeRADIUS:

```bash
# Stop service
sudo systemctl stop freeradius
sudo systemctl disable freeradius

# Remove packages
sudo apt purge freeradius freeradius-utils

# Remove configuration and data
sudo rm -rf /etc/freeradius
```

## Support

- 📖 **Documentation:** [docs/RADIUS_SETUP_GUIDE.md](../docs/RADIUS_SETUP_GUIDE.md)
- 🐛 **Issues:** [GitHub Issues](https://github.com/ahmadfoysal/radtik-radius-setup/issues)
- 💬 **Community:** [RadTik Forum](https://community.radtik.com)

## License

MIT License - See [LICENSE](../LICENSE) file for details.

---

**RadTik Team** | Making RADIUS Setup Simple
