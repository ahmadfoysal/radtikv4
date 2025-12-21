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
                'price_monthly' => 500.00,
                'price_yearly' => 5500.00,
                'max_routers' => 3,
                'max_users' => 100,
                'max_zones' => 3,
                'max_vouchers_per_router' => 500,
                'grace_period_days' => 3,
                'early_pay_days' => 7,
                'early_pay_discount_percent' => 5,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'description' => 'Growing businesses with 10 routers',
                'price_monthly' => 1500.00,
                'price_yearly' => 16000.00,
                'max_routers' => 10,
                'max_users' => 200,
                'max_zones' => 10,
                'max_vouchers_per_router' => 1000,
                'grace_period_days' => 5,
                'early_pay_days' => 10,
                'early_pay_discount_percent' => 8,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'description' => 'Professional package with 25 routers',
                'price_monthly' => 3500.00,
                'price_yearly' => 38000.00,
                'max_routers' => 25,
                'max_users' => 300,
                'max_zones' => 25,
                'max_vouchers_per_router' => 2000,
                'grace_period_days' => 7,
                'early_pay_days' => 15,
                'early_pay_discount_percent' => 10,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Large scale operations with 50 routers',
                'price_monthly' => 6500.00,
                'price_yearly' => 70000.00,
                'max_routers' => 50,
                'max_users' => 500,
                'max_zones' => 50,
                'max_vouchers_per_router' => null,
                'grace_period_days' => 10,
                'early_pay_days' => 30,
                'early_pay_discount_percent' => 12,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
