<?php

namespace Database\Seeders;

use App\Models\Voucher;
use App\Models\VoucherTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VoucherTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Classic Simple',
                'component' => 'template-1',
                'preview_image' => 'templates/preview-1.png',
                'is_active' => true,
            ],
            [
                'name' => 'Modern Dark',
                'component' => 'template-2',
                'preview_image' => 'templates/preview-2.png',
                'is_active' => true,
            ],
            [
                'name' => 'Thermal Receipt (80mm)',
                'component' => 'template-3',
                'preview_image' => 'templates/preview-3.png',
                'is_active' => true,
            ],
            [
                'name' => 'Minimalist White',
                'component' => 'template-4',
                'preview_image' => 'templates/preview-4.png',
                'is_active' => true,
            ],
            [
                'name' => 'Gradient Blue',
                'component' => 'template-5',
                'preview_image' => 'templates/preview-5.png',
                'is_active' => true,
            ],
            [
                'name' => 'QR Code Focus',
                'component' => 'template-6',
                'preview_image' => 'templates/preview-6.png',
                'is_active' => true,
            ],
            [
                'name' => 'Corporate Professional',
                'component' => 'template-7',
                'preview_image' => 'templates/preview-7.png',
                'is_active' => true,
            ],
            [
                'name' => 'Retro Style',
                'component' => 'template-8',
                'preview_image' => 'templates/preview-8.png',
                'is_active' => true,
            ],
            [
                'name' => 'Compact Strip',
                'component' => 'template-9',
                'preview_image' => 'templates/preview-9.png',
                'is_active' => true,
            ],
            [
                'name' => 'Festive Event',
                'component' => 'template-10',
                'preview_image' => 'templates/preview-10.png',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            VoucherTemplate::updateOrCreate(
                ['component' => $template['component']], // Check duplicates by component name
                $template
            );
        }
    }
}
