<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetDemoData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'demo:reset {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reset demo data to default state (runs hourly in production)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will delete all demo data and reset to default. Continue?', true)) {
                $this->info('Operation cancelled.');
                return self::FAILURE;
            }
        }

        $this->info('🔄 Starting demo data reset...');
        $startTime = microtime(true);

        try {
            // Step 1: Clear demo-related data
            $this->clearDemoData();

            // Step 2: Re-seed demo data
            $this->info('📝 Seeding fresh demo data...');
            Artisan::call('db:seed', ['--class' => 'DemoDataSeeder', '--force' => true]);
            $this->info(Artisan::output());

            // Step 3: Clear caches
            $this->clearCaches();

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Demo data reset completed in {$duration} seconds!");
            $this->newLine();
            $this->info('📝 Demo Credentials:');
            $this->info('  Superadmin: demo-superadmin@radtik.local / password');
            $this->info('  Admin:      demo-admin@radtik.local / password');
            $this->info('  Reseller:   demo-reseller@radtik.local / password');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error resetting demo data: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Clear all demo-related data from the database
     */
    protected function clearDemoData(): void
    {
        $this->info('🗑️  Clearing demo data...');

        DB::beginTransaction();

        try {
            // Delete data created by demo users (identified by email domain @radtik.local)
            $demoUserIds = DB::table('users')
                ->where('email', 'like', '%@example.com')
                ->pluck('id')
                ->toArray();

            if (empty($demoUserIds)) {
                $this->warn('No demo users found. Skipping data cleanup.');
                DB::rollBack();
                return;
            }

            $this->line('  - Found ' . count($demoUserIds) . ' demo users');

            // Delete in order to respect foreign key constraints

            // 1. Voucher logs (no FK constraints)
            $deleted = DB::table('voucher_logs')
                ->whereIn('router_id', function ($query) use ($demoUserIds) {
                    $query->select('id')->from('routers')->whereIn('user_id', $demoUserIds);
                })
                ->delete();
            $this->line("  - Deleted {$deleted} voucher logs");

            // 2. Vouchers
            $deleted = DB::table('vouchers')
                ->whereIn('router_id', function ($query) use ($demoUserIds) {
                    $query->select('id')->from('routers')->whereIn('user_id', $demoUserIds);
                })
                ->delete();
            $this->line("  - Deleted {$deleted} vouchers");

            // 3. Reseller router assignments
            $deleted = DB::table('reseller_router')
                ->whereIn('reseller_id', $demoUserIds)
                ->orWhereIn('assigned_by', $demoUserIds)
                ->delete();
            $this->line("  - Deleted {$deleted} reseller router assignments");

            // 4. Profiles
            $deleted = DB::table('profiles')
                ->whereIn('router_id', function ($query) use ($demoUserIds) {
                    $query->select('id')->from('routers')->whereIn('user_id', $demoUserIds);
                })
                ->delete();
            $this->line("  - Deleted {$deleted} profiles");

            // 5. Tickets
            $deleted = DB::table('tickets')
                ->whereIn('user_id', $demoUserIds)
                ->delete();
            $this->line("  - Deleted {$deleted} tickets");

            // 6. Invoices
            $deleted = DB::table('invoices')
                ->whereIn('user_id', $demoUserIds)
                ->delete();
            $this->line("  - Deleted {$deleted} invoices");

            // 7. Routers
            $deleted = DB::table('routers')
                ->whereIn('user_id', $demoUserIds)
                ->delete();
            $this->line("  - Deleted {$deleted} routers");

            // 8. Zones
            $deleted = DB::table('zones')
                ->whereIn('user_id', $demoUserIds)
                ->delete();
            $this->line("  - Deleted {$deleted} zones");

            // 9. Activity logs related to demo users
            $deleted = DB::table('activity_log')
                ->whereIn('causer_id', $demoUserIds)
                ->where('causer_type', 'App\\Models\\User')
                ->delete();
            $this->line("  - Deleted {$deleted} activity logs");

            // 10. User permissions
            DB::table('model_has_permissions')
                ->whereIn('model_id', $demoUserIds)
                ->where('model_type', 'App\\Models\\User')
                ->delete();

            // 11. User roles
            DB::table('model_has_roles')
                ->whereIn('model_id', $demoUserIds)
                ->where('model_type', 'App\\Models\\User')
                ->delete();

            // 12. Finally, delete demo users
            $deleted = DB::table('users')
                ->where('email', 'like', '%@example.com')
                ->delete();
            $this->line("  - Deleted {$deleted} demo users");

            DB::commit();
            $this->info('✓ Demo data cleared successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clear application caches
     */
    protected function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $this->line('  - All caches cleared');
    }
}
