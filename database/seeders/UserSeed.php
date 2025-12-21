<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Superadmin
        $superadmin = User::create([
            'name' => 'System Administrator',
            'email' => 'superadmin@radtik.local',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_phone_verified' => true,
            'email_verified_at' => now(),
            'balance' => 50000,
            'commission' => 0,
            'country' => 'Bangladesh',
            'address' => 'Gulshan, Dhaka-1212',
            'phone' => '+8801711000001',
            'last_login_at' => now(),
        ]);
        $superadmin->assignRole('superadmin');

        // Create Admin
        $admin = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@radtik.local',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_phone_verified' => true,
            'email_verified_at' => now(),
            'balance' => 25000,
            'commission' => 10,
            'country' => 'Bangladesh',
            'address' => 'Banani, Dhaka-1213',
            'phone' => '+8801711000002',
            'last_login_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create Reseller
        $reseller = User::create([
            'name' => 'Reseller Partner',
            'email' => 'reseller@radtik.local',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_phone_verified' => true,
            'email_verified_at' => now(),
            'balance' => 5000,
            'commission' => 0,
            'admin_id' => $admin->id,
            'country' => 'Bangladesh',
            'address' => 'Uttara, Dhaka-1230',
            'phone' => '+8801711000003',
            'last_login_at' => now(),
        ]);
        $reseller->assignRole('reseller');

        // Assign permissions to reseller
        $reseller->givePermissionTo([
            'view_router',
            'view_vouchers',
            'generate_vouchers',
            'print_vouchers',
            'view_hotspot_users',
            'create_single_user',
            'view_active_sessions',
            'view_voucher_logs',
        ]);
    }
}
