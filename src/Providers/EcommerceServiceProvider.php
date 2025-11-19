<?php

namespace Secretwebmaster\WncmsEcommerce\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

use Secretwebmaster\WncmsEcommerce\Models\Product;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\OrderItem;
use Secretwebmaster\WncmsEcommerce\Models\Transaction;
use Secretwebmaster\WncmsEcommerce\Models\Price;
use Secretwebmaster\WncmsEcommerce\Models\Discount;
use Secretwebmaster\WncmsEcommerce\Models\Credit;
use Secretwebmaster\WncmsEcommerce\Models\CreditTransaction;
use Secretwebmaster\WncmsEcommerce\Models\Card;
use Secretwebmaster\WncmsEcommerce\Models\Subscription;
use Secretwebmaster\WncmsEcommerce\Models\Plan;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;

use Secretwebmaster\WncmsEcommerce\Services\Managers\OrderManager;
use Secretwebmaster\WncmsEcommerce\Services\Managers\PlanManager;

use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\OrderController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\ProductController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\PlanController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\TransactionController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\SubscriptionController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\CardController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\CreditController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\PaymentGatewayController;
use Secretwebmaster\WncmsEcommerce\Services\Managers\ProductManager;

use Secretwebmaster\WncmsEcommerce\Database\Seeders\PaymentGatewaySeeder;
use Secretwebmaster\WncmsEcommerce\Database\Seeders\PlanSeeder;
use Secretwebmaster\WncmsEcommerce\Database\Seeders\ProductSeeder;
use Wncms\Facades\MacroableModels;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register singleton managers (frontend cache usage)
        $this->app->singleton('order-manager', fn() => new OrderManager());
        $this->app->singleton('plan-manager', fn() => new PlanManager());
        $this->app->singleton('product-manager', fn() => new ProductManager());

        AliasLoader::getInstance()->alias('PlanManager', PlanManager::class);
        AliasLoader::getInstance()->alias('OrderManager', OrderManager::class);
        AliasLoader::getInstance()->alias('ProductManager', ProductManager::class);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/wncms-ecommerce.php',
            'wncms-ecommerce'
        );

        // Build permissions dynamically
        $permissions = [];
        $models = [
            'product',
            'order',
            'order_item',
            'transaction',
            'price',
            'discount',
            'credit',
            'credit_transaction',
            'card',
            'subscription',
            'plan',
            'payment_gateway',
        ];

        $suffixes = [
            'list',
            'index',
            'show',
            'create',
            'clone',
            'bulk_create',
            'edit',
            'bulk_edit',
            'delete',
            'bulk_delete',
        ];

        foreach ($models as $model) {
            foreach ($suffixes as $suffix) {
                $permissions[] = "{$model}_{$suffix}";
            }
        }

        wncms()->registerPackage('wncms-ecommerce', [
            'base' => __DIR__ . '/../../',

            'info' => [
                'name' => [
                    'en'    => 'E-commerce',
                    'zh_TW' => '電子商務',
                    'zh_CN' => '电子商务',
                    'ja'    => 'Eコマース',
                ],
                'description' => [
                    'en'    => 'Manage products, orders, transactions, payments, and subscriptions.',
                    'zh_TW' => '管理商品、訂單、交易、付款與訂閱。',
                    'zh_CN' => '管理商品、订单、交易、支付与订阅。',
                    'ja'    => '商品、注文、取引、支払い、サブスクリプションを管理します。',
                ],
                'version' => '1.0.0',
                'author'  => 'Secretwebmaster',
                'icon'    => 'fa-solid fa-cart-shopping',
            ],

            'models' => [
                'product'           => Product::class,
                'order'             => Order::class,
                'order_item'        => OrderItem::class,
                'transaction'       => Transaction::class,
                'price'             => Price::class,
                'discount'          => Discount::class,
                'credit'            => Credit::class,
                'credit_transaction' => CreditTransaction::class,
                'card'              => Card::class,
                'subscription'      => Subscription::class,
                'plan'              => Plan::class,
                'payment_gateway'   => PaymentGateway::class,
            ],

            'managers' => [
                'order' => OrderManager::class,
                'plan'  => PlanManager::class,
                'product' => ProductManager::class
            ],

            'controllers' => [
                'order'           => OrderController::class,
                'product'         => ProductController::class,
                'plan'            => PlanController::class,
                'transaction'     => TransactionController::class,
                'subscription'    => SubscriptionController::class,
                'card'            => CardController::class,
                'credit'          => CreditController::class,
                'payment_gateway' => PaymentGatewayController::class,
            ],

            'menus' => [
                [
                    'title' => ['en' => 'Products', 'zh_TW' => '商品', 'zh_CN' => '商品', 'ja' => '商品'],
                    'icon' => 'fa-solid fa-box',
                    'permission' => 'product_index',
                    'items' => [
                        ['name' => ['en' => 'Product List', 'zh_TW' => '商品列表', 'zh_CN' => '商品列表', 'ja' => '商品一覧'], 'route' => 'products.index', 'permission' => 'product_index'],
                        // ['name' => ['en' => 'Create Product', 'zh_TW' => '新增商品', 'zh_CN' => '新增商品', 'ja' => '商品を追加'], 'route' => 'products.create', 'permission' => 'product_create'],
                        ['name' => ['en' => 'Discounts', 'zh_TW' => '折扣', 'zh_CN' => '折扣', 'ja' => '割引'], 'route' => 'discounts.index', 'permission' => 'discount_index'],
                        ['name' => ['en' => 'Prices', 'zh_TW' => '價格', 'zh_CN' => '价格', 'ja' => '価格'], 'route' => 'prices.index', 'permission' => 'price_index'],
                        ['name' => ['en' => 'Coupons', 'zh_TW' => '優惠券', 'zh_CN' => '优惠券', 'ja' => 'クーポン'], 'route' => 'coupons.index', 'permission' => 'discount_index'],
                    ],
                ],
                [
                    'title' => ['en' => 'Orders', 'zh_TW' => '訂單', 'zh_CN' => '订单', 'ja' => '注文'],
                    'icon' => 'fa-solid fa-bag-shopping',
                    'permission' => 'order_index',
                    'items' => [
                        ['name' => ['en' => 'Order List', 'zh_TW' => '訂單列表', 'zh_CN' => '订单列表', 'ja' => '注文一覧'], 'route' => 'orders.index', 'permission' => 'order_index'],
                        ['name' => ['en' => 'Order Items', 'zh_TW' => '訂單項目', 'zh_CN' => '订单项目', 'ja' => '注文項目'], 'route' => 'order_items.index', 'permission' => 'order_item_index'],
                    ],
                ],
                [
                    'title' => ['en' => 'Transactions', 'zh_TW' => '交易', 'zh_CN' => '交易', 'ja' => '取引'],
                    'icon' => 'fa-solid fa-file-invoice-dollar',
                    'permission' => 'transaction_index',
                    'items' => [
                        ['name' => ['en' => 'Transaction List', 'zh_TW' => '交易列表', 'zh_CN' => '交易列表', 'ja' => '取引一覧'], 'route' => 'transactions.index', 'permission' => 'transaction_index'],
                        ['name' => ['en' => 'Redeem Codes', 'zh_TW' => '卡密', 'zh_CN' => '卡密', 'ja' => '引換コード'], 'route' => 'cards.index', 'permission' => 'card_index'],
                        ['name' => ['en' => 'Payment Gateways', 'zh_TW' => '支付閘道', 'zh_CN' => '支付网关', 'ja' => '決済ゲートウェイ'], 'route' => 'payment_gateways.index', 'permission' => 'payment_gateway_index'],
                    ],
                ],
                [
                    'title' => ['en' => 'Subscriptions', 'zh_TW' => '訂閱', 'zh_CN' => '订阅', 'ja' => 'サブスクリプション'],
                    'icon' => 'fa-solid fa-repeat',
                    'permission' => 'subscription_index',
                    'items' => [
                        ['name' => ['en' => 'Subscription List', 'zh_TW' => '訂閱列表', 'zh_CN' => '订阅列表', 'ja' => 'サブスクリプション一覧'], 'route' => 'subscriptions.index', 'permission' => 'subscription_index'],
                        ['name' => ['en' => 'Plans', 'zh_TW' => '方案', 'zh_CN' => '方案', 'ja' => 'プラン'], 'route' => 'plans.index', 'permission' => 'plan_index'],
                    ],
                ],
                [
                    'title' => ['en' => 'Credits', 'zh_TW' => '點數', 'zh_CN' => '点数', 'ja' => 'クレジット'],
                    'icon' => 'fa-solid fa-coins',
                    'permission' => 'credit_index',
                    'items' => [
                        ['name' => ['en' => 'Credits', 'zh_TW' => '點數記錄', 'zh_CN' => '点数记录', 'ja' => 'クレジット記録'], 'route' => 'credits.index', 'permission' => 'credit_index'],
                        ['name' => ['en' => 'Credit Transactions', 'zh_TW' => '點數交易', 'zh_CN' => '点数交易', 'ja' => 'クレジット取引'], 'route' => 'credit_transactions.index', 'permission' => 'credit_transaction_index'],
                    ],
                ],
            ],

            'permissions' => $permissions,

            'seeders' => [
                PaymentGatewaySeeder::class,
                ProductSeeder::class,
                PlanSeeder::class,
            ],
        ]);
    }

    public function boot(): void
    {
        // Load package resources
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'wncms-ecommerce');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'wncms-ecommerce');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Secretwebmaster\WncmsEcommerce\Console\Commands\PayOrder::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../../config/wncms-ecommerce.php' => config_path('wncms-ecommerce.php'),
        ], 'wncms-ecommerce-config');

        // Load routes if they exist
        foreach (['web', 'api'] as $file) {
            $path = __DIR__ . "/../../routes/{$file}.php";
            if (file_exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }

        // Register package metadata with WNCMS
        // wncms()->registerPackage('wncms-ecommerce', [
        //     'base' => __DIR__ . '/../../',

        //     'info' => [
        //         'name' => [
        //             'en'    => 'E-commerce',
        //             'zh_TW' => '電子商務',
        //             'zh_CN' => '电子商务',
        //             'ja'    => 'Eコマース',
        //         ],
        //         'description' => [
        //             'en'    => 'Manage products, orders, transactions, payments, and subscriptions.',
        //             'zh_TW' => '管理商品、訂單、交易、付款與訂閱。',
        //             'zh_CN' => '管理商品、订单、交易、支付与订阅。',
        //             'ja'    => '商品、注文、取引、支払い、サブスクリプションを管理します。',
        //         ],
        //         'version' => '1.0.0',
        //         'author'  => 'Secretwebmaster',
        //         'icon'    => 'fa-solid fa-cart-shopping',
        //     ],

        //     'models' => [
        //         'product'           => Product::class,
        //         'order'             => Order::class,
        //         'order_item'        => OrderItem::class,
        //         'transaction'       => Transaction::class,
        //         'price'             => Price::class,
        //         'discount'          => Discount::class,
        //         'credit'            => Credit::class,
        //         'credit_transaction' => CreditTransaction::class,
        //         'card'              => Card::class,
        //         'subscription'      => Subscription::class,
        //         'plan'              => Plan::class,
        //         'payment_gateway'   => PaymentGateway::class,
        //     ],

        //     'managers' => [
        //         'order' => OrderManager::class,
        //         'plan'  => PlanManager::class,
        //         'product' => ProductManager::class
        //     ],

        //     'controllers' => [
        //         'order'           => OrderController::class,
        //         'product'         => ProductController::class,
        //         'plan'            => PlanController::class,
        //         'transaction'     => TransactionController::class,
        //         'subscription'    => SubscriptionController::class,
        //         'card'            => CardController::class,
        //         'credit'          => CreditController::class,
        //         'payment_gateway' => PaymentGatewayController::class,
        //     ],

        //     'menus' => [
        //         [
        //             'title' => ['en' => 'Products', 'zh_TW' => '商品', 'zh_CN' => '商品', 'ja' => '商品'],
        //             'icon' => 'fa-solid fa-box',
        //             'permission' => 'product_index',
        //             'items' => [
        //                 ['name' => ['en' => 'Product List', 'zh_TW' => '商品列表', 'zh_CN' => '商品列表', 'ja' => '商品一覧'], 'route' => 'products.index', 'permission' => 'product_index'],
        //                 // ['name' => ['en' => 'Create Product', 'zh_TW' => '新增商品', 'zh_CN' => '新增商品', 'ja' => '商品を追加'], 'route' => 'products.create', 'permission' => 'product_create'],
        //                 ['name' => ['en' => 'Discounts', 'zh_TW' => '折扣', 'zh_CN' => '折扣', 'ja' => '割引'], 'route' => 'discounts.index', 'permission' => 'discount_index'],
        //                 ['name' => ['en' => 'Prices', 'zh_TW' => '價格', 'zh_CN' => '价格', 'ja' => '価格'], 'route' => 'prices.index', 'permission' => 'price_index'],
        //                 ['name' => ['en' => 'Coupons', 'zh_TW' => '優惠券', 'zh_CN' => '优惠券', 'ja' => 'クーポン'], 'route' => 'coupons.index', 'permission' => 'discount_index'],
        //             ],
        //         ],
        //         [
        //             'title' => ['en' => 'Orders', 'zh_TW' => '訂單', 'zh_CN' => '订单', 'ja' => '注文'],
        //             'icon' => 'fa-solid fa-bag-shopping',
        //             'permission' => 'order_index',
        //             'items' => [
        //                 ['name' => ['en' => 'Order List', 'zh_TW' => '訂單列表', 'zh_CN' => '订单列表', 'ja' => '注文一覧'], 'route' => 'orders.index', 'permission' => 'order_index'],
        //                 ['name' => ['en' => 'Order Items', 'zh_TW' => '訂單項目', 'zh_CN' => '订单项目', 'ja' => '注文項目'], 'route' => 'order_items.index', 'permission' => 'order_item_index'],
        //             ],
        //         ],
        //         [
        //             'title' => ['en' => 'Transactions', 'zh_TW' => '交易', 'zh_CN' => '交易', 'ja' => '取引'],
        //             'icon' => 'fa-solid fa-file-invoice-dollar',
        //             'permission' => 'transaction_index',
        //             'items' => [
        //                 ['name' => ['en' => 'Transaction List', 'zh_TW' => '交易列表', 'zh_CN' => '交易列表', 'ja' => '取引一覧'], 'route' => 'transactions.index', 'permission' => 'transaction_index'],
        //                 ['name' => ['en' => 'Redeem Codes', 'zh_TW' => '卡密', 'zh_CN' => '卡密', 'ja' => '引換コード'], 'route' => 'cards.index', 'permission' => 'card_index'],
        //                 ['name' => ['en' => 'Payment Gateways', 'zh_TW' => '支付閘道', 'zh_CN' => '支付网关', 'ja' => '決済ゲートウェイ'], 'route' => 'payment_gateways.index', 'permission' => 'payment_gateway_index'],
        //             ],
        //         ],
        //         [
        //             'title' => ['en' => 'Subscriptions', 'zh_TW' => '訂閱', 'zh_CN' => '订阅', 'ja' => 'サブスクリプション'],
        //             'icon' => 'fa-solid fa-repeat',
        //             'permission' => 'subscription_index',
        //             'items' => [
        //                 ['name' => ['en' => 'Subscription List', 'zh_TW' => '訂閱列表', 'zh_CN' => '订阅列表', 'ja' => 'サブスクリプション一覧'], 'route' => 'subscriptions.index', 'permission' => 'subscription_index'],
        //                 ['name' => ['en' => 'Plans', 'zh_TW' => '方案', 'zh_CN' => '方案', 'ja' => 'プラン'], 'route' => 'plans.index', 'permission' => 'plan_index'],
        //             ],
        //         ],
        //         [
        //             'title' => ['en' => 'Credits', 'zh_TW' => '點數', 'zh_CN' => '点数', 'ja' => 'クレジット'],
        //             'icon' => 'fa-solid fa-coins',
        //             'permission' => 'credit_index',
        //             'items' => [
        //                 ['name' => ['en' => 'Credits', 'zh_TW' => '點數記錄', 'zh_CN' => '点数记录', 'ja' => 'クレジット記録'], 'route' => 'credits.index', 'permission' => 'credit_index'],
        //                 ['name' => ['en' => 'Credit Transactions', 'zh_TW' => '點數交易', 'zh_CN' => '点数交易', 'ja' => 'クレジット取引'], 'route' => 'credit_transactions.index', 'permission' => 'credit_transaction_index'],
        //             ],
        //         ],
        //     ],

        //     'permissions' => $permissions,

        //     'seeders' => [
        //         PaymentGatewaySeeder::class,
        //         ProductSeeder::class,
        //         PlanSeeder::class,
        //     ],
        // ]);

        // add relationship to user model
        try {
            $userModel = wncms()->getModelClass('user');

            if (class_exists($userModel)) {

                // Relationship: credits()
                MacroableModels::addMacro($userModel, 'credits', function () {
                    return $this->hasMany(wncms()->getModelClass('credit'));
                });

                // Relationship: subscriptions()
                MacroableModels::addMacro($userModel, 'subscriptions', function () {
                    return $this->hasMany(wncms()->getModelClass('subscription'));
                });

                // Relationship: orders()
                MacroableModels::addMacro($userModel, 'orders', function () {
                    return $this->hasMany(wncms()->getModelClass('order'));
                });

                // Accessor: balance
                MacroableModels::addMacro($userModel, 'getBalanceAttribute', function () {
                    $this->loadMissing('credits');
                    return $this->credits->where('type', 'balance')->first()->amount ?? 0;
                });

                // Method: getCredit($type)
                MacroableModels::addMacro($userModel, 'getCredit', function ($type) {
                    $this->loadMissing('credits');
                    return $this->credits->where('type', $type)->first()->amount ?? 0;
                });

                // Method: getPlans()
                MacroableModels::addMacro($userModel, 'getPlans', function () {
                    $this->loadMissing('subscriptions');
                    return $this->subscriptions->map(function ($subscription) {
                        return $subscription->plan;
                    })->unique();
                });

                // Method: hasPlan($planId = null)
                MacroableModels::addMacro($userModel, 'hasPlan', function ($planId = null) {
                    if (!$planId) {
                        $this->loadMissing('subscriptions');
                        return $this->subscriptions->where('status', 'active')->count() > 0;
                    }
                    return $this->getPlans()->contains('id', $planId);
                });
            }
        } catch (\Throwable $e) {
            info('Ecommerce macros not registered: ' . $e->getMessage());
        }
    }
}
