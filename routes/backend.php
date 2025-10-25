<?php

use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\PlanController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\PriceController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\ProductController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\DiscountController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\CreditController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\CreditTransactionController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\CardController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\OrderController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\OrderItemController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\TransactionController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\SubscriptionController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend\PaymentGatewayController;

Route::prefix('panel')->middleware(['auth', 'is_installed', 'has_website'])->group(function () {

    //payment_gateway
    Route::get('payment_gateways', [PaymentGatewayController::class, 'index'])->middleware('can:payment_gateway_index')->name('payment_gateways.index');
    Route::get('payment_gateways/create', [PaymentGatewayController::class, 'create'])->middleware('can:payment_gateway_create')->name('payment_gateways.create');
    Route::get('payment_gateways/create/{id}', [PaymentGatewayController::class, 'create'])->middleware('can:payment_gateway_clone')->name('payment_gateways.clone');
    Route::get('payment_gateways/{id}/edit', [PaymentGatewayController::class, 'edit'])->middleware('can:payment_gateway_edit')->name('payment_gateways.edit');
    Route::post('payment_gateways/store', [PaymentGatewayController::class, 'store'])->middleware('can:payment_gateway_create')->name('payment_gateways.store');
    Route::patch('payment_gateways/{id}', [PaymentGatewayController::class, 'update'])->middleware('can:payment_gateway_edit')->name('payment_gateways.update');
    Route::delete('payment_gateways/{id}', [PaymentGatewayController::class, 'destroy'])->middleware('can:payment_gateway_delete')->name('payment_gateways.destroy');
    Route::post('payment_gateways/bulk_delete', [PaymentGatewayController::class, 'bulk_delete'])->middleware('can:payment_gateway_bulk_delete')->name('payment_gateways.bulk_delete');


    // Plan
    Route::get('plans', [PlanController::class, 'index'])->middleware('can:plan_index')->name('plans.index');
    Route::get('plans/create', [PlanController::class, 'create'])->middleware('can:plan_create')->name('plans.create');
    Route::get('plans/create/{id}', [PlanController::class, 'create'])->middleware('can:plan_clone')->name('plans.clone');
    Route::get('plans/{id}/edit', [PlanController::class, 'edit'])->middleware('can:plan_edit')->name('plans.edit');
    Route::post('plans/store', [PlanController::class, 'store'])->middleware('can:plan_create')->name('plans.store');
    Route::patch('plans/{id}', [PlanController::class, 'update'])->middleware('can:plan_edit')->name('plans.update');
    Route::delete('plans/{id}', [PlanController::class, 'destroy'])->middleware('can:plan_delete')->name('plans.destroy');
    Route::post('plans/bulk_delete', [PlanController::class, 'bulk_delete'])->middleware('can:plan_bulk_delete')->name('plans.bulk_delete');

    // price for model Price
    // Route::get('prices', [PriceController::class, 'index'])->middleware('can:price_index')->name('prices.index');
    // Route::get('prices/create', [PriceController::class, 'create'])->middleware('can:price_create')->name('prices.create');
    // Route::get('prices/create/{id}', [PriceController::class, 'create'])->middleware('can:price_clone')->name('prices.clone');
    // Route::get('prices/{id}/edit', [PriceController::class, 'edit'])->middleware('can:price_edit')->name('prices.edit');
    // Route::post('prices/store', [PriceController::class, 'store'])->middleware('can:price_create')->name('prices.store');
    // Route::patch('prices/{id}', [PriceController::class, 'update'])->middleware('can:price_edit')->name('prices.update');
    // Route::delete('prices/{id}', [PriceController::class, 'destroy'])->middleware('can:price_delete')->name('prices.destroy');
    // Route::post('prices/bulk_delete', [PriceController::class, 'bulk_delete'])->middleware('can:price_bulk_delete')->name('prices.bulk_delete');

    // product for model Product
    Route::get('products', [ProductController::class, 'index'])->middleware('can:product_index')->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->middleware('can:product_create')->name('products.create');
    Route::get('products/create/{id}', [ProductController::class, 'create'])->middleware('can:product_clone')->name('products.clone');
    Route::get('products/{id}/edit', [ProductController::class, 'edit'])->middleware('can:product_edit')->name('products.edit');
    Route::post('products/store', [ProductController::class, 'store'])->middleware('can:product_create')->name('products.store');
    Route::patch('products/{id}', [ProductController::class, 'update'])->middleware('can:product_edit')->name('products.update');
    Route::delete('products/{id}', [ProductController::class, 'destroy'])->middleware('can:product_delete')->name('products.destroy');
    Route::post('products/bulk_delete', [ProductController::class, 'bulk_delete'])->middleware('can:product_bulk_delete')->name('products.bulk_delete');

    // discount for model Discount
    Route::get('discounts', [DiscountController::class, 'index'])->middleware('can:discount_index')->name('discounts.index');
    Route::get('discounts/create', [DiscountController::class, 'create'])->middleware('can:discount_create')->name('discounts.create');
    Route::get('discounts/create/{id}', [DiscountController::class, 'create'])->middleware('can:discount_clone')->name('discounts.clone');
    Route::get('discounts/{id}/edit', [DiscountController::class, 'edit'])->middleware('can:discount_edit')->name('discounts.edit');
    Route::post('discounts/store', [DiscountController::class, 'store'])->middleware('can:discount_create')->name('discounts.store');
    Route::patch('discounts/{id}', [DiscountController::class, 'update'])->middleware('can:discount_edit')->name('discounts.update');
    Route::delete('discounts/{id}', [DiscountController::class, 'destroy'])->middleware('can:discount_delete')->name('discounts.destroy');
    Route::post('discounts/bulk_delete', [DiscountController::class, 'bulk_delete'])->middleware('can:discount_bulk_delete')->name('discounts.bulk_delete');

    // credit for model Credit
    Route::get('credits', [CreditController::class, 'index'])->middleware('can:credit_index')->name('credits.index');
    Route::get('credits/recharge', [CreditController::class, 'show_recharge'])->middleware('can:credit_recharge')->name('credits.recharge');
    Route::post('credits/recharge/submit', [CreditController::class, 'handle_recharge'])->middleware('can:credit_recharge')->name('credits.recharge.submit');
    Route::get('credits/create', [CreditController::class, 'create'])->middleware('can:credit_create')->name('credits.create');
    Route::get('credits/create/{id}', [CreditController::class, 'create'])->middleware('can:credit_clone')->name('credits.clone');
    Route::get('credits/{id}/edit', [CreditController::class, 'edit'])->middleware('can:credit_edit')->name('credits.edit');
    Route::post('credits/store', [CreditController::class, 'store'])->middleware('can:credit_create')->name('credits.store');
    Route::patch('credits/{id}', [CreditController::class, 'update'])->middleware('can:credit_edit')->name('credits.update');
    Route::delete('credits/{id}', [CreditController::class, 'destroy'])->middleware('can:credit_delete')->name('credits.destroy');
    Route::post('credits/bulk_delete', [CreditController::class, 'bulk_delete'])->middleware('can:credit_bulk_delete')->name('credits.bulk_delete');

    // credit_transaction for model CreditTransaction
    Route::get('credit_transactions', [CreditTransactionController::class, 'index'])->middleware('can:credit_transaction_index')->name('credit_transactions.index');
    Route::get('credit_transactions/create', [CreditTransactionController::class, 'create'])->middleware('can:credit_transaction_create')->name('credit_transactions.create');
    Route::get('credit_transactions/create/{id}', [CreditTransactionController::class, 'create'])->middleware('can:credit_transaction_clone')->name('credit_transactions.clone');
    Route::get('credit_transactions/{id}/edit', [CreditTransactionController::class, 'edit'])->middleware('can:credit_transaction_edit')->name('credit_transactions.edit');
    Route::post('credit_transactions/store', [CreditTransactionController::class, 'store'])->middleware('can:credit_transaction_create')->name('credit_transactions.store');
    Route::patch('credit_transactions/{id}', [CreditTransactionController::class, 'update'])->middleware('can:credit_transaction_edit')->name('credit_transactions.update');
    Route::delete('credit_transactions/{id}', [CreditTransactionController::class, 'destroy'])->middleware('can:credit_transaction_delete')->name('credit_transactions.destroy');
    Route::post('credit_transactions/bulk_delete', [CreditTransactionController::class, 'bulk_delete'])->middleware('can:credit_transaction_bulk_delete')->name('credit_transactions.bulk_delete');

    // card for model Card
    Route::get('cards', [CardController::class, 'index'])->middleware('can:card_index')->name('cards.index');
    Route::get('cards/create', [CardController::class, 'create'])->middleware('can:card_create')->name('cards.create');
    Route::get('cards/create/{id}', [CardController::class, 'create'])->middleware('can:card_clone')->name('cards.clone');
    Route::get('cards/{id}/edit', [CardController::class, 'edit'])->middleware('can:card_edit')->name('cards.edit');
    Route::post('cards/store', [CardController::class, 'store'])->middleware('can:card_create')->name('cards.store');
    Route::patch('cards/{id}', [CardController::class, 'update'])->middleware('can:card_edit')->name('cards.update');
    Route::delete('cards/{id}', [CardController::class, 'destroy'])->middleware('can:card_delete')->name('cards.destroy');
    Route::post('cards/bulk_delete', [CardController::class, 'bulk_delete'])->middleware('can:card_bulk_delete')->name('cards.bulk_delete');
    Route::post('cards/bulk_create', [CardController::class, 'bulkCreate'])->middleware('can:card_bulk_create')->name('cards.bulk_create');

    // order for model Order
    Route::get('orders', [OrderController::class, 'index'])->middleware('can:order_index')->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->middleware('can:order_create')->name('orders.create');
    Route::get('orders/create/{id}', [OrderController::class, 'create'])->middleware('can:order_clone')->name('orders.clone');
    Route::get('orders/{id}/edit', [OrderController::class, 'edit'])->middleware('can:order_edit')->name('orders.edit');
    Route::post('orders/store', [OrderController::class, 'store'])->middleware('can:order_create')->name('orders.store');
    Route::patch('orders/{id}', [OrderController::class, 'update'])->middleware('can:order_edit')->name('orders.update');
    Route::delete('orders/{id}', [OrderController::class, 'destroy'])->middleware('can:order_delete')->name('orders.destroy');
    Route::post('orders/bulk_delete', [OrderController::class, 'bulk_delete'])->middleware('can:order_bulk_delete')->name('orders.bulk_delete');

    // order_item for model OrderItem
    Route::get('order_items', [OrderItemController::class, 'index'])->middleware('can:order_item_index')->name('order_items.index');
    Route::get('order_items/create', [OrderItemController::class, 'create'])->middleware('can:order_item_create')->name('order_items.create');
    Route::get('order_items/create/{id}', [OrderItemController::class, 'create'])->middleware('can:order_item_clone')->name('order_items.clone');
    Route::get('order_items/{id}/edit', [OrderItemController::class, 'edit'])->middleware('can:order_item_edit')->name('order_items.edit');
    Route::post('order_items/store', [OrderItemController::class, 'store'])->middleware('can:order_item_create')->name('order_items.store');
    Route::patch('order_items/{id}', [OrderItemController::class, 'update'])->middleware('can:order_item_edit')->name('order_items.update');
    Route::delete('order_items/{id}', [OrderItemController::class, 'destroy'])->middleware('can:order_item_delete')->name('order_items.destroy');
    Route::post('order_items/bulk_delete', [OrderItemController::class, 'bulk_delete'])->middleware('can:order_item_bulk_delete')->name('order_items.bulk_delete');

    // transaction for model Transaction
    Route::get('transactions', [TransactionController::class, 'index'])->middleware('can:transaction_index')->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->middleware('can:transaction_create')->name('transactions.create');
    Route::get('transactions/create/{id}', [TransactionController::class, 'create'])->middleware('can:transaction_clone')->name('transactions.clone');
    Route::get('transactions/{id}/edit', [TransactionController::class, 'edit'])->middleware('can:transaction_edit')->name('transactions.edit');
    Route::post('transactions/store', [TransactionController::class, 'store'])->middleware('can:transaction_create')->name('transactions.store');
    Route::patch('transactions/{id}', [TransactionController::class, 'update'])->middleware('can:transaction_edit')->name('transactions.update');
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy'])->middleware('can:transaction_delete')->name('transactions.destroy');
    Route::post('transactions/bulk_delete', [TransactionController::class, 'bulk_delete'])->middleware('can:transaction_bulk_delete')->name('transactions.bulk_delete');

    // subscription for model Subscription
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->middleware('can:subscription_index')->name('subscriptions.index');
    Route::get('subscriptions/create', [SubscriptionController::class, 'create'])->middleware('can:subscription_create')->name('subscriptions.create');
    Route::get('subscriptions/create/{id}', [SubscriptionController::class, 'create'])->middleware('can:subscription_clone')->name('subscriptions.clone');
    Route::get('subscriptions/{id}/edit', [SubscriptionController::class, 'edit'])->middleware('can:subscription_edit')->name('subscriptions.edit');
    Route::post('subscriptions/store', [SubscriptionController::class, 'store'])->middleware('can:subscription_create')->name('subscriptions.store');
    Route::patch('subscriptions/{id}', [SubscriptionController::class, 'update'])->middleware('can:subscription_edit')->name('subscriptions.update');
    Route::delete('subscriptions/{id}', [SubscriptionController::class, 'destroy'])->middleware('can:subscription_delete')->name('subscriptions.destroy');
    Route::post('subscriptions/bulk_delete', [SubscriptionController::class, 'bulk_delete'])->middleware('can:subscription_bulk_delete')->name('subscriptions.bulk_delete');
});
