<?php

namespace Database\Seeders;

use App\Models\Router;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routers = Router::orderBy('id')->get();
        $creator = User::where('email', 'admin@example.com')->first()
            ?? User::orderBy('id')->first();

        if ($routers->isEmpty() || ! $creator) {
            return;
        }

        $profiles = $this->prepareProfiles($creator);

        if ($profiles->isEmpty()) {
            return;
        }

        $rows = [];
        $statuses = ['inactive', 'active', 'expired', 'disabled'];
        $now = now();

        foreach ($routers as $routerIndex => $router) {
            $batch = sprintf('R%02d-%s', $routerIndex + 1, Str::upper(Str::random(4)));

            for ($i = 0; $i < 20; $i++) {
                $status = $statuses[$i % count($statuses)];
                $activatedAt = in_array($status, ['active', 'expired'], true)
                    ? $now->copy()->subDays(random_int(1, 10))
                    : null;
                $expiresAt = $status === 'expired'
                    ? $now->copy()->subDays(random_int(1, 3))
                    : $now->copy()->addDays(20 + $i);
                $profile = $profiles[$i % $profiles->count()];

                $rows[] = [
                    'name' => sprintf('Voucher %02d-%02d', $routerIndex + 1, $i + 1),
                    'user_profile_id' => $profile->id,
                    'username' => strtolower(Str::random(4)) . sprintf('%02d', $router->id) . Str::random(2),
                    'password' => Str::upper(Str::random(6)),
                    'status' => $status,
                    'mac_address' => $this->randomMac(),
                    'activated_at' => $activatedAt,
                    'expires_at' => $expiresAt,
                    'user_id' => $router->user_id ?? $creator->id,
                    'router_id' => $router->id,
                    'created_by' => $creator->id,
                    'bytes_in' => random_int(1_000_000, 120_000_000),
                    'bytes_out' => random_int(1_000_000, 120_000_000),
                    'up_time' => sprintf('%dh %02dm', rand(1, 72), rand(0, 59)),
                    'batch' => $batch,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            Voucher::insert($rows);
        }
    }

    /**
     * Ensure seed user has usable profiles.
     */
    protected function prepareProfiles(User $user): Collection
    {
        $definitions = collect([
            [
                'name' => 'Home 10M',
                'rate_limit' => '10M/5M',
                'validity' => '30d',
                'mac_binding' => false,
                'price' => 15.00,
                'description' => 'Default residential profile.',
            ],
            [
                'name' => 'Cafe Burst',
                'rate_limit' => '20M/10M',
                'validity' => '15d',
                'mac_binding' => false,
                'price' => 9.50,
                'description' => 'Short-term voucher for cafés and pop-ups.',
            ],
            [
                'name' => 'Event Unlimited',
                'rate_limit' => '50M/50M',
                'validity' => '7d',
                'mac_binding' => true,
                'price' => 25.00,
                'description' => 'Strict profile for ticketed events.',
            ],
            [
                'name' => 'Basic 5M',
                'rate_limit' => '5M/2M',
                'validity' => '7d',
                'mac_binding' => false,
                'price' => 5.00,
                'description' => 'Entry-level internet access.',
            ],
            [
                'name' => 'Student Plan',
                'rate_limit' => '15M/8M',
                'validity' => '90d',
                'mac_binding' => true,
                'price' => 40.00,
                'description' => 'Long-term plan for students.',
            ],
            [
                'name' => 'Business Pro',
                'rate_limit' => '100M/50M',
                'validity' => '30d',
                'mac_binding' => true,
                'price' => 75.00,
                'description' => 'High-speed business internet.',
            ],
            [
                'name' => 'Guest Access',
                'rate_limit' => '2M/1M',
                'validity' => '1d',
                'mac_binding' => false,
                'price' => 2.00,
                'description' => 'Limited guest internet access.',
            ],
            [
                'name' => 'Premium 25M',
                'rate_limit' => '25M/15M',
                'validity' => '60d',
                'mac_binding' => false,
                'price' => 45.00,
                'description' => 'Premium residential package.',
            ],
            [
                'name' => 'Conference WiFi',
                'rate_limit' => '30M/20M',
                'validity' => '3d',
                'mac_binding' => false,
                'price' => 12.00,
                'description' => 'Temporary access for conferences.',
            ],
            [
                'name' => 'Enterprise',
                'rate_limit' => '200M/100M',
                'validity' => '365d',
                'mac_binding' => true,
                'price' => 500.00,
                'description' => 'Annual enterprise-grade connection.',
            ],
        ]);

        return $definitions->map(function (array $definition) use ($user) {
            return UserProfile::firstOrCreate(
                ['name' => $definition['name'], 'user_id' => $user->id],
                [
                    'rate_limit' => $definition['rate_limit'],
                    'validity' => $definition['validity'],
                    'mac_binding' => $definition['mac_binding'],
                    'price' => $definition['price'],
                    'description' => $definition['description'],
                ]
            );
        });
    }

    protected function randomMac(): string
    {
        return implode(':', array_map(
            fn() => strtoupper(str_pad(dechex(random_int(0, 255)), 2, '0', STR_PAD_LEFT)),
            range(1, 6)
        ));
    }
}
