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
        Role::firstOrCreate(['name' => 'superadmin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'reseller']);

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
            'sync_router_data',
            'view_router_logs',
            'view_sales_summary',

            // Hotspot User Management
            'view_hotspot_users',
            'create_single_user',
            'edit_hotspot_users',
            'delete_hotspot_users',
            'view_active_sessions',
            'delete_active_session',
            'view_session_cookies',
            'delete_session_cookie',

            // Voucher Management
            'view_vouchers',
            'view_voucher_list',
            'edit_vouchers',
            'delete_vouchers',
            'generate_vouchers',
            'print_vouchers',
            'print_single_voucher',
            'bulk_delete_vouchers',
            'reset_voucher',

            // Subscription Management
            'view_subscription',

            // Reports
            'view_reports',
            'view_voucher_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
