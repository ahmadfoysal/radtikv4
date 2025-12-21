<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use App\Models\Zone;
use App\Models\VoucherTemplate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class RouterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(2);
        $packages = Package::orderBy('id')->get();
        $voucherTemplates = VoucherTemplate::orderBy('id')->get();
        $zones = Zone::orderBy('id')->get();

        if (! $user || $packages->isEmpty() || $voucherTemplates->isEmpty() || $zones->isEmpty()) {
            return;
        }

        $routers = [
            [
                'name' => 'Metro Core 01',
                'address' => '10.20.10.1',
                'login_address' => 'metro-core-01.isp.local',
                'port' => 8728,
                'ssh_port' => 2222,
                'username' => 'metro-admin',
                'password' => 'Metro#2401!',
                'monthly_isp_cost' => 85.50,
                'note' => 'Primary POP for the downtown mesh.',
                'logo' => 'routers/metro-core.svg',
            ],
            [
                'name' => 'Summit North Edge',
                'address' => '10.20.20.1',
                'login_address' => 'north-edge.summitisp.net',
                'port' => 8728,
                'ssh_port' => 2223,
                'username' => 'summit-admin',
                'password' => 'Summit#2402!',
                'monthly_isp_cost' => 92.75,
                'note' => 'Handles suburban and mountain subscribers.',
                'logo' => 'routers/summit-edge.svg',
            ],
            [
                'name' => 'Harbor Gateway',
                'address' => '10.30.10.5',
                'login_address' => 'harbor-gw.isp.local',
                'port' => 8729,
                'ssh_port' => 2224,
                'username' => 'harbor-admin',
                'password' => 'Harbor#2403!',
                'monthly_isp_cost' => 76.20,
                'note' => 'Feeds marina Wi-Fi and event halls.',
                'logo' => 'routers/harbor-gateway.svg',
            ],
            [
                'name' => 'West Park Uplink',
                'address' => '10.40.5.10',
                'login_address' => 'uplink-west.parknet.io',
                'port' => 8730,
                'ssh_port' => 2225,
                'username' => 'west-admin',
                'password' => 'West#2404!',
                'monthly_isp_cost' => 66.00,
                'note' => 'Community park deployments and CCTV offload.',
                'logo' => 'routers/west-park.svg',
            ],
            [
                'name' => 'East Campus POP',
                'address' => '10.50.1.25',
                'login_address' => 'east-campus.pop.local',
                'port' => 8728,
                'ssh_port' => 2226,
                'username' => 'campus-admin',
                'password' => 'Campus#2405!',
                'monthly_isp_cost' => 104.30,
                'note' => 'Edu roaming, labs, and dormitories.',
                'logo' => 'routers/east-campus.svg',
            ],
            [
                'name' => 'Canyon Ridge Relay',
                'address' => '10.60.14.2',
                'login_address' => 'relay-canyon.ridgewan.net',
                'port' => 8731,
                'ssh_port' => 2227,
                'username' => 'canyon-admin',
                'password' => 'Canyon#2406!',
                'monthly_isp_cost' => 58.40,
                'note' => 'Bridges remote canyon subscribers.',
                'logo' => 'routers/canyon-relay.svg',
            ],
            [
                'name' => 'Airport South Hub',
                'address' => '10.70.3.3',
                'login_address' => 'airport-hub-south.wisp',
                'port' => 8727,
                'ssh_port' => 2228,
                'username' => 'airport-admin',
                'password' => 'Airport#2407!',
                'monthly_isp_cost' => 149.80,
                'note' => 'Handles captive portal and lounge traffic.',
                'logo' => 'routers/airport-hub.svg',
            ],
            [
                'name' => 'Lakeside Distribution',
                'address' => '10.80.8.8',
                'login_address' => 'lakeside-distribution.isp',
                'port' => 8728,
                'ssh_port' => 2229,
                'username' => 'lakeside-admin',
                'password' => 'Lake#2408!',
                'monthly_isp_cost' => 71.10,
                'note' => 'Covers resorts and floating venues.',
                'logo' => 'routers/lakeside.svg',
            ],
            [
                'name' => 'Industrial East Spine',
                'address' => '10.90.12.12',
                'login_address' => 'industrial-spine-east.net',
                'port' => 8728,
                'ssh_port' => 2230,
                'username' => 'spine-admin',
                'password' => 'Spine#2409!',
                'monthly_isp_cost' => 134.25,
                'note' => 'High-availability core for factories.',
                'logo' => 'routers/industrial-spine.svg',
            ],
            [
                'name' => 'Valley Residential Hub',
                'address' => '10.110.4.4',
                'login_address' => 'valley-hub.homeisp.net',
                'port' => 8726,
                'ssh_port' => 2232,
                'username' => 'valley-admin',
                'password' => 'Valley#2411!',
                'monthly_isp_cost' => 63.90,
                'note' => 'Focuses on HOA and gated communities.',
                'logo' => 'routers/valley-hub.svg',
            ],
        ];

        foreach ($routers as $index => $routerData) {
            $templateId = $voucherTemplates[$index % $voucherTemplates->count()]->id;
            $zoneId = $zones[$index % $zones->count()]->id;
            $router = Router::firstOrNew(['address' => $routerData['address']]);
            $router->fill([
                'name' => $routerData['name'],
                'address' => $routerData['address'],
                'login_address' => $routerData['login_address'],
                'port' => (string) $routerData['port'],
                'ssh_port' => (string) $routerData['ssh_port'],
                'username' => $routerData['username'],
                'note' => $routerData['note'],
                'user_id' => $user->id,
                'zone_id' => $zoneId,
                'monthly_isp_cost' => $routerData['monthly_isp_cost'],
                'logo' => $routerData['logo'],
                'voucher_template_id' => $templateId,
            ]);
            $router->password = Crypt::encryptString($routerData['password']);
            if (! $router->app_key) {
                $router->app_key = Str::random(40);
            }
            $router->save();
        }
    }
}
