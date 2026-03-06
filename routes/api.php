<?php

use Secretwebmaster\WncmsEcommerce\Http\Controllers\Api\V1\PaymentGatewayController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('payment')->name('payment.')->controller(PaymentGatewayController::class)->group(function () {
        Route::post('notify', 'notify')->name('notify');
        Route::post('notify/{payment_gateway}', 'notify')->name('notify.gateway');
    });
});
