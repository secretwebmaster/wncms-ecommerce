<?php

use Secretwebmaster\WncmsEcommerce\Http\Controllers\Api\V1\PaymentGatewayController;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Payment
    Route::prefix('payment')->name('payment.')->controller(PaymentGatewayController::class)->group(function () {
        Route::post('notify', 'notify')->name('notify');
    });
});
