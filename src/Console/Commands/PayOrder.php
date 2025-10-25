<?php

namespace Secretwebmaster\WncmsEcommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;
use Secretwebmaster\WncmsEcommerce\Services\Managers\ProductManager;

class PayOrder extends Command
{
    protected $signature = 'wncms-ecommerce:pay-order {orderSlug} {paymentGatewaySlug}';
    protected $description = 'Simulate payment notification for testing gateways';

    public function handle()
    {
        $orderSlug = $this->argument('orderSlug');
        $gatewaySlug = $this->argument('paymentGatewaySlug');

        // 1. Locate order
        $order = Order::where('slug', $orderSlug)->first();
        if (!$order) {
            $this->error("❌ Order not found: {$orderSlug}");
            return;
        }

        // 2. Locate gateway
        $gateway = PaymentGateway::where('slug', $gatewaySlug)->first();
        if (!$gateway) {
            $this->error("❌ Payment gateway '{$gatewaySlug}' not found.");
            return;
        }

        // 3. Attach gateway to the order if missing
        if (!$order->payment_gateway_id) {
            $order->update(['payment_gateway_id' => $gateway->id]);
            $this->info("✅ Attached gateway '{$gatewaySlug}' to order.");
        }

        // 4. Build fake payload
        $payload = [
            'order_id'  => $order->slug,
            'trade_id'  => 'SIM-' . strtoupper(uniqid()),
            'amount'    => (float) $order->total_amount,
            'status'    => 'success',
            'timestamp' => now()->timestamp,
        ];

        // 5. Sign payload same as Epusdt::sign()
        ksort($payload);
        $signStr = '';
        foreach ($payload as $key => $val) {
            if ($key !== 'signature' && $val !== '') {
                $signStr .= ($signStr ? '&' : '') . "$key=$val";
            }
        }
        $payload['signature'] = md5($signStr . $gateway->client_secret);

        // 6. Define target URL
        $url = route('api.v1.payment.notify', ['payment_gateway' => $gateway->slug]);
        $this->info("POST → {$url}");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT));

        // 7. Send POST request
        $response = Http::asForm()->post($url, $payload);

        $this->info("Response Status: " . $response->status());
        $this->line("Response Body: " . $response->body());
    }

    public function generateDemoProducts()
    {
        $this->info('Generating demo products and testing pagination...');

        $productModel = wncms()->package('wncms-ecommerce')->model('product');
        $manager = new ProductManager();

        // Generate demo products
        $faker = \Faker\Factory::create('en_US');
        for ($i = 1; $i <= 10; $i++) {
            $productModel::create([
                'name'        => $faker->words(3, true),
                'slug'        => 'demo-' . uniqid(),
                'status'      => 'active',
                'type'        => 'virtual',
                'price'       => $faker->randomFloat(2, 10, 200),
                'stock'       => $faker->numberBetween(1, 50),
                'is_variable' => false,
                'properties'  => ['version' => $faker->randomDigit()],
                'variants'    => ['color' => ['red', 'blue'], 'size' => ['S', 'M', 'L']],
            ]);
        }

        $this->info('✅ 10 demo products created.');

        // Show first page
        $products = $manager->getList(['page_size' => 5]);
        $this->table(
            ['ID', 'Name', 'Price', 'Status'],
            $products->map(fn($p) => [$p->id, $p->name, $p->price, $p->status])->toArray()
        );

        $this->info('✅ Displayed first page (5 products).');
    }
}
