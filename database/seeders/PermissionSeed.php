<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'reseller']);

        // create permissions
        $permissions = [

            // Router Management
            'add_router',
            'edit_router',
            'delete_router',
            'view_router',
            'ping_router',
            'install_scripts',
            'import_router_configs',
            'view_router_logs',

            // Hotspot User Management
            'view_hotspot_users',
            'create_single_user',
            'edit_hotspot_users',
            'delete_hotspot_users',
            'view_active_sessions',
            'view_session_cookies',
            'view_hotspot_logs',
            'disconnect_users',

            // Voucher Management
            'view_vouchers',
            'edit_vouchers',
            'delete_vouchers',
            'generate_vouchers',
            'print_vouchers',
            'print_single_voucher',
            'bulk_delete_vouchers',
            'reset_voucher',

            // Bandwidth & Monitoring
            'view_live_bandwidth',
            'view_router_health',

            //Report
            'view_reports',
            'view_voucher_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
