<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin =   User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_phone_verified' => true,
            'subscription' => null,
            'balance' => 0,
            'country' => 'USA',
            'address' => '123 Admin St, Admin City, Admin State, 12345',
            'phone' => '+1234567890',
        ]);

        //assingn role
        $superadmin->assignRole('superadmin');

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_phone_verified' => true,
            'subscription' => null,
            'balance' => 0,
            'country' => 'USA',
            'address' => '456 Admin Rd, Admin Town, Admin State, 67890',
            'phone' => '+1987654321',
        ]);

        //assign role
        $admin->assignRole('admin');

        $reseller = User::create([
            'name' => 'Reseller User',
            'email' => 'relesser@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_phone_verified' => true,
            'subscription' => null,
            'balance' => 0,
            'admin_id' => $admin->id,
            'country' => 'USA',
            'address' => '789 Reseller Ave, Reseller City, Reseller State',
            'phone' => '+1123456789',
        ]);

        //assign role
        $reseller->assignRole('reseller');
    }
}
