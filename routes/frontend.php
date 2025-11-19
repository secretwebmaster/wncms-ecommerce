<?php

use Illuminate\Support\Facades\Route;

use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\CardController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\ProductController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\OrderController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\PlanController;
use Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend\SubscriptionController;

use Secretwebmaster\WncmsEcommerce\Models\Product;

Route::name('frontend.')->middleware('is_installed', 'has_website')->group(function () {

    // Plans
    Route::prefix('plans')->controller(PlanController::class)->group(function () {
        Route::get('/', 'index')->name('plans.index');
        Route::get('/{slug}', 'show')->name('plans.show');
        Route::post('/subscribe', 'subscribe')->name('plans.subscribe');
        Route::post('/unsubscribe', 'unsubscribe')->name('plans.unsubscribe');
    });

    // Products
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index')->name('products.index');
        Route::get('/{slug}', 'show')->name('products.show');
        Route::get('/{type}/{slug}', [ProductController::class, 'tag'])->where('type',  wncms()->tag()->getTagTypesForRoute(Product::class))->name('products.tag');
    });

    Route::middleware(['auth'])->group(function () {

        // orders
        Route::prefix('orders')->controller(OrderController::class)->group(function () {
            Route::get('/', 'index')->name('orders.index');
            Route::post('/create', 'create')->name('orders.create');
            Route::get('/{slug}', 'show')->name('orders.show');
            Route::post('/{slug}/pay', 'pay')->name('orders.pay');
            Route::get('/{slug}/success', 'success')->name('orders.success');
            Route::post('/status', 'status')->name('orders.status');
        });

        Route::prefix('user')->group(function () {
            Route::prefix('subscription')->controller(SubscriptionController::class)->group(function () {
                Route::get('/', 'index')->name('users.subscriptions.index');
                Route::get('/{id}', 'show')->name('users.subscriptions.show');
            });

            Route::prefix('card')->controller(CardController::class)->group(function () {
                Route::get('/', 'show')->name('users.card');
                Route::post('/use', 'use')->name('users.card.use');
            });
        });
    });
});
