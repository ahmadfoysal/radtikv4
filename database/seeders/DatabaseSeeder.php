<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        // Determine seeding mode
        $useComprehensive = $this->shouldUseComprehensiveSeeder();

        if ($useComprehensive) {
            $this->command->info('📦 Using Comprehensive Demo Seeder...');
            $this->call([
                ComprehensiveDemoSeeder::class,
                PaymentGatewaySeeder::class,
                EmailSettingSeeder::class,
                KnowledgebaseArticleSeeder::class,
                DocumentationArticleSeeder::class,
            ]);
        } else {
            $this->command->info('📦 Using Basic Seeders...');
            $this->call([
                PermissionSeed::class,
                UserSeed::class,
                ZoneSeeder::class,
                VoucherTemplateSeeder::class,
                PackageSeeder::class,
                RouterSeeder::class,
                VoucherSeeder::class,
                PaymentGatewaySeeder::class,
                EmailSettingSeeder::class,
                KnowledgebaseArticleSeeder::class,
                DocumentationArticleSeeder::class,
            ]);
        }

        $this->command->newLine();
        $this->command->info('✅ Database Seeding Completed!');
    }

    /**
     * Determine if comprehensive seeder should be used.
     * Priority: ENV variable > Interactive prompt
     * Default: Basic seeders (false)
     */
    private function shouldUseComprehensiveSeeder(): bool
    {
        // 1. Check environment variable (highest priority)
        if (env('USE_COMPREHENSIVE_SEEDER') !== null) {
            return filter_var(env('USE_COMPREHENSIVE_SEEDER'), FILTER_VALIDATE_BOOLEAN);
        }

        // 2. Interactive prompt (defaults to basic seeders)
        return $this->command->confirm(
            'Do you want to create comprehensive demo data with realistic content?',
            false // Default to "no" - uses basic seeders
        );
    }
}
