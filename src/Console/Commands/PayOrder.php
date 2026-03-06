<?php

namespace Secretwebmaster\WncmsEcommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;

class PayOrder extends Command
{
    protected $signature = 'wncms-ecommerce:pay-order {orderSlug} {paymentGatewaySlug}';
    protected $description = 'Simulate payment notification for testing gateways';

    public function handle()
    {
        $orderSlug = $this->argument('orderSlug');
        $gatewaySlug = $this->argument('paymentGatewaySlug');

        $order = Order::where('slug', $orderSlug)->first();
        if (!$order) {
            $this->error("Order not found: {$orderSlug}");
            return self::FAILURE;
        }

        $gateway = PaymentGateway::where('slug', $gatewaySlug)->first();
        if (!$gateway) {
            $this->error("Payment gateway '{$gatewaySlug}' not found.");
            return self::FAILURE;
        }

        if (!$order->payment_gateway_id) {
            $order->update(['payment_gateway_id' => $gateway->id]);
        }

        $payload = [
            'order_id' => $order->slug,
            'trade_id' => 'SIM-' . strtoupper(uniqid()),
            'amount' => (float) $order->total_amount,
            'status' => 'success',
            'timestamp' => now()->timestamp,
        ];

        ksort($payload);
        $signStr = '';
        foreach ($payload as $key => $val) {
            if ($val !== '') {
                $signStr .= ($signStr ? '&' : '') . "$key=$val";
            }
        }
        $payload['signature'] = md5($signStr . $gateway->client_secret);

        $url = route('api.v1.payment.notify.gateway', ['payment_gateway' => $gateway->slug]);

        $response = Http::asForm()->post($url, $payload);

        $this->info("Response Status: {$response->status()}");
        $this->line("Response Body: {$response->body()}");

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
