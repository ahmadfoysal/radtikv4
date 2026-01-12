<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates subscription packages for the system.
     */
    public function run(): void
    {
        $this->command->info('📦 Creating subscription packages...');

        // $packages = [
        //     [
        //         'name' => 'Free',
        //         'description' => 'Perfect for getting started with basic WiFi hotspot management. Ideal for small cafes or testing the platform.',
        //         'price_monthly' => 0,
        //         'price_yearly' => 0,
        //         'max_routers' => 1,
        //         'max_users' => 100,
        //         'max_zones' => 1,
        //         'max_vouchers_per_router' => 100,
        //         'grace_period_days' => 0,
        //         'early_pay_days' => null,
        //         'early_pay_discount_percent' => null,
        //         'auto_renew_allowed' => false,
        //         'features' => [
        //             'Basic voucher generation',
        //             'Single router management',
        //             'Up to 100 users',
        //             'Standard support',
        //         ],
        //         'is_featured' => false,
        //         'is_active' => true,
        //     ],
        //     [
        //         'name' => 'Starter',
        //         'description' => 'Great for small businesses managing multiple locations. Get started with professional hotspot services.',
        //         'price_monthly' => 500,
        //         'price_yearly' => 5000,
        //         'max_routers' => 3,
        //         'max_users' => 300,
        //         'max_zones' => 3,
        //         'max_vouchers_per_router' => 1000,
        //         'grace_period_days' => 3,
        //         'early_pay_days' => 7,
        //         'early_pay_discount_percent' => 0,
        //         'auto_renew_allowed' => true,
        //         'features' => [
        //             'Up to 3 routers',
        //             '300 users per router',
        //             'Zone management',
        //             'Voucher templates',
        //             'Email support',
        //         ],
        //         'is_featured' => false,
        //         'is_active' => true,
        //     ],
        //     [
        //         'name' => 'Business',
        //         'description' => 'Most popular choice for growing businesses. Advanced features and priority support included.',
        //         'price_monthly' => 1500,
        //         'price_yearly' => 15000,
        //         'max_routers' => 10,
        //         'max_users' => 1000,
        //         'max_zones' => 10,
        //         'max_vouchers_per_router' => 2000,
        //         'grace_period_days' => 5,
        //         'early_pay_days' => 10,
        //         'early_pay_discount_percent' => 10,
        //         'auto_renew_allowed' => true,
        //         'features' => [
        //             'Up to 10 routers',
        //             '1000 users per router',
        //             'Multiple zones',
        //             'Custom voucher templates',
        //             'Advanced reporting',
        //             'Priority email support',
        //             'API access',
        //         ],
        //         'is_featured' => true,
        //         'is_active' => true,
        //     ],
        //     [
        //         'name' => 'Professional',
        //         'description' => 'For serious businesses requiring advanced management tools and dedicated support.',
        //         'price_monthly' => 3000,
        //         'price_yearly' => 30000,
        //         'max_routers' => 25,
        //         'max_users' => 2000,
        //         'max_zones' => 25,
        //         'max_vouchers_per_router' => 5000,
        //         'grace_period_days' => 7,
        //         'early_pay_days' => 15,
        //         'early_pay_discount_percent' => 15,
        //         'auto_renew_allowed' => true,
        //         'features' => [
        //             'Up to 25 routers',
        //             '2000 users per router',
        //             'Unlimited zones',
        //             'All voucher templates',
        //             'Advanced analytics',
        //             'Reseller management',
        //             'Priority support',
        //             'Full API access',
        //             'White-label options',
        //         ],
        //         'is_featured' => false,
        //         'is_active' => true,
        //     ],
        //     [
        //         'name' => 'Enterprise',
        //         'description' => 'Ultimate solution for large organizations. Unlimited resources with dedicated account manager.',
        //         'price_monthly' => 7000,
        //         'price_yearly' => 70000,
        //         'max_routers' => 100,
        //         'max_users' => 10000,
        //         'max_zones' => 100,
        //         'max_vouchers_per_router' => 20000,
        //         'grace_period_days' => 10,
        //         'early_pay_days' => 30,
        //         'early_pay_discount_percent' => 20,
        //         'auto_renew_allowed' => true,
        //         'features' => [
        //             'Up to 100 routers',
        //             '10,000 users per router',
        //             'Unlimited zones',
        //             'Custom branding',
        //             'Advanced security features',
        //             'Multi-tenant support',
        //             'Dedicated account manager',
        //             '24/7 phone support',
        //             'Full API access',
        //             'Custom integrations',
        //             'SLA guarantee',
        //         ],
        //         'is_featured' => false,
        //         'is_active' => true,
        //     ],
        // ];

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
                'is_featured' => false,
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
                'is_featured' => false,
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
                'is_featured' => true,
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
                'is_featured' => false,
                'is_active' => true,
            ]
        ];


        foreach ($packages as $package) {
            Package::firstOrCreate(
                ['name' => $package['name']],
                $package
            );
        }

        $this->command->info('✅ Subscription packages created');
        $this->command->info('   5 packages: Free, Starter, Business (Featured), Professional, Enterprise');
    }
}
