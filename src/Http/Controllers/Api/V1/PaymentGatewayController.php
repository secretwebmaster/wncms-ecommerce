<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;
use Wncms\Http\Controllers\Controller;

class PaymentGatewayController extends Controller
{
    public function notify(Request $request, ?string $payment_gateway = null)
    {
        $gatewaySlug = $payment_gateway ?: $request->input('payment_gateway');
        if (!$gatewaySlug) {
            return response()->json(['error' => 'Payment gateway is required'], 422);
        }

        $paymentGateway = PaymentGateway::where('status', 'active')
            ->where('slug', $gatewaySlug)
            ->first();

        if (!$paymentGateway) {
            return response()->json(['error' => 'Payment gateway not found'], 404);
        }

        $processor = $paymentGateway->processor();
        if (!$processor) {
            info("Process class of {$paymentGateway->slug} is not found");
            return response()->json(['error' => "Process class of {$paymentGateway->slug} is not found"], 404);
        }

        return $processor->notify($request);
    }
}
