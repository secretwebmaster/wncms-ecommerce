<?php

namespace Secretwebmaster\WncmsEcommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Secretwebmaster\WncmsEcommerce\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        info('Seeding membership plans (Silver / Gold / Diamond) with multiple price durations...');

        $plans = [
            [
                'name' => '白銀會員',
                'slug' => 'silver',
                'description' => '入門級會員方案，提供基本功能與標準下載速度。',
                'status' => 'active',
                'free_trial_duration' => 0,
                'prices' => [
                    ['amount' => 5.00,   'duration' => 1,  'duration_unit' => 'day'],
                    ['amount' => 29.00,  'duration' => 1,  'duration_unit' => 'month'],
                    ['amount' => 79.00,  'duration' => 3,  'duration_unit' => 'month'],
                    ['amount' => 249.00, 'duration' => 1,  'duration_unit' => 'year'],
                ],
            ],
            [
                'name' => '黃金會員',
                'slug' => 'gold',
                'description' => '進階會員方案，提供更快下載與更多專屬資源。',
                'status' => 'active',
                'free_trial_duration' => 3,
                'prices' => [
                    ['amount' => 9.00,   'duration' => 1,  'duration_unit' => 'day'],
                    ['amount' => 49.00,  'duration' => 1,  'duration_unit' => 'month'],
                    ['amount' => 129.00, 'duration' => 3,  'duration_unit' => 'month'],
                    ['amount' => 399.00, 'duration' => 1,  'duration_unit' => 'year'],
                ],
            ],
            [
                'name' => '鑽石會員',
                'slug' => 'diamond',
                'description' => '最高級會員方案，享受無限下載、專屬客服與優先支援。',
                'status' => 'active',
                'free_trial_duration' => 7,
                'prices' => [
                    ['amount' => 15.00,  'duration' => 1,  'duration_unit' => 'day'],
                    ['amount' => 79.00,  'duration' => 1,  'duration_unit' => 'month'],
                    ['amount' => 199.00, 'duration' => 3,  'duration_unit' => 'month'],
                    ['amount' => 699.00, 'duration' => 1,  'duration_unit' => 'year'],
                ],
            ],
        ];

        foreach ($plans as $data) {
            $prices = $data['prices'];
            unset($data['prices']);

            $plan = Plan::updateOrCreate(['slug' => $data['slug']], $data);

            foreach ($prices as $price) {
                $plan->prices()->updateOrCreate(
                    [
                        'duration'      => $price['duration'],
                        'duration_unit' => $price['duration_unit'],
                    ],
                    [
                        'amount'        => $price['amount'],
                        'is_lifetime'   => false,
                        'attributes'    => null,
                        'stock'         => 0,
                    ]
                );
            }
        }
    }
}
