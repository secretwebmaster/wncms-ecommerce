<?php

namespace Secretwebmaster\WncmsEcommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'USDT(TRC20)',
                'slug' => 'epusdt',
                'status' => 'active',
                'type' => 'redirect',
                'account_id' => null,
                'client_id' => null,
                'client_secret' => null,
                'endpoint' => null,
                'attributes' => [
                    'callback_url' => route('api.v1.payment.notify', ['payment_gateway' => 'epusdt']),
                ],
                'description' => 'USDT(TRC20) 支付網關整合',
            ],
        ];

        foreach ($gateways as $data) {
            PaymentGateway::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
