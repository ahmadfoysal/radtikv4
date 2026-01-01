<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Free',
                'description' => 'Free plan with 1 router and 100 users - Perfect for testing',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'max_routers' => 1,
                'max_users' => 100,
                'max_zones' => 1,
                'max_vouchers_per_router' => 100,
                'grace_period_days' => 0,
                'early_pay_days' => null,
                'early_pay_discount_percent' => null,
                'auto_renew_allowed' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Starter',
                'description' => 'Perfect for small businesses with 3 routers',
                'price_monthly' => 2000.00,
                'price_yearly' => 20000.00,
                'max_routers' => 3,
                'max_users' => 3000,
                'max_zones' => 3,
                'max_vouchers_per_router' => 1500,
                'grace_period_days' => 3,
                'early_pay_days' => 7,
                'early_pay_discount_percent' => 0,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'description' => 'Growing businesses with 10 routers',
                'price_monthly' => 5000.00,
                'price_yearly' => 45000.00,
                'max_routers' => 10,
                'max_users' => 10000,
                'max_zones' => 10,
                'max_vouchers_per_router' => 5000,
                'grace_period_days' => 5,
                'early_pay_days' => 7,
                'early_pay_discount_percent' => 0,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Professional package with 25 routers',
                'price_monthly' => 20000.00,
                'price_yearly' => 180000.00,
                'max_routers' => 300,
                'max_users' => 300000,
                'max_zones' => 300,
                'max_vouchers_per_router' => 5000,
                'grace_period_days' => 7,
                'early_pay_days' => 7,
                'early_pay_discount_percent' => 0,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ]
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
