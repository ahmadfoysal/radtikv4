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
                'name' => 'Starter Bronze',
                'price_monthly' => 19.99,
                'price_yearly' => 215.88,
                'user_limit' => 50,
                'billing_cycle' => 'monthly',
                'early_pay_days' => 5,
                'early_pay_discount_percent' => 5,
                'auto_renew_allowed' => true,
                'description' => 'Entry level package suitable for small hotspots or pilot deployments.',
                'is_active' => true,
            ],
            [
                'name' => 'Growth Silver',
                'price_monthly' => 39.50,
                'price_yearly' => 430.00,
                'user_limit' => 150,
                'billing_cycle' => 'monthly',
                'early_pay_days' => 10,
                'early_pay_discount_percent' => 8,
                'auto_renew_allowed' => true,
                'description' => 'Balanced option for resellers expanding to multiple neighborhoods.',
                'is_active' => true,
            ],
            [
                'name' => 'Business Gold',
                'price_monthly' => 59.90,
                'price_yearly' => 650.00,
                'user_limit' => 300,
                'billing_cycle' => 'yearly',
                'early_pay_days' => 15,
                'early_pay_discount_percent' => 10,
                'auto_renew_allowed' => true,
                'description' => 'Annual package with better rates for operators with stable customer bases.',
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Platinum',
                'price_monthly' => 129.00,
                'price_yearly' => 1350.00,
                'user_limit' => 750,
                'billing_cycle' => 'yearly',
                'early_pay_days' => 20,
                'early_pay_discount_percent' => 12,
                'auto_renew_allowed' => true,
                'description' => 'High-capacity plan that unlocks larger pools and premium automation.',
                'is_active' => true,
            ],
            [
                'name' => 'Unlimited Titanium',
                'price_monthly' => 199.00,
                'price_yearly' => 2150.00,
                'user_limit' => 2000,
                'billing_cycle' => 'monthly',
                'early_pay_days' => null,
                'early_pay_discount_percent' => null,
                'auto_renew_allowed' => false,
                'description' => 'For bespoke deployments that prefer manual renewals and no user cap.',
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
