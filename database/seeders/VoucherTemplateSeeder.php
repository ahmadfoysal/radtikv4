<?php

namespace Database\Seeders;

use App\Models\VoucherTemplate;
use Illuminate\Database\Seeder;

class VoucherTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default voucher print templates.
     */
    public function run(): void
    {
        $this->command->info('🎫 Creating voucher templates...');

        $templates = [
            [
                'name' => 'Classic Template (Default)',
                'component' => 'template-1',
                'is_active' => true,
            ],
            [
                'name' => 'Modern Template',
                'component' => 'template-2',
                'is_active' => true,
            ],
            [
                'name' => 'Minimal Template',
                'component' => 'template-3',
                'is_active' => true,
            ],
            [
                'name' => 'Professional Template',
                'component' => 'template-4',
                'is_active' => true,
            ],
            [
                'name' => 'Thermal Receipt (80mm)',
                'component' => 'template-5',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            VoucherTemplate::firstOrCreate(
                ['component' => $template['component']],
                $template
            );
        }

        $this->command->info('✅ Voucher templates created');
    }
}
