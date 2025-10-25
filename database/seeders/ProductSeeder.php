<?php

namespace Secretwebmaster\WncmsEcommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Secretwebmaster\WncmsEcommerce\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => '高級主題',
                'slug' => 'premium-theme',
                'status' => 'active',
                'type' => 'virtual',
                'price' => 49.00,
                'stock' => 100,
                'is_variable' => false,
                'properties' => ['版本' => '1.0', '分類' => '主題'],
                'variants' => null,
            ],
            [
                'name' => '外掛套裝',
                'slug' => 'plugin-bundle',
                'status' => 'active',
                'type' => 'virtual',
                'price' => 79.00,
                'stock' => 100,
                'is_variable' => false,
                'properties' => ['版本' => '1.0', '分類' => '外掛'],
                'variants' => null,
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
