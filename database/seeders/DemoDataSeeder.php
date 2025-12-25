<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Profile;
use App\Models\ResellerRouter;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherTemplate;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds for demo environment.
     * Creates realistic demo data with multiple users, routers, vouchers, etc.
     */
    public function run(): void
    {
        // Mark as demo mode
        $this->command->info('🎭 Creating Demo Data...');

        // Step 1: Create roles and permissions if they don't exist
        $this->createRolesAndPermissions();

        // Step 2: Create demo users (identifiable by email domain)
        $superadmin = $this->createDemoSuperAdmin();
        $admin = $this->createDemoAdmin();
        $reseller = $this->createDemoReseller($admin);

        // Step 3: Create packages
        $packages = $this->createPackages();

        // Step 4: Create zones
        $zones = $this->createZones($admin);

        // Step 5: Create voucher template
        $template = $this->createVoucherTemplate();

        // Step 6: Create routers for admin
        $routers = $this->createRouters($admin, $zones, $template, $packages);

        // Step 7: Assign some routers to reseller
        $this->assignRoutersToReseller($reseller, $routers, $admin);

        // Step 8: Create profiles for routers
        $this->createProfiles($routers);

        // Step 9: Create vouchers
        $vouchers = $this->createVouchers($routers);

        // Step 10: Create voucher logs (activations)
        $this->createVoucherLogs($routers);

        // Step 11: Create invoices
        $this->createInvoices($admin, $superadmin);

        // Step 12: Create support tickets
        $this->createTickets($admin, $reseller);

        $this->command->info('✅ Demo data created successfully!');
        $this->command->info('');
        $this->command->info('📝 Demo Credentials:');
        $this->command->info('  Admin:    demo@example.com / 12345678');
        $this->command->info('  Reseller: reseller@example.com / 12345678');
        $this->command->info('');
        $this->command->warn('⚠️  Demo data will reset every hour!');
    }

    protected function createRolesAndPermissions(): void
    {
        $this->command->info('Creating roles and permissions...');

        // Create roles if they don't exist
        $roles = ['superadmin', 'admin', 'reseller'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Create permissions
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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }

    protected function createDemoSuperAdmin(): User
    {
        $this->command->info('Creating superadmin with strong password...');

        // Generate a strong random password for superadmin (not for demo access)
        $strongPassword = bin2hex(random_bytes(16)); // 32 character random password

        $user = User::updateOrCreate(
            ['email' => 'superadmin@radtik.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($strongPassword),
                'is_active' => true,
                'balance' => 100000,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['superadmin']);

        // Store password in environment or display it once
        $this->command->warn('🔐 SUPERADMIN PASSWORD (SAVE THIS!): ' . $strongPassword);
        $this->command->warn('   Email: superadmin@radtik.local');

        return $user;
    }

    protected function createDemoAdmin(): User
    {
        $this->command->info('Creating demo admin...');

        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'balance' => 25000,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['admin']);

        return $user;
    }

    protected function createDemoReseller(User $admin): User
    {
        $this->command->info('Creating demo reseller...');

        $user = User::updateOrCreate(
            ['email' => 'reseller@example.com'],
            [
                'name' => 'Demo Reseller',
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'balance' => 5000,
                'admin_id' => $admin->id,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['reseller']);

        // Assign all reseller permissions
        $permissions = Permission::whereIn('name', [
            'view_router',
            'add_router',
            'view_vouchers',
            'generate_vouchers',
            'print_vouchers',
            'create_single_user',
            'view_active_sessions',
            'view_session_cookies',
            'view_hotspot_logs',
            'view_voucher_logs',
        ])->get();

        $user->syncPermissions($permissions);

        return $user;
    }

    protected function createPackages(): array
    {
        $this->command->info('Creating packages...');

        $packages = [
            ['name' => 'Basic Monthly', 'price' => 500, 'billing_cycle' => 'monthly', 'duration_days' => 30, 'max_routers' => 5],
            ['name' => 'Standard Monthly', 'price' => 1000, 'billing_cycle' => 'monthly', 'duration_days' => 30, 'max_routers' => 15],
            ['name' => 'Premium Monthly', 'price' => 2000, 'billing_cycle' => 'monthly', 'duration_days' => 30, 'max_routers' => 50],
            ['name' => 'Annual Plan', 'price' => 10000, 'billing_cycle' => 'yearly', 'duration_days' => 365, 'max_routers' => 100],
        ];

        $created = [];
        foreach ($packages as $package) {
            $created[] = Package::firstOrCreate(['name' => $package['name']], $package);
        }

        return $created;
    }

    protected function createZones(User $admin): array
    {
        $this->command->info('Creating zones...');

        $zones = ['North Zone', 'South Zone', 'East Zone', 'West Zone', 'Central Zone'];
        $created = [];

        foreach ($zones as $zoneName) {
            $created[] = Zone::firstOrCreate(
                ['name' => $zoneName, 'user_id' => $admin->id],
                ['description' => "Demo {$zoneName} for testing"]
            );
        }

        return $created;
    }

    protected function createVoucherTemplate(): VoucherTemplate
    {
        $this->command->info('Creating voucher template...');

        return VoucherTemplate::firstOrCreate(
            ['name' => 'Demo Template'],
            [
                'template' => 'Default demo template',
                'is_active' => true,
            ]
        );
    }

    protected function createRouters(User $admin, array $zones, VoucherTemplate $template, array $packages): array
    {
        $this->command->info('Creating demo routers...');

        $routerData = [
            ['name' => 'Router Central Office', 'address' => '192.168.1.1', 'zone_index' => 4],
            ['name' => 'Router North Branch', 'address' => '192.168.2.1', 'zone_index' => 0],
            ['name' => 'Router South Hub', 'address' => '192.168.3.1', 'zone_index' => 1],
            ['name' => 'Router East Point', 'address' => '192.168.4.1', 'zone_index' => 2],
            ['name' => 'Router West Tower', 'address' => '192.168.5.1', 'zone_index' => 3],
        ];

        $routers = [];
        foreach ($routerData as $index => $data) {
            $package = $packages[$index % count($packages)];
            $endDate = Carbon::now()->addDays($package->duration_days ?? 30);

            $router = Router::firstOrCreate(
                ['address' => $data['address'], 'user_id' => $admin->id],
                [
                    'name' => $data['name'],
                    'port' => 8728,
                    'username' => 'admin',
                    'password' => Crypt::encryptString('admin123'),
                    'app_key' => bin2hex(random_bytes(16)),
                    'zone_id' => $zones[$data['zone_index']]->id ?? null,
                    'voucher_template_id' => $template->id,
                    'login_address' => "http://10.0.{$index}.1/login",
                    'monthly_expense' => rand(500, 2000),
                    'package' => [
                        'name' => $package->name,
                        'billing_cycle' => $package->billing_cycle,
                        'price' => $package->price,
                        'start_date' => Carbon::now()->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ],
                ]
            );

            $routers[] = $router;
        }

        return $routers;
    }

    protected function assignRoutersToReseller(User $reseller, array $routers, User $admin): void
    {
        $this->command->info('Assigning routers to reseller...');

        // Assign first 3 routers to reseller
        for ($i = 0; $i < min(3, count($routers)); $i++) {
            ResellerRouter::firstOrCreate(
                ['router_id' => $routers[$i]->id, 'reseller_id' => $reseller->id],
                ['assigned_by' => $admin->id]
            );
        }
    }

    protected function createProfiles(array $routers): void
    {
        $this->command->info('Creating profiles...');

        $profileTemplates = [
            ['name' => '1 Hour - 5 Mbps', 'validity_days' => 0, 'validity_hours' => 1, 'speed_limit' => '5M/5M'],
            ['name' => '1 Day - 10 Mbps', 'validity_days' => 1, 'validity_hours' => 0, 'speed_limit' => '10M/10M'],
            ['name' => '3 Days - 15 Mbps', 'validity_days' => 3, 'validity_hours' => 0, 'speed_limit' => '15M/15M'],
            ['name' => '7 Days - 20 Mbps', 'validity_days' => 7, 'validity_hours' => 0, 'speed_limit' => '20M/20M'],
            ['name' => '30 Days - 50 Mbps', 'validity_days' => 30, 'validity_hours' => 0, 'speed_limit' => '50M/50M'],
        ];

        foreach ($routers as $router) {
            foreach ($profileTemplates as $template) {
                Profile::firstOrCreate(
                    ['name' => $template['name'], 'router_id' => $router->id],
                    [
                        'validity_days' => $template['validity_days'],
                        'validity_hours' => $template['validity_hours'],
                        'speed_limit' => $template['speed_limit'],
                        'price' => rand(20, 500),
                    ]
                );
            }
        }
    }

    protected function createVouchers(array $routers): array
    {
        $this->command->info('Creating vouchers...');

        $statuses = ['active', 'inactive', 'expired'];
        $vouchers = [];

        foreach ($routers as $router) {
            $profiles = Profile::where('router_id', $router->id)->get();

            foreach ($profiles as $profile) {
                // Create 10 vouchers per profile
                for ($i = 0; $i < 10; $i++) {
                    $status = $statuses[array_rand($statuses)];
                    $createdAt = Carbon::now()->subDays(rand(0, 30));

                    $voucher = Voucher::create([
                        'router_id' => $router->id,
                        'profile' => $profile->name,
                        'username' => 'demo-' . strtolower(str_replace(' ', '', $profile->name)) . '-' . rand(1000, 9999),
                        'password' => bin2hex(random_bytes(4)),
                        'status' => $status,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $vouchers[] = $voucher;
                }
            }
        }

        return $vouchers;
    }

    protected function createVoucherLogs(array $routers): void
    {
        $this->command->info('Creating voucher activation logs...');

        foreach ($routers as $router) {
            $profiles = Profile::where('router_id', $router->id)->get();

            foreach ($profiles as $profile) {
                // Create 20-50 activation logs per profile
                $activationCount = rand(20, 50);

                for ($i = 0; $i < $activationCount; $i++) {
                    $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

                    DB::table('voucher_logs')->insert([
                        'router_id' => $router->id,
                        'router_name' => $router->name,
                        'username' => 'demo-user-' . rand(1000, 9999),
                        'profile' => $profile->name,
                        'price' => $profile->price,
                        'event_type' => 'activated',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }
    }

    protected function createInvoices(User $admin, User $superadmin): void
    {
        $this->command->info('Creating invoices...');

        $categories = ['router_subscription', 'balance_topup', 'router_renewal'];
        $statuses = ['completed', 'pending'];

        // Create 30 invoices
        for ($i = 0; $i < 30; $i++) {
            $status = $i < 25 ? 'completed' : 'pending'; // Most completed, some pending
            $createdAt = Carbon::now()->subDays(rand(0, 60));

            Invoice::create([
                'user_id' => $admin->id,
                'amount' => rand(100, 5000),
                'status' => $status,
                'category' => $categories[array_rand($categories)],
                'description' => 'Demo invoice #' . ($i + 1),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Create some for superadmin too
        for ($i = 0; $i < 10; $i++) {
            Invoice::create([
                'user_id' => $superadmin->id,
                'amount' => rand(1000, 10000),
                'status' => 'completed',
                'category' => 'balance_topup',
                'description' => 'Superadmin demo invoice #' . ($i + 1),
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
            ]);
        }
    }

    protected function createTickets(User $admin, User $reseller): void
    {
        $this->command->info('Creating support tickets...');

        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high'];

        $ticketTopics = [
            'Router connection issue',
            'Voucher generation problem',
            'Login credentials not working',
            'Speed limit configuration help',
            'Profile creation assistance',
            'Payment gateway inquiry',
            'Zone management question',
            'Dashboard statistics clarification',
        ];

        // Create 15 tickets
        for ($i = 0; $i < 15; $i++) {
            $user = $i % 2 === 0 ? $admin : $reseller;
            $createdAt = Carbon::now()->subDays(rand(0, 45));

            Ticket::create([
                'user_id' => $user->id,
                'subject' => $ticketTopics[array_rand($ticketTopics)],
                'message' => 'This is a demo support ticket created for testing purposes. The issue is described here in detail.',
                'status' => $statuses[array_rand($statuses)],
                'priority' => $priorities[array_rand($priorities)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
