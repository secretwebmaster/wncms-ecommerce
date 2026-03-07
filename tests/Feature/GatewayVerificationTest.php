<?php

namespace Secretwebmaster\WncmsEcommerce\Tests\Feature;

use Illuminate\Http\Request;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;
use Secretwebmaster\WncmsEcommerce\PaymentGateways\Epusdt;
use Secretwebmaster\WncmsEcommerce\PaymentGateways\Paypal;
use Secretwebmaster\WncmsEcommerce\PaymentGateways\Stripe;
use Secretwebmaster\WncmsEcommerce\Tests\TestCase;

class GatewayVerificationTest extends TestCase
{
    public function test_epusdt_verification_requires_valid_signature(): void
    {
        $gateway = new PaymentGateway();
        $gateway->forceFill([
            'slug' => 'epusdt',
            'client_secret' => 'secret-key',
        ]);

        $order = new Order();
        $order->forceFill([
            'id' => 100,
            'slug' => 'ORD-100',
            'status' => 'pending_payment',
        ]);
        $order->setRelation('payment_gateway', $gateway);

        $payload = [
            'order_id' => 'ORD-100',
            'trade_id' => 'TRX-100',
            'status' => 'success',
            'amount' => '10.00',
        ];
        $payload['signature'] = $this->epusdtSign($payload, 'secret-key');
        $request = Request::create('/v1/payment/notify/epusdt', 'POST', $payload);

        $processor = new Epusdt($gateway);
        $result = $processor->verifyCallback($request, $order);

        $this->assertTrue($result['verified']);
        $this->assertSame('verified', $result['message']);
    }

    public function test_stripe_verification_fails_without_webhook_secret(): void
    {
        $gateway = new PaymentGateway();
        $gateway->forceFill([
            'slug' => 'stripe',
            'webhook_secret' => null,
        ]);

        $event = ['id' => 'evt_123', 'type' => 'payment_intent.succeeded'];
        $request = Request::create(
            '/v1/payment/notify/stripe',
            'POST',
            [],
            [],
            [],
            [],
            json_encode($event)
        );
        $request->headers->set('Stripe-Signature', 't=123,v1=abc');

        $processor = new Stripe($gateway);
        $result = $processor->verifyCallback($request);

        $this->assertFalse($result['verified']);
        $this->assertSame('missing webhook secret', $result['message']);
    }

    public function test_paypal_capture_rejects_token_order_mismatch(): void
    {
        $gateway = new PaymentGateway();
        $gateway->forceFill([
            'id' => 10,
            'slug' => 'paypal',
            'client_id' => 'cid',
            'client_secret' => 'csecret',
        ]);

        $order = new Order();
        $order->forceFill([
            'id' => 321,
            'slug' => 'ORD-321',
            'status' => 'pending_payment',
            'payment_gateway_id' => 10,
            'tracking_code' => 'EXPECTED_TOKEN',
            'total_amount' => 99.99,
            'currency' => 'USD',
        ]);

        $request = Request::create('/orders/ORD-321/paypal/return', 'GET', [
            'token' => 'OTHER_TOKEN',
        ]);

        $processor = new Paypal($gateway);
        $result = $processor->capture($order, $request);

        $this->assertFalse((bool) ($result['success'] ?? false));
    }

    protected function epusdtSign(array $payload, string $secret): string
    {
        ksort($payload);
        $parts = [];
        foreach ($payload as $key => $value) {
            if ($key === 'signature' || $value === '') {
                continue;
            }
            $parts[] = $key . '=' . $value;
        }

        return md5(implode('&', $parts) . $secret);
    }
}
