<?php

namespace Database\Seeders;

use App\Gateway\CryptomusGateway;
use App\Gateway\PayStationGateway;
use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates payment gateway entries with placeholder credentials.
     */
    public function run(): void
    {
        $this->command->info('💳 Creating payment gateways...');

        // Cryptomus Gateway
        PaymentGateway::firstOrCreate(
            ['name' => 'Cryptomus'],
            [
                'class' => CryptomusGateway::class,
                'data' => [
                    'merchant_id' => '',
                    'api_key' => '',
                    'network' => 'USDT_TRC20',
                    'test_mode' => true,
                ],
                'is_active' => false, // Disabled by default until configured
            ]
        );

        // PayStation Gateway
        PaymentGateway::firstOrCreate(
            ['name' => 'PayStation'],
            [
                'class' => PayStationGateway::class,
                'data' => [
                    'merchant_id' => '',
                    'password' => '',
                    'base_url' => 'https://api.paystation.com.bd',
                ],
                'is_active' => false, // Disabled by default until configured
            ]
        );

        $this->command->info('✅ Payment gateways created (inactive by default)');
        $this->command->warn('⚠️  Configure gateway credentials in admin panel to activate');
    }
}
