<?php

namespace Secretwebmaster\WncmsEcommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;
use Secretwebmaster\WncmsEcommerce\Services\Managers\ProductManager;

class PayOrder extends Command
{
    protected $signature = 'wncms-ecommerce:pay-order {slug}';
    protected $description = 'Similate payment';

    public function handle()
    {
        $slug = $this->argument('slug');
        $order = Order::where('slug',$slug)->first();
        if (!$order) {
            $this->error("Order not found: {$slug}");
            return;
        }

        $gateway = PaymentGateway::where('slug', 'epusdt')->first();
        if (!$gateway) {
            $this->error("Payment gateway 'epusdt' not found.");
            return;
        }

        // Build payload
        $payload = [
            'order_id'      => $order->slug,
            'trade_id'      => 'SIM-' . strtoupper(uniqid()),
            'amount'        => (float) $order->total_amount,
            'status'        => 'success',
            'timestamp'     => now()->timestamp,
        ];

        // Sign same as Epusdt::sign()
        ksort($payload);
        $signStr = '';
        foreach ($payload as $key => $val) {
            if ($key !== 'signature' && $val !== '') {
                $signStr .= ($signStr ? '&' : '') . "$key=$val";
            }
        }
        $payload['signature'] = md5($signStr . $gateway->client_secret);

        $url = route('api.v1.payment.notify', ['payment_gateway' => 'epusdt']);
        $this->info("POST → $url");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT));

        // Send POST request
        $response = Http::asForm()->post($url, $payload);

        $this->info("Response Status: " . $response->status());
        $this->line("Response Body: " . $response->body());
    }

    public function generateDemoProducts()
    {
        $this->info('Generating demo products and testing pagination...');
    
        $productModel = wncms()->package('wncms-ecommerce')->model('product');
        $manager = new ProductManager();
    
        // 1. Generate 10 demo products
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
    
        // 2. Get paginated list
        $products = $manager->getList(['page_size' => 5]);
    
        $this->table(
            ['ID', 'Name', 'Price', 'Status'],
            $products->map(fn($p) => [$p->id, $p->name, $p->price, $p->status])->toArray()
        );
    
        $this->info('✅ Displayed first page (5 products).');
    }
    
}
