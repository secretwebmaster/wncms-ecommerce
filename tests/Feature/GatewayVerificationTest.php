<?php

namespace Secretwebmaster\WncmsEcommerce\Tests\Feature;

use Illuminate\Http\Request;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;
use Secretwebmaster\WncmsEcommerce\PaymentGateways\Ecpay;
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

    public function test_ecpay_verification_passes_with_valid_check_mac(): void
    {
        $gateway = new PaymentGateway();
        $gateway->forceFill([
            'slug' => 'ecpay',
            'client_id' => '3002607',
            'client_secret' => '5294y06JbISpM5x9',
            'webhook_secret' => 'v77hoKGq4kWxNNIS',
        ]);

        $order = new Order();
        $order->forceFill([
            'id' => 201,
            'slug' => 'ORD-201',
            'status' => 'pending_payment',
            'tracking_code' => 'WNO201',
            'total_amount' => 100,
        ]);

        $payload = [
            'MerchantID' => '3002607',
            'MerchantTradeNo' => 'WNO201',
            'TradeNo' => '2301010000001',
            'TradeAmt' => '100',
            'RtnCode' => '1',
            'RtnMsg' => 'Succeeded',
            'PaymentDate' => '2026/03/06 20:00:00',
            'PaymentType' => 'Credit_CreditCard',
        ];
        $payload['CheckMacValue'] = $this->ecpayCheckMac(
            $payload,
            '5294y06JbISpM5x9',
            'v77hoKGq4kWxNNIS'
        );

        $request = Request::create('/v1/payment/notify/ecpay', 'POST', $payload);

        $processor = new Ecpay($gateway);
        $result = $processor->verifyCallback($request, $order);

        $this->assertTrue($result['verified']);
        $this->assertSame('verified', $result['message']);
    }

    public function test_ecpay_verification_fails_with_invalid_check_mac(): void
    {
        $gateway = new PaymentGateway();
        $gateway->forceFill([
            'slug' => 'ecpay',
            'client_id' => '3002607',
            'client_secret' => '5294y06JbISpM5x9',
            'webhook_secret' => 'v77hoKGq4kWxNNIS',
        ]);

        $order = new Order();
        $order->forceFill([
            'id' => 202,
            'slug' => 'ORD-202',
            'status' => 'pending_payment',
            'tracking_code' => 'WNO202',
            'total_amount' => 200,
        ]);

        $request = Request::create('/v1/payment/notify/ecpay', 'POST', [
            'MerchantID' => '3002607',
            'MerchantTradeNo' => 'WNO202',
            'TradeNo' => '2301010000002',
            'TradeAmt' => '200',
            'RtnCode' => '1',
            'CheckMacValue' => 'INVALIDMAC',
        ]);

        $processor = new Ecpay($gateway);
        $result = $processor->verifyCallback($request, $order);

        $this->assertFalse($result['verified']);
        $this->assertSame('check mac mismatch', $result['message']);
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

    protected function ecpayCheckMac(array $payload, string $hashKey, string $hashIv): string
    {
        unset($payload['CheckMacValue']);
        ksort($payload);

        $pairs = [];
        foreach ($payload as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        $raw = 'HashKey=' . $hashKey . '&' . implode('&', $pairs) . '&HashIV=' . $hashIv;
        $encoded = strtolower(urlencode($raw));
        $encoded = str_replace(
            ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
            ['-', '_', '.', '!', '*', '(', ')'],
            $encoded
        );

        return strtoupper(hash('sha256', $encoded));
    }
}
