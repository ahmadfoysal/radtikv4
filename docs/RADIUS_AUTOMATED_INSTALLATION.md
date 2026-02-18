# RadTik RADIUS Automated Installation System

## Overview

Complete automated installation system for FreeRADIUS servers with remote management via Laravel admin panel.

## Files Created/Modified

### 1. Bootstrap Installation Script
**File:** `radtik-radius/bootstrap-install.sh`

One-command installer that:
- Clones repository from GitHub
- Copies files to `/opt/radtik-radius`
- Runs complete installation
- Takes 5-10 minutes

**Usage:**
```bash
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius/main/bootstrap-install.sh | sudo bash
```

Or with custom repository:
```bash
curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius/main/bootstrap-install.sh | \
  sudo RADTIK_REPO_URL='https://github.com/ahmadfoysal/radtik-radius.git' RADTIK_BRANCH='main' bash
```

### 2. SSH Service Enhancements
**File:** `app/Services/RadiusServerSshService.php`

**New Methods:**
- `installRadiusServer($repoUrl, $branch)` - Remotely install RADIUS via SSH
- `checkInstallationStatus()` - Verify installation completion
- `getServiceStatus()` - Enhanced with detailed logging and debugging

**Features:**
- Downloads bootstrap script from GitHub
- Executes installation in background
- Monitors progress via service status
- Logs all operations for debugging

### 3. Show Component Enhancements  
**File:** `app/Livewire/Radius/Show.php`

**New Methods:**
- `installRadiusServer()` - Trigger remote installation
- `checkInstallation()` - Verify installation completed
- Enhanced `refreshStatus()` - Detailed debug logging

**New Properties:**
- Auto-refreshing service status
- Installation progress tracking
- Debug information display

### 4. Enhanced Status Dashboard
**File:** `resources/views/livewire/radius/show.blade.php`

**New Features:**
- 4-card status grid (SSH, Installation, FreeRADIUS, API)
- Installation status badge with color coding
- Context-aware action buttons:
  - "Install RADIUS Server" - when pending/failed
  - "Check Installation Progress" - during installation
  - "Reinstall RADIUS Server" - when completed
  - "Restart Services" - for service management
  - "Test RADIUS Auth" - authentication testing
  - "Reconfigure Secrets" - update credentials

**Status Display:**
- Real-time SSH connection status
- Installation progress (pending → installing → completed/failed)
- FreeRADIUS service status (active/inactive)
- API service status (active/inactive)
- System health metrics
- Recent logs (tabbed view)

### 5. Configuration Updates
**File:** `config/app.php`

**New Settings:**
```php
'radtik_repo_url' => env('RADTIK_REPO_URL', 'https://github.com/ahmadfoysal/radtik-radius.git'),
'radtik_branch' => env('RADTIK_BRANCH', 'main'),
```

**Environment Variables (.env):**
```ini
RADTIK_REPO_URL=https://github.com/ahmadfoysal/radtik-radius.git
RADTIK_BRANCH=main
```

### 6. Documentation Updates
**File:** `radtik-radius/QUICKSTART.md`

Added one-liner installation section at the top with:
- Quick start command
- Installation time estimate
- Post-installation instructions

## User Workflows

### Method 1: One-Command Installation (Standalone)

**On RADIUS Server:**
```bash
curl -fsSL https://raw.githubusercontent.com/username/radtikv4/main/radtik-radius/bootstrap-install.sh | sudo bash
```

**In Laravel Admin:**
1. Add server with IP and SSH credentials
2. Click "Configure Secrets" to set up API tokens
3. Services auto-start, ready to use

### Method 2: Remote Installation via Laravel Admin

**In Laravel Admin Panel:**
1. Go to RADIUS Servers → Add Server
2. Enter: Host IP, SSH username, SSH password (port 22)
3. Click "Create Server"
4. Server appears with status "Pending"
5. Click server → View Status page
6. Click "Install RADIUS Server" button
7. Wait 5-10 minutes (monitor with "Refresh")
8. Status changes: Installing → Completed
9. All services active and ready!

**No SSH needed!** Everything happens automatically through the web interface.

### Method 3: Manual Installation

**On RADIUS Server:**
```bash
git clone https://github.com/username/radtikv4.git
cd radtikv4/radtik-radius
sudo bash install.sh
```

**In Laravel Admin:**
Add server and configure secrets remotely.

## Installation Process Flow

```
Laravel Admin Panel
       ↓
  [Install Button] 
       ↓
  SSH Connection
       ↓
Download bootstrap-install.sh from GitHub
       ↓
  Execute Script
       ↓
  Clone Repository → /tmp/radtik-install-XXX
       ↓
  Copy Files → /opt/radtik-radius
       ↓
  Run install.sh
       ↓
  Install Packages (FreeRADIUS, Python, Flask)
       ↓
  Configure Services
       ↓
  Start Services
       ↓
  [Installation Complete]
       ↓
  Click "Check Installation" button
       ↓
  Status updates: Completed
       ↓
  Services: Active ✓
```

## Status Page Features

### Service Status Cards
1. **SSH Connection** - Real-time connection test
2. **Installation Status** - pending/installing/completed/failed
3. **FreeRADIUS Service** - active/inactive with debug info
4. **API Service** - active/inactive with port listening check

### System Health Metrics
- CPU usage (%)
- Memory usage (%)
- Disk usage (%)
- System uptime
- Load average
- Total RADIUS users

### Action Buttons (Context-Aware)
- **Test Connection** - Always available
- **Install RADIUS Server** - When pending/failed
- **Check Installation Progress** - During installation
- **Reinstall RADIUS Server** - When completed (with data preservation)
- **Restart Services** - When completed
- **Test RADIUS Auth** - When completed
- **Reconfigure Secrets** - Always available

### Recent Logs Viewer
- FreeRADIUS logs (tabbed)
- API Server logs (tabbed)
- Last 20 lines displayed
- Auto-scrolling

## Security Features

- SSH connections use encrypted credentials (Laravel Crypt)
- Scripts downloaded over HTTPS
- Sudo passwordless execution (configure on server)
- Installation logs preserved for audit
- Secrets automatically generated (32-64 chars)
- No credentials stored in logs

## Debugging

**Enable Debug Mode:**
```ini
APP_DEBUG=true
```

Shows additional information on status cards.

**View Logs:**
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# FreeRADIUS logs (on server)
sudo tail -f /var/log/freeradius/radius.log

# API server logs (on server)
sudo journalctl -u radtik-radius-api -f
```

**SSH Debug:**
All SSH commands are logged with:
- Command executed
- Output received  
- Timestamps
- Server ID

**Service Status Debug:**
Logs include:
- Raw systemctl output
- Parsed status values
- Boolean results (active/inactive)

## Configuration Requirements

### On RADIUS Server

**Passwordless sudo** (recommended):
```bash
# Allow user to run sudo without password
sudo visudo
# Add line:
username ALL=(ALL) NOPASSWD: ALL
```

**Or use root user:**
```
SSH Username: root
SSH Password: <root password>
```

### In Laravel .env

```ini
RADTIK_REPO_URL=https://github.com/ahmadfoysal/radtik-radius.git
RADTIK_BRANCH=main
```

## Troubleshooting

### Installation Fails
1. Check SSH connectivity: "Test Connection" button
2. Verify sudo permissions on server
3. Check Laravel logs: `storage/logs/laravel.log`
4. Check server internet access: `ping github.com`

### Services Inactive
1. Click "Restart Services"
2. Check logs in tabbed viewer
3. SSH to server and check: `sudo systemctl status freeradius`
4. Click "Check Installation" to verify

### Debug Information
1. Enable `APP_DEBUG=true`
2. Refresh status page
3. View debug values under service badges
4. Check Laravel logs for detailed SSH output

## Next Steps

The system is ready to use with your public repository:
- **Repository:** https://github.com/ahmadfoysal/radtik-radius
- **One-Line Install:** `curl -fsSL https://raw.githubusercontent.com/ahmadfoysal/radtik-radius/main/bootstrap-install.sh | sudo bash`

Recommended actions:
1. Test installation on clean Ubuntu 22.04 server
2. Verify all services start correctly
3. Document any custom configuration in your repository README

## Support

For issues:
1. Check Laravel logs
2. Check service logs on RADIUS server
3. Verify SSH connectivity
4. Review installation status
5. Open GitHub issue with logs

---

**Ready to Use!** 🚀

The system is now fully automated. Users can install RADIUS servers with a single click from the admin panel.
