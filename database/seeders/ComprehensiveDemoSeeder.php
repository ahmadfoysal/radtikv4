<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\ResellerRouter;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Voucher;
use App\Models\VoucherLog;
use App\Models\VoucherTemplate;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ComprehensiveDemoSeeder extends Seeder
{
    private array $bangladeshCities = [
        'Dhaka',
        'Chittagong',
        'Sylhet',
        'Rajshahi',
        'Khulna',
        'Barisal',
        'Rangpur',
        'Mymensingh',
        'Comilla',
        'Gazipur',
        'Narayanganj',
        'Jessore',
        'Bogra',
        'Dinajpur',
        'Cox\'s Bazar'
    ];

    private array $bangladeshAreas = [
        'Dhanmondi',
        'Gulshan',
        'Banani',
        'Uttara',
        'Mirpur',
        'Mohammadpur',
        'Bashundhara',
        'Badda',
        'Rampura',
        'Tejgaon',
        'Farmgate',
        'Panthapath',
        'Kawran Bazar',
        'Motijheel',
        'Paltan'
    ];

    private array $profileTemplates = [
        ['name' => '1 Hour', 'price' => 20, 'days' => 0, 'hours' => 1, 'speed' => '5M/5M'],
        ['name' => '3 Hours', 'price' => 50, 'days' => 0, 'hours' => 3, 'speed' => '8M/8M'],
        ['name' => '12 Hours', 'price' => 100, 'days' => 0, 'hours' => 12, 'speed' => '10M/10M'],
        ['name' => '1 Day', 'price' => 150, 'days' => 1, 'hours' => 0, 'speed' => '15M/15M'],
        ['name' => '3 Days', 'price' => 350, 'days' => 3, 'hours' => 0, 'speed' => '20M/20M'],
        ['name' => '7 Days', 'price' => 700, 'days' => 7, 'hours' => 0, 'speed' => '25M/25M'],
        ['name' => '15 Days', 'price' => 1200, 'days' => 15, 'hours' => 0, 'speed' => '30M/30M'],
        ['name' => '30 Days', 'price' => 2000, 'days' => 30, 'hours' => 0, 'speed' => '50M/50M'],
    ];

    /**
     * Run the comprehensive demo seeder.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Comprehensive Demo Data Seeding...');
        $this->command->newLine();

        DB::beginTransaction();

        try {
            // 1. Create roles and permissions
            [$superadminRole, $adminRole, $resellerRole] = $this->createRolesAndPermissions();

            // 2. Create users
            [$superadmin, $admins, $resellers] = $this->createUsers($superadminRole, $adminRole, $resellerRole);

            // 3. Create packages
            $packages = $this->createPackages();

            // 4. Create subscriptions for admins
            $this->createSubscriptions($admins, $packages);

            // 5. Create zones
            $zones = $this->createZones($admins);

            // 6. Create voucher templates
            $templates = $this->createVoucherTemplates($admins);

            // 7. Create routers
            $routers = $this->createRouters($admins, $zones, $templates, $packages);

            // 8. Assign routers to resellers
            $this->assignRoutersToResellers($resellers, $routers, $admins);

            // 9. Create user profiles for routers
            $this->createUserProfiles($routers);

            // 10. Create vouchers
            $this->createVouchers($routers);

            // 11. Create voucher logs (activations)
            $this->createVoucherLogs($routers);

            // 12. Create invoices
            $this->createInvoices(array_merge($admins, $resellers), $superadmin);

            // 13. Create tickets
            $this->createTickets(array_merge($admins, $resellers));

            DB::commit();

            $this->command->newLine();
            $this->command->info('✅ Comprehensive Demo Data Seeding Completed!');
            $this->command->newLine();
            $this->displayCredentials();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createRolesAndPermissions(): array
    {
        $this->command->info('👥 Creating roles and permissions...');

        // Create roles
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $resellerRole = Role::firstOrCreate(['name' => 'reseller']);

        // Create all permissions
        $permissions = [
            'add_router',
            'edit_router',
            'delete_router',
            'view_router',
            'ping_router',
            'view_hotspot_users',
            'create_single_user',
            'edit_hotspot_users',
            'delete_hotspot_users',
            'view_active_sessions',
            'view_session_cookies',
            'view_hotspot_logs',
            'view_vouchers',
            'edit_vouchers',
            'delete_vouchers',
            'generate_vouchers',
            'print_vouchers',
            'bulk_delete_vouchers',
            'view_voucher_logs',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        return [$superadminRole, $adminRole, $resellerRole];
    }

    private function createUsers($superadminRole, $adminRole, $resellerRole): array
    {
        $this->command->info('👤 Creating demo users...');

        // Create Superadmin
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@radtik.demo'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'phone' => '+8801711000001',
                'address' => 'Head Office, Dhaka',
                'country' => 'Bangladesh',
                'balance' => 100000,
                'commission' => 0,
                'is_active' => true,
                'is_phone_verified' => true,
                'email_verified_at' => now(),
                'last_login_at' => Carbon::now()->subHours(2),
            ]
        );
        $superadmin->syncRoles([$superadminRole]);

        // Create 5 Admin users
        $admins = [];
        $adminNames = [
            ['name' => 'Ahmed Khan', 'city' => 'Dhaka', 'area' => 'Gulshan'],
            ['name' => 'Fatima Rahman', 'city' => 'Chittagong', 'area' => 'Agrabad'],
            ['name' => 'Jamal Hossain', 'city' => 'Sylhet', 'area' => 'Zindabazar'],
            ['name' => 'Nishat Alam', 'city' => 'Rajshahi', 'area' => 'Shaheb Bazar'],
            ['name' => 'Rashed Mahmud', 'city' => 'Khulna', 'area' => 'Sonadanga'],
        ];

        foreach ($adminNames as $index => $adminData) {
            $admin = User::updateOrCreate(
                ['email' => 'admin' . ($index + 1) . '@radtik.demo'],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make('password'),
                    'phone' => '+880171100' . str_pad($index + 10, 4, '0', STR_PAD_LEFT),
                    'address' => $adminData['area'] . ', ' . $adminData['city'],
                    'country' => 'Bangladesh',
                    'balance' => rand(10000, 50000),
                    'commission' => rand(5, 15),
                    'is_active' => true,
                    'is_phone_verified' => true,
                    'email_verified_at' => now(),
                    'last_login_at' => Carbon::now()->subHours(rand(1, 48)),
                ]
            );
            $admin->syncRoles([$adminRole]);
            $admins[] = $admin;
        }

        // Create 10 Reseller users
        $resellers = [];
        $resellerNames = [
            'Jamal Uddin',
            'Shabnam Sultana',
            'Rahim Mia',
            'Kulsum Begum',
            'Habib Rahman',
            'Amina Khatun',
            'Saiful Islam',
            'Rahela Akter',
            'Monir Hossain',
            'Nasrin Jahan'
        ];

        foreach ($resellerNames as $index => $name) {
            $adminOwner = $admins[array_rand($admins)];

            $reseller = User::updateOrCreate(
                ['email' => 'reseller' . ($index + 1) . '@radtik.demo'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'phone' => '+880171200' . str_pad($index + 10, 4, '0', STR_PAD_LEFT),
                    'address' => $this->bangladeshAreas[array_rand($this->bangladeshAreas)] . ', ' .
                        $this->bangladeshCities[array_rand($this->bangladeshCities)],
                    'country' => 'Bangladesh',
                    'balance' => rand(1000, 10000),
                    'admin_id' => $adminOwner->id,
                    'commission' => 0,
                    'is_active' => $index < 8, // 2 inactive
                    'is_phone_verified' => true,
                    'email_verified_at' => now(),
                    'last_login_at' => Carbon::now()->subDays(rand(0, 7)),
                ]
            );
            $reseller->syncRoles([$resellerRole]);

            // Assign permissions to resellers
            $resellerPermissions = Permission::whereIn('name', [
                'view_router',
                'view_vouchers',
                'generate_vouchers',
                'print_vouchers',
                'view_hotspot_users',
                'create_single_user',
                'view_active_sessions',
                'view_voucher_logs',
                'view_hotspot_logs'
            ])->get();
            $reseller->syncPermissions($resellerPermissions);

            $resellers[] = $reseller;
        }

        return [$superadmin, $admins, $resellers];
    }

    private function createPackages(): array
    {
        $this->command->info('📦 Creating subscription packages...');

        $packages = [
            [
                'name' => 'Free',
                'description' => 'Free plan with 1 router and 100 users - Perfect for testing',
                'price_monthly' => 0,
                'price_yearly' => 0,
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
                'price_monthly' => 500,
                'price_yearly' => 5500,
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
                'description' => 'Growing businesses with 10 routers and extended features',
                'price_monthly' => 1500,
                'price_yearly' => 16000,
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
                'description' => 'Professional package with 25 routers for medium enterprises',
                'price_monthly' => 3500,
                'price_yearly' => 38000,
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
                'price_monthly' => 6500,
                'price_yearly' => 70000,
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
            [
                'name' => 'Corporate',
                'description' => 'Premium package for large corporations with 100+ routers',
                'price_monthly' => 12000,
                'price_yearly' => 130000,
                'max_routers' => 100,
                'max_users' => 1000,
                'max_zones' => 100,
                'max_vouchers_per_router' => null,
                'grace_period_days' => 15,
                'early_pay_days' => 45,
                'early_pay_discount_percent' => 15,
                'auto_renew_allowed' => true,
                'is_active' => true,
            ],
        ];

        $created = [];
        foreach ($packages as $packageData) {
            $created[] = Package::firstOrCreate(
                ['name' => $packageData['name']],
                $packageData
            );
        }

        return $created;
    }

    private function createSubscriptions(array $admins, array $packages): void
    {
        $this->command->info('💳 Creating subscriptions for admins...');

        foreach ($admins as $index => $admin) {
            // Distribute packages among admins (some get free, some paid)
            $packageIndex = $index % count($packages);
            $package = $packages[$packageIndex];

            // Random billing cycle
            $billingCycle = rand(0, 1) === 0 ? 'monthly' : 'yearly';

            // Create subscription using the model method
            $admin->subscribeToPackage($package, $billingCycle);
        }
    }

    private function createZones(array $admins): array
    {
        $this->command->info('🗺️  Creating zones...');

        $zones = [];

        // Each admin gets 3-5 zones
        foreach ($admins as $admin) {
            $zoneCount = rand(3, 5);
            for ($i = 0; $i < $zoneCount; $i++) {
                $area = $this->bangladeshAreas[array_rand($this->bangladeshAreas)];
                $city = $this->bangladeshCities[array_rand($this->bangladeshCities)];

                $zone = Zone::firstOrCreate(
                    ['name' => $area . ' - ' . $city, 'user_id' => $admin->id],
                    ['description' => 'Coverage area for ' . $area . ' region in ' . $city]
                );
                $zones[] = $zone;
            }
        }

        return $zones;
    }

    private function createVoucherTemplates(array $admins): array
    {
        $this->command->info('🎫 Creating voucher templates...');

        $templates = [];

        $templateData = [
            ['name' => 'Classic Blue', 'component' => 'template-1'],
            ['name' => 'Thermal 80mm', 'component' => 'template-2'],
            ['name' => 'Modern Green', 'component' => 'template-3'],
        ];

        foreach ($templateData as $data) {
            $template = VoucherTemplate::firstOrCreate(
                ['name' => $data['name']],
                [
                    'component' => $data['component'],
                    'is_active' => true,
                ]
            );
            $templates[] = $template;
        }

        return $templates;
    }

    private function createRouters(array $admins, array $zones, array $templates, array $packages): array
    {
        $this->command->info('🌐 Creating routers...');

        $routers = [];
        $routerPrefix = ['Central', 'North', 'South', 'East', 'West', 'Main', 'Branch', 'Hub', 'Point', 'Gateway'];

        // Each admin gets 5-10 routers
        foreach ($admins as $adminIndex => $admin) {
            $adminZones = array_filter($zones, fn($z) => $z->user_id === $admin->id);
            $template = $templates[$adminIndex] ?? $templates[0];
            $routerCount = rand(5, 10);

            for ($i = 0; $i < $routerCount; $i++) {
                $prefix = $routerPrefix[array_rand($routerPrefix)];
                $area = $this->bangladeshAreas[array_rand($this->bangladeshAreas)];

                $baseIp = rand(10, 192) . '.' . rand(0, 255) . '.' . rand(0, 255);
                $createdAt = Carbon::now()->subDays(rand(0, 60));

                $router = Router::create([
                    'name' => $prefix . ' ' . $area . ' Router-' . ($i + 1),
                    'address' => $baseIp . '.1',
                    'login_address' => 'http://' . $baseIp . '.1/login',
                    'port' => 8728,
                    'ssh_port' => 22,
                    'username' => 'admin',
                    'password' => Crypt::encryptString('admin@' . rand(1000, 9999)),
                    'app_key' => bin2hex(random_bytes(16)),
                    'user_id' => $admin->id,
                    'zone_id' => !empty($adminZones) ? $adminZones[array_rand($adminZones)]->id : null,
                    'voucher_template_id' => $template->id,
                    'monthly_isp_cost' => rand(800, 3000),
                    'note' => 'Demo router for ' . $area . ' area coverage',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $routers[] = $router;
            }
        }

        return $routers;
    }

    private function assignRoutersToResellers(array $resellers, array $routers, array $admins): void
    {
        $this->command->info('🔗 Assigning routers to resellers...');

        foreach ($resellers as $reseller) {
            // Each reseller gets 2-5 routers from their admin
            $adminRouters = array_filter($routers, fn($r) => $r->user_id === $reseller->admin_id);

            if (empty($adminRouters)) continue;

            $assignCount = min(rand(2, 5), count($adminRouters));
            $selectedRouters = (array)array_rand(array_flip(array_keys($adminRouters)), $assignCount);

            foreach ($selectedRouters as $key) {
                $router = $adminRouters[$key];

                ResellerRouter::firstOrCreate(
                    ['router_id' => $router->id, 'reseller_id' => $reseller->id],
                    [
                        'assigned_by' => $reseller->admin_id,
                        'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    ]
                );
            }
        }
    }

    private function createUserProfiles(array $routers): void
    {
        $this->command->info('👥 Creating user profiles for routers...');

        foreach ($routers as $router) {
            // Each router gets all profile types
            foreach ($this->profileTemplates as $template) {
                UserProfile::firstOrCreate(
                    ['name' => $template['name'], 'router_id' => $router->id],
                    [
                        'validity_days' => $template['days'],
                        'validity_hours' => $template['hours'],
                        'speed_limit' => $template['speed'],
                        'price' => $template['price'],
                        'mac_binding' => false,
                        'description' => 'Internet access for ' . $template['name'],
                    ]
                );
            }
        }
    }

    private function createVouchers(array $routers): void
    {
        $this->command->info('🎟️  Creating vouchers...');

        $statuses = ['inactive', 'active', 'expired', 'disabled'];

        foreach ($routers as $router) {
            $profiles = UserProfile::where('router_id', $router->id)->get();

            if ($profiles->isEmpty()) continue;

            // Each router gets 50-100 vouchers
            $voucherCount = rand(50, 100);
            $batch = 'BATCH-' . strtoupper(substr(md5($router->id . time()), 0, 8));

            for ($i = 0; $i < $voucherCount; $i++) {
                $profile = $profiles->random();
                $status = $statuses[array_rand($statuses)];
                $createdAt = Carbon::now()->subDays(rand(0, 60));

                $activatedAt = in_array($status, ['active', 'expired'])
                    ? $createdAt->copy()->addHours(rand(1, 24))
                    : null;

                $expiresAt = $activatedAt
                    ? $activatedAt->copy()->addDays($profile->validity_days)->addHours($profile->validity_hours)
                    : null;

                if ($status === 'expired' && $expiresAt) {
                    $expiresAt = Carbon::now()->subDays(rand(1, 10));
                }

                Voucher::create([
                    'name' => 'Voucher-' . strtoupper(substr(md5(time() . $i), 0, 8)),
                    'username' => 'user' . rand(1000, 9999) . strtolower(substr(md5(rand()), 0, 4)),
                    'password' => strtoupper(substr(md5(rand()), 0, 8)),
                    'user_profile_id' => $profile->id,
                    'router_id' => $router->id,
                    'user_id' => $router->user_id,
                    'created_by' => $router->user_id,
                    'status' => $status,
                    'mac_address' => $this->generateMacAddress(),
                    'activated_at' => $activatedAt,
                    'expires_at' => $expiresAt,
                    'batch' => $batch,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function createVoucherLogs(array $routers): void
    {
        $this->command->info('📊 Creating voucher activation logs...');

        foreach ($routers as $router) {
            $profiles = UserProfile::where('router_id', $router->id)->get();

            if ($profiles->isEmpty()) continue;

            // Each router gets 100-300 activation logs over the past 60 days
            $logCount = rand(100, 300);

            for ($i = 0; $i < $logCount; $i++) {
                $profile = $profiles->random();
                $createdAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));

                VoucherLog::create([
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'username' => 'user' . rand(1000, 9999) . strtolower(substr(md5(rand()), 0, 4)),
                    'profile' => $profile->name,
                    'price' => $profile->price,
                    'event_type' => 'activated',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function createInvoices(array $users, User $superadmin): void
    {
        $this->command->info('💰 Creating invoices...');

        $categories = ['router_subscription', 'balance_topup', 'router_renewal', 'commission_payment'];
        $statuses = ['completed', 'pending', 'failed', 'cancelled'];

        // Create invoices for each user
        foreach ($users as $user) {
            $invoiceCount = rand(10, 30);

            for ($i = 0; $i < $invoiceCount; $i++) {
                $status = $i < ($invoiceCount * 0.8) ? 'completed' : $statuses[array_rand($statuses)];
                $category = $categories[array_rand($categories)];
                $createdAt = Carbon::now()->subDays(rand(0, 90));

                Invoice::create([
                    'user_id' => $user->id,
                    'amount' => rand(100, 10000),
                    'status' => $status,
                    'category' => $category,
                    'description' => ucfirst(str_replace('_', ' ', $category)) . ' - Invoice #' . strtoupper(substr(md5(time() . $i), 0, 8)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function createTickets(array $users): void
    {
        $this->command->info('🎫 Creating support tickets...');

        $subjects = [
            'Router not connecting to MikroTik',
            'Voucher generation failed',
            'Cannot access router dashboard',
            'Speed limit not working properly',
            'User profile configuration help needed',
            'Payment gateway integration issue',
            'Zone assignment problem',
            'Reseller access permission issue',
            'Voucher template customization',
            'Monthly expense calculation incorrect',
            'Email notification not working',
            'Two-factor authentication setup help',
            'Export vouchers to PDF problem',
            'Router statistics not updating',
            'Bulk voucher deletion error',
        ];

        $statuses = ['open', 'in_progress', 'waiting_reply', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        foreach ($users as $user) {
            $ticketCount = rand(2, 8);

            for ($i = 0; $i < $ticketCount; $i++) {
                $createdAt = Carbon::now()->subDays(rand(0, 60));
                $status = $statuses[array_rand($statuses)];

                $ticket = Ticket::create([
                    'user_id' => $user->id,
                    'subject' => $subjects[array_rand($subjects)],
                    'message' => 'This is a demo support ticket. ' .
                        'I am experiencing issues with the system. ' .
                        'Please help me resolve this problem as soon as possible. ' .
                        'The issue started about ' . rand(1, 10) . ' days ago.',
                    'status' => $status,
                    'priority' => $priorities[array_rand($priorities)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Add 0-3 replies to each ticket
                if (in_array($status, ['in_progress', 'waiting_reply', 'resolved', 'closed'])) {
                    $replyCount = rand(0, 3);

                    for ($j = 0; $j < $replyCount; $j++) {
                        $replyAt = $createdAt->copy()->addHours(rand(1, 48));
                        $isStaff = $j % 2 === 1;

                        TicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $isStaff ? 1 : $user->id, // 1 is superadmin
                            'message' => $isStaff
                                ? 'Thank you for contacting support. We are looking into this issue and will get back to you shortly.'
                                : 'Thank you for the quick response. I appreciate your help with this matter.',
                            'is_staff_reply' => $isStaff,
                            'created_at' => $replyAt,
                            'updated_at' => $replyAt,
                        ]);
                    }
                }
            }
        }
    }

    private function generateMacAddress(): string
    {
        return sprintf(
            '%02X:%02X:%02X:%02X:%02X:%02X',
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            rand(0, 255),
            rand(0, 255)
        );
    }

    private function displayCredentials(): void
    {
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Superadmin', 'superadmin@radtik.demo', 'password'],
                ['Admin 1', 'admin1@radtik.demo', 'password'],
                ['Admin 2', 'admin2@radtik.demo', 'password'],
                ['Admin 3', 'admin3@radtik.demo', 'password'],
                ['Reseller 1', 'reseller1@radtik.demo', 'password'],
                ['Reseller 2', 'reseller2@radtik.demo', 'password'],
            ]
        );

        $this->command->newLine();
        $this->command->warn('⚠️  DEMO MODE: All demo accounts use email domain "@radtik.demo"');
        $this->command->info('💡 You can login with any of the above credentials to explore the system');
    }
}
