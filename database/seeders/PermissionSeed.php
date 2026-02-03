<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeed extends Seeder
{
    /**
     * Run the database seeds.
     * Creates roles, permissions, and a superadmin user for production.
     */
    public function run(): void
    {
        $this->command->info('👥 Creating roles...');

        // Create roles
        Role::firstOrCreate(['name' => 'superadmin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'reseller']);

        $this->command->info('🔐 Creating permissions...');

        // Create permissions
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

        $this->command->info('👤 Creating superadmin user...');

        // Create superadmin user
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'phone' => '+1234567890',
                'address' => 'Head Office',
                'country' => 'Bangladesh',
                'balance' => 0,
                'commission' => 0,
                'is_active' => true,
                'is_phone_verified' => true,
                'email_verified_at' => now(),
                'last_login_at' => now(),
            ]
        );

        $superadmin->syncRoles(['superadmin']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'phone' => '+1234567891',
                'address' => 'Head Office',
                'country' => 'Bangladesh',
                'balance' => 0,
                'commission' => 0,
                'is_active' => true,
                'is_phone_verified' => true,
                'email_verified_at' => now(),
                'last_login_at' => now(),
            ]
        );


        $admin->syncRoles(['admin']);

        //assign all permissions to admin
        $admin->givePermissionTo(Permission::all());

        $this->command->newLine();
        $this->command->info('✅ Production essentials created successfully!');
        $this->command->warn('📧 Superadmin Email: superadmin@example.com');
        $this->command->warn('🔑 Superadmin Password: password');
        $this->command->warn('⚠️  Please change the password after first login!');
    }
}
