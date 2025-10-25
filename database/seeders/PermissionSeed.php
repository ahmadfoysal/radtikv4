<?php

namespace Database\Seeders;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


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

        //create permissions
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage routers']);
        Permission::create(['name' => 'manage zones']);
    }
}
