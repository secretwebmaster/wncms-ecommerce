<?php

use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\CardController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\ProductController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\OrderController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\PlanController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\SubscriptionController;



// Plans
Route::prefix('plans')->controller(PlanController::class)->group(function () {
    Route::get('/', 'index')->name('frontend.plans.index');
    Route::get('/{slug}', 'show')->name('frontend.plans.show');
    Route::post('/subscribe', 'subscribe')->name('frontend.plans.subscribe');
    Route::post('/unsubscribe', 'unsubscribe')->name('frontend.plans.unsubscribe');
});

// Products
Route::prefix('product')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->name('frontend.products.index');
    Route::get('/{slug}', 'show')->name('frontend.products.show');
});

Route::middleware(['auth'])->group(function () {

    // orders
    Route::prefix('orders')->controller(OrderController::class)->group(function () {
        Route::get('/', 'index')->name('frontend.orders.index');
        Route::post('/create', 'create')->name('frontend.orders.create');
        Route::get('/{slug}', 'show')->name('frontend.orders.show');
        Route::post('/{slug}/pay', 'pay')->name('frontend.orders.pay');
        Route::get('/{slug}/success', 'success')->name('frontend.orders.success');
        Route::post('/status', 'status')->name('frontend.orders.status');
    });

    Route::prefix('user')->group(function () {
        Route::prefix('subscription')->controller(SubscriptionController::class)->group(function () {
            Route::get('/', 'index')->name('frontend.users.subscriptions.index');
            Route::get('/{id}', 'show')->name('frontend.users.subscriptions.show');
        });

        Route::prefix('card')->controller(CardController::class)->group(function () {
            Route::get('/', 'show')->name('frontend.users.card');
            Route::post('/use', 'use')->name('frontend.users.card.use');
        });
    });
});
