<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentGateway::updateOrCreate(
            ['name' => 'Cryptomus'],
            [
                'class' => \App\Gateway\CryptomusGateway::class,
                'data' => [
                    'merchant_id' => 'test_merchant',
                    'api_key' => 'test_api_key',
                    'network' => 'USDT_TRC20',
                    'test_mode' => true,
                ],
                'is_active' => false, // Disabled by default until configured
            ]
        );

        PaymentGateway::updateOrCreate(
            ['name' => 'PayStation'],
            [
                'class' => \App\Gateway\PayStationGateway::class,
                'data' => [
                    'merchant_id' => '1234-xxxx',
                    'password' => 'secret',
                    'base_url' => 'https://www.paystation.com.bd',
                    'test_mode' => true,
                ],
                'is_active' => false, // Disabled by default until configured
            ]
        );
    }
}
