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
            'view_dashboard',
            'view_reseller_analytics',
            'view_system_health',

            // Router Management (Assigned Only)
            'view_assigned_routers',
            'view_router_details',
            'edit_assigned_routers',
            'delete_assigned_routers',
            'ping_assigned_routers',
            'view_router_status',
            'view_router_logs',
            'view_router_statistics',
            'manage_router_vouchers',
            'manage_router_profiles',
            'sync_router_data',
            'import_router_configs',

            // Hotspot User Management
            'view_hotspot_users',
            'create_single_user',
            'create_hotspot_users',
            'edit_hotspot_users',
            'delete_hotspot_users',
            'view_active_sessions',
            'view_session_cookies',
            'view_hotspot_logs',
            'disconnect_users',
            'view_disconnected_users',
            'bulk_create_users',

            // Voucher Management
            'view_vouchers',
            'create_vouchers',
            'edit_vouchers',
            'delete_vouchers',
            'generate_vouchers',
            'generate_voucher_batches',
            'print_vouchers',
            'print_single_voucher',
            'export_vouchers',
            'bulk_delete_vouchers',
            'reset_voucher',

            // Profile Management
            'view_profiles',
            'create_profiles',
            'edit_profiles',
            'delete_profiles',
            'assign_profiles',

            // Bandwidth & Monitoring
            'view_live_bandwidth',
            'view_bandwidth_history',
            'view_router_health',
            'view_ping_latency',

            // Commission & Sales
            'view_own_commission',
            'view_voucher_sales',
            'generate_sales_reports',

            // Billing (Limited View)
            'view_own_invoices',
            'view_router_invoices',
            'view_own_balance',
            'request_balance_adjustment',

            // Reports & Analytics
            'generate_user_reports',
            'generate_bandwidth_reports',
            'generate_voucher_reports',
            'view_system_logs',
            'export_reports',

            // User Management (Sub-resellers)
            'view_sub_resellers',
            'create_sub_users',
            'edit_sub_users',
            'assign_router_access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
