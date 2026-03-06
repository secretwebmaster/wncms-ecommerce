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
                'driver' => 'epusdt',
                'account_id' => null,
                'client_id' => null,
                'client_secret' => null,
                'webhook_secret' => null,
                'endpoint' => null,
                'return_url' => null,
                'currency' => 'USD',
                'is_sandbox' => true,
                'attributes' => [],
                'description' => 'USDT(TRC20) 支付網關整合',
            ],
            [
                'name' => 'PayPal',
                'slug' => 'paypal',
                'status' => 'active',
                'type' => 'redirect',
                'driver' => 'paypal',
                'account_id' => null,
                'client_id' => null,
                'client_secret' => null,
                'webhook_secret' => null,
                'endpoint' => 'https://api-m.paypal.com',
                'return_url' => null,
                'currency' => 'USD',
                'is_sandbox' => false,
                'attributes' => [],
                'description' => 'PayPal payment gateway',
            ],
            [
                'name' => 'PayPal Sandbox',
                'slug' => 'paypal_sandbox',
                'status' => 'active',
                'type' => 'redirect',
                'driver' => 'paypal',
                'account_id' => null,
                'client_id' => null,
                'client_secret' => null,
                'webhook_secret' => null,
                'endpoint' => 'https://api-m.sandbox.paypal.com',
                'return_url' => null,
                'currency' => 'USD',
                'is_sandbox' => true,
                'attributes' => [],
                'description' => 'PayPal sandbox payment gateway',
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'status' => 'active',
                'type' => 'redirect',
                'driver' => 'stripe',
                'account_id' => null,
                'client_id' => null,
                'client_secret' => null,
                'webhook_secret' => null,
                'endpoint' => 'https://api.stripe.com',
                'return_url' => null,
                'currency' => 'USD',
                'is_sandbox' => false,
                'attributes' => [],
                'description' => 'Stripe payment gateway',
            ],
            [
                'name' => 'Stripe Test',
                'slug' => 'stripe_test',
                'status' => 'active',
                'type' => 'redirect',
                'driver' => 'stripe',
                'account_id' => null,
                'client_id' => null,
                'client_secret' => null,
                'webhook_secret' => null,
                'endpoint' => 'https://api.stripe.com',
                'return_url' => null,
                'currency' => 'USD',
                'is_sandbox' => true,
                'attributes' => [],
                'description' => 'Stripe test payment gateway',
            ],
        ];

        foreach ($gateways as $data) {
            $paymentGateway = PaymentGateway::firstOrNew(['slug' => $data['slug']]);

            $paymentGateway->name = $data['name'];
            $paymentGateway->status = $data['status'];
            $paymentGateway->type = $data['type'];
            $paymentGateway->driver = $data['driver'];
            $paymentGateway->description = $data['description'];
            $paymentGateway->currency = $paymentGateway->currency ?: $data['currency'];
            $paymentGateway->is_sandbox = $paymentGateway->exists
                ? (bool) $paymentGateway->is_sandbox
                : (bool) $data['is_sandbox'];
            $paymentGateway->endpoint = $paymentGateway->endpoint ?: $data['endpoint'];
            $paymentGateway->return_url = $paymentGateway->return_url ?: $data['return_url'];
            $paymentGateway->account_id = $paymentGateway->account_id ?: $data['account_id'];
            $paymentGateway->client_id = $paymentGateway->client_id ?: $data['client_id'];
            $paymentGateway->client_secret = $paymentGateway->client_secret ?: $data['client_secret'];
            $paymentGateway->webhook_secret = $paymentGateway->webhook_secret ?: $data['webhook_secret'];

            if (empty($paymentGateway->attributes)) {
                $paymentGateway->attributes = $data['attributes'];
            }

            $paymentGateway->save();
        }
    }
}
