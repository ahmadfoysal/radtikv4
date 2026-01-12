<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Database Seeding...');
        $this->command->newLine();

        // Check if comprehensive demo mode is enabled
        $useDemoData = $this->shouldUseDemoSeeder();

        if ($useDemoData) {
            $this->command->info('📦 Using Demo Data Seeder...');
            $this->call([
                ComprehensiveDemoSeeder::class,
            ]);
        } else {
            $this->command->info('📦 Creating production essentials...');
            $this->call([
                PermissionSeed::class,
                PaymentGatewaySeeder::class,
                VoucherTemplateSeeder::class,
                KnowledgebaseArticleSeeder::class,
                DocumentationArticleSeeder::class,
            ]);
        }

        $this->command->newLine();
        $this->command->info('✅ Database Seeding Completed!');
    }

    /**
     * Determine if demo seeder should be used.
     * Priority: ENV variable > Interactive prompt
     * Default: Production mode (false)
     */
    private function shouldUseDemoSeeder(): bool
    {
        // 1. Check environment variable (highest priority)
        if (env('USE_DEMO_SEEDER') !== null) {
            return filter_var(env('USE_DEMO_SEEDER'), FILTER_VALIDATE_BOOLEAN);
        }

        // 2. Interactive prompt (defaults to production mode)
        return $this->command->confirm(
            'Do you want to seed with demo data? (Use for testing/demo only)',
            false
        );
    }
}
