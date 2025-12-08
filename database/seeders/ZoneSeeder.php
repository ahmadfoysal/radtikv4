<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('email', 'admin@example.com')->first()
            ?? User::orderBy('id')->first();

        if (! $owner) {
            return;
        }

        $zones = [
            [
                'name' => 'Downtown Core',
                'description' => 'High-density business district with fibre uplinks and redundant power.',
                'color' => '#2563eb',
            ],
            [
                'name' => 'Harbor District',
                'description' => 'Marina, cruise docks, and waterfront venues.',
                'color' => '#0ea5e9',
            ],
            [
                'name' => 'Mountain Ridge',
                'description' => 'Elevated repeaters that cover hillside communities and trails.',
                'color' => '#16a34a',
            ],
            [
                'name' => 'University Town',
                'description' => 'Campus buildings, dormitories, and research labs.',
                'color' => '#a855f7',
            ],
            [
                'name' => 'Industrial East',
                'description' => 'Factories, warehouses, and private 5G backhauls.',
                'color' => '#f97316',
            ],
            [
                'name' => 'Coastal Residential',
                'description' => 'Beachfront condos, hotels, and gated resort communities.',
                'color' => '#14b8a6',
            ],
        ];

        foreach ($zones as $zone) {
            Zone::updateOrCreate(
                [
                    'name' => $zone['name'],
                    'user_id' => $owner->id,
                ],
                [
                    'description' => $zone['description'],
                    'color' => $zone['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
