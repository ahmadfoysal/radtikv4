# RADIUS Server Management - Implementation Guide

## Overview

The RADIUS server management system allows admins to automatically provision and manage RADIUS servers on Linode with automated FreeRADIUS installation via SSH.

## Architecture

### Components

1. **Migration**: `database/migrations/2026_02_03_052345_create_radius_servers_table.php`
2. **Model**: `app/Models/RadiusServer.php`
3. **Service**: `app/Services/LinodeService.php`
4. **Job**: `app/Jobs/ProvisionRadiusServer.php`
5. **Livewire Components**:
   - Index: `app/Livewire/Radius/Index.php`
   - Create: `app/Livewire/Radius/Create.php`
   - Edit: `app/Livewire/Radius/Edit.php`

### Database Schema

```sql
- id
- name                    -- Friendly server name
- host                    -- IP/hostname (populated after Linode creation)
- auth_port              -- RADIUS auth port (default: 1812)
- acct_port              -- RADIUS accounting port (default: 1813)
- secret                 -- RADIUS shared secret (encrypted)
- timeout                -- Connection timeout
- retries                -- Connection retry attempts
- is_active              -- Server active status
- description            -- Optional notes

-- SSH Configuration
- ssh_port               -- SSH port (default: 22)
- ssh_username           -- SSH username (default: root)
- ssh_password           -- SSH password (encrypted)
- ssh_private_key        -- SSH private key (encrypted)

-- Linode Integration
- linode_node_id         -- Linode instance ID
- linode_region          -- Linode datacenter region
- linode_plan            -- Instance size/plan
- linode_image           -- OS image
- linode_label           -- Label in Linode dashboard
- linode_ipv4            -- IPv4 address
- linode_ipv6            -- IPv6 address

-- Installation Status
- installation_status    -- pending|creating|installing|completed|failed|error
- installation_log       -- Installation progress logs
- installed_at           -- Timestamp when completed
- auto_provision         -- Auto-create on Linode flag
```

## Setup Instructions

### 1. Environment Configuration

Add to your `.env` file:

```env
# Linode API Configuration
LINODE_API_TOKEN=your_linode_api_token_here
```

Get your Linode API token from: https://cloud.linode.com/profile/tokens

### 2. Install Required Dependencies

```bash
# Install phpseclib for SSH connections
composer require phpseclib/phpseclib:~3.0
```

### 3. Run Migration

```bash
php artisan migrate
```

### 4. Queue Configuration

The provisioning process runs in the background via Laravel queues. Configure your queue:

**Option 1: Database Queue (Recommended for development)**
```env
QUEUE_CONNECTION=database
```

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

**Option 2: Redis Queue (Recommended for production)**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

```bash
php artisan queue:work redis
```

### 5. Supervisor Configuration (Production)

Create `/etc/supervisor/conf.d/radtik-queue.conf`:

```ini
[program:radtik-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/radtik/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/radtik/storage/logs/queue.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start radtik-queue:*
```

## Usage

### Creating a RADIUS Server

#### Auto-Provisioned (Recommended)

1. Navigate to: Admin Settings > RADIUS Servers > Add Server
2. Fill in:
   - **Server Name**: Friendly identifier
   - **Shared Secret**: RADIUS secret (min 8 chars)
   - **Auto-Provision**: ✓ Enabled
3. Configure Linode:
   - **Region**: Select datacenter location
   - **Plan**: Choose instance size (Nanode 1GB recommended)
   - **OS Image**: Ubuntu 22.04 LTS (recommended)
4. Configure SSH:
   - **SSH Username**: root (default)
   - **SSH Port**: 22 (default)
   - Password will be auto-generated
5. Click "Create Server"

**What happens next:**
1. Server record created with status: `pending`
2. Background job dispatched
3. Job creates Linode instance (status: `creating`)
4. Waits for instance to boot
5. Connects via SSH (status: `installing`)
6. Installs FreeRADIUS with configuration
7. Configures firewall rules
8. Updates status to `completed`

#### Manual Configuration

1. Uncheck "Auto-Provision"
2. Provide existing server details:
   - Host/IP address
   - SSH credentials
3. Server status: `completed` immediately

### Installation Status

- **Pending**: Waiting to start provisioning
- **Creating**: Creating Linode instance
- **Installing**: Installing FreeRADIUS
- **Completed**: Ready to use
- **Failed**: Installation failed (check logs)

### Viewing Installation Logs

In the Edit page, the installation log shows:
- Linode creation details
- IP address assignment
- SSH connection status
- FreeRADIUS installation output
- Any errors encountered

## Linode Configuration Details

### Available Regions

- `us-east` - US East (Newark)
- `us-west` - US West (Fremont)
- `us-central` - US Central (Dallas)
- `us-southeast` - US Southeast (Atlanta)
- `eu-west` - EU West (London)
- `eu-central` - EU Central (Frankfurt)
- `ap-south` - Asia Pacific (Singapore)
- `ap-northeast` - Asia Pacific (Tokyo)

### Available Plans

- `g6-nanode-1` - Nanode 1GB (1 vCPU, 1GB RAM) - $5/month
- `g6-standard-1` - Linode 2GB (1 vCPU, 2GB RAM) - $10/month
- `g6-standard-2` - Linode 4GB (2 vCPU, 4GB RAM) - $20/month
- `g6-standard-4` - Linode 8GB (4 vCPU, 8GB RAM) - $40/month

### OS Images

- `linode/ubuntu22.04` - Ubuntu 22.04 LTS (recommended)
- `linode/ubuntu20.04` - Ubuntu 20.04 LTS
- `linode/debian11` - Debian 11
- `linode/debian12` - Debian 12

## FreeRADIUS Configuration

The automated installation:

1. Updates system packages
2. Installs FreeRADIUS and utilities
3. Configures `/etc/freeradius/3.0/clients.conf` with:
   - Shared secret from server settings
   - Allow connections from any IP (0.0.0.0/0)
   - NAS type: other (MikroTik compatible)
4. Configures custom ports if specified
5. Configures firewall (UFW):
   - Opens auth port (default: 1812/udp)
   - Opens accounting port (default: 1813/udp)
   - Opens SSH port (22/tcp)
6. Enables and starts FreeRADIUS service
7. Validates configuration

## MikroTik Integration

To use the RADIUS server with MikroTik:

1. In MikroTik > RADIUS:
   - Add RADIUS server
   - Address: `<linode_ipv4>`
   - Secret: `<your_shared_secret>`
   - Authentication Port: `1812` (or custom)
   - Accounting Port: `1813` (or custom)
   - Service: `hotspot`

2. In MikroTik > IP > Hotspot > Server Profiles:
   - Enable RADIUS authentication
   - Enable RADIUS accounting
   - Select your RADIUS server

## API Reference

### LinodeService Methods

#### `provisionServer(RadiusServer $server): void`
Provisions a complete RADIUS server on Linode with FreeRADIUS installation.

#### `deleteLinodeInstance(RadiusServer $server): bool`
Deletes the Linode instance associated with a server.

#### `getLinodeDetails(int $linodeId): ?array`
Retrieves current status and details of a Linode instance.

#### `restartLinodeInstance(RadiusServer $server): bool`
Reboots the Linode instance.

#### `testConnection(RadiusServer $server): bool`
Tests SSH connectivity to the server.

## Troubleshooting

### Job Not Running

**Problem**: Server stuck in "pending" status

**Solution**:
```bash
# Check queue is running
php artisan queue:work

# Check queue jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### SSH Connection Failed

**Problem**: Installation fails with SSH error

**Causes**:
- Server not finished booting (wait longer)
- Incorrect SSH credentials
- Firewall blocking connection
- Wrong port

**Check logs**: View installation_log in Edit page

### FreeRADIUS Installation Failed

**Problem**: Status shows "failed" after installation

**Solution**:
1. Check installation_log for specific error
2. SSH to server manually: `ssh root@<ip>`
3. Check FreeRADIUS status: `systemctl status freeradius`
4. Check FreeRADIUS logs: `tail -f /var/log/freeradius/radius.log`

### Linode API Errors

**Problem**: Provisioning fails immediately

**Causes**:
- Invalid API token
- Insufficient Linode credits
- Region unavailable
- Plan not available

**Check**: Verify `.env` has correct `LINODE_API_TOKEN`

## Security Considerations

1. **Encrypted Secrets**: All passwords and keys are encrypted using Laravel's `Crypt` facade
2. **SSH Security**: 
   - Use SSH keys instead of passwords when possible
   - Change default SSH port from 22
   - Disable root login after initial setup
3. **Firewall**: UFW automatically configured to only allow necessary ports
4. **RADIUS Secret**: Use strong secrets (min 16 characters, complex)
5. **API Token**: Keep Linode API token secure, never commit to version control

## Cost Estimation

### Linode Costs (Monthly)

- Nanode 1GB: $5/month (sufficient for most use cases)
- Linode 2GB: $10/month (medium traffic)
- Linode 4GB: $20/month (high traffic)

### Recommendations

- **Small deployment** (< 100 users): Nanode 1GB
- **Medium deployment** (100-500 users): Linode 2GB
- **Large deployment** (500+ users): Linode 4GB+

## Future Enhancements

- [ ] Support for multiple cloud providers (AWS, DigitalOcean, Vultr)
- [ ] RADIUS user management via web interface
- [ ] Real-time monitoring and alerts
- [ ] Automatic scaling based on load
- [ ] Backup and disaster recovery
- [ ] RADIUS proxy configuration
- [ ] Integration with external authentication (LDAP, AD)

## Support

For issues or questions:
1. Check installation logs in Edit page
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check queue logs: `storage/logs/queue.log`
4. Enable debug mode temporarily in `.env`: `APP_DEBUG=true`

## License

Part of RADTik v4 MikroTik Management System
