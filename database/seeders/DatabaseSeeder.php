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
        $this->call([
            PermissionSeed::class,
            UserSeed::class,
            ZoneSeeder::class,
            VoucherTemplateSeeder::class,
            PackageSeeder::class,
            RouterSeeder::class,
            VoucherSeeder::class,
            PaymentGatewaySeeder::class,
            KnowledgebaseArticleSeeder::class,
            DocumentationArticleSeeder::class,
        ]);
    }
}
