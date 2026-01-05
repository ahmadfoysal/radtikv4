# Permissions List

This document lists all permissions declared in the codebase using `$this->authorize()` method.

## Router Management

| Permission | Description | Used In |
|------------|-------------|---------|
| `add_router` | Add new routers | `Router\Create.php` |
| `edit_router` | Edit existing routers | `Router\Edit.php` |
| `delete_router` | Delete routers | `Router\Show.php` |
| `view_router` | View router details | `Router\Index.php`, `Router\Show.php` |
| `ping_router` | Ping routers to check connectivity | `Router\Index.php` |
| `install_scripts` | Install scripts on routers | `Router\Index.php` |
| `import_router_configs` | Import router configurations | `Router\Import.php` |
| `sync_router_data` | Sync data from routers | `Router\Show.php` |
| `view_router_logs` | View router logs | (Declared in seeder, not yet used) |

## Hotspot User Management

| Permission | Description | Used In |
|------------|-------------|---------|
| `create_single_user` | Create single hotspot user | `HotspotUsers\Create.php` |
| `view_active_sessions` | View active hotspot sessions | `HotspotUsers\ActiveSessions.php` |
| `delete_active_session` | Delete/remove active sessions | `HotspotUsers\ActiveSessions.php` |
| `view_session_cookies` | View session cookies | `HotspotUsers\SessionCookies.php` |
| `delete_session_cookie` | Delete session cookies | `HotspotUsers\SessionCookies.php` |
| `view_hotspot_logs` | View hotspot logs | `HotspotUsers\Logs.php` |
| `view_hotspot_users` | View hotspot users | (Declared in seeder, not yet used) |
| `edit_hotspot_users` | Edit hotspot users | (Declared in seeder, not yet used) |
| `delete_hotspot_users` | Delete hotspot users | (Declared in seeder, not yet used) |
| `disconnect_users` | Disconnect hotspot users | (Declared in seeder, not yet used) |

## Voucher Management

| Permission | Description | Used In |
|------------|-------------|---------|
| `view_vouchers` | View voucher list | `Voucher\Index.php` |
| `view_voucher_list` | View voucher list (bulk manager) | `Voucher\BulkManager.php` |
| `edit_vouchers` | Edit vouchers | `Voucher\Edit.php`, `Voucher\Index.php` |
| `delete_vouchers` | Delete vouchers | `Voucher\Index.php` |
| `generate_vouchers` | Generate new vouchers | `Voucher\Generate.php` |
| `print_vouchers` | Print vouchers (bulk) | `Voucher\BulkManager.php` |
| `bulk_delete_vouchers` | Bulk delete vouchers | `Voucher\BulkManager.php` |
| `print_single_voucher` | Print single voucher | (Declared in seeder, not yet used) |
| `reset_voucher` | Reset voucher | (Declared in seeder, not yet used) |

## Dashboard & Reports

| Permission | Description | Used In |
|------------|-------------|---------|
| `view_dashboard` | View dashboard | `Dashboard.php` |
| `view_reports` | View reports | (Declared in seeder, not yet used) |
| `view_voucher_logs` | View voucher logs | (Declared in seeder, not yet used) |

## Bandwidth & Monitoring

| Permission | Description | Used In |
|------------|-------------|---------|
| `view_live_bandwidth` | View live bandwidth | (Declared in seeder, not yet used) |
| `view_router_health` | View router health | (Declared in seeder, not yet used) |

## Ticket System

The ticket system uses policy-based authorization (Laravel Policies) rather than string permissions:
- `view` - View tickets (handled by TicketPolicy)
- `update` - Update tickets (handled by TicketPolicy)
- `delete` - Delete tickets (handled by TicketPolicy)

These are checked using `$this->authorize('view', $ticket)` format in `Tickets\Show.php`.

## Summary

### Total Permissions Declared: 40

### Permissions Currently Used in Code: 22
- Dashboard: 1
- Router Management: 8
- Hotspot User Management: 6
- Voucher Management: 7

### Permissions Declared but Not Yet Used: 18
- `view_router_logs`
- `view_hotspot_users`
- `edit_hotspot_users`
- `delete_hotspot_users`
- `disconnect_users`
- `print_single_voucher`
- `reset_voucher`
- `view_reports`
- `view_voucher_logs`
- `view_live_bandwidth`
- `view_router_health`

## Notes

- All permissions are managed through Spatie Laravel Permission package
- Permissions are seeded via `database/seeders/PermissionSeed.php`
- Roles: `superadmin`, `admin`, `reseller`
- Ticket permissions use Laravel Policies instead of string-based permissions

