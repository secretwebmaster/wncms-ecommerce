<?php

namespace Secretwebmaster\WncmsEcommerce\PaymentGateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Secretwebmaster\WncmsEcommerce\Exceptions\PaymentGatewayException;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;

abstract class BasePaymentGateway
{
    protected PaymentGateway $paymentGateway;
    
    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function checkOrder($orderId)
    {
        // find order
        if ($orderId instanceof Order) {
            $order = $orderId;
        } else {
            $order = Order::find($orderId);
        }

        if (!$order) {
            throw new PaymentGatewayException('Order not found');
        }

        // check order status
        if ($order->status != 'pending_payment') {
            throw new PaymentGatewayException('Order is not pending payment');
        }

        return $order;
    }

    public function load($paymentGatewayId)
    {
        // find payment gateway
        $paymentGateway = PaymentGateway::where('slug', $paymentGatewayId)->first();
        if (!$paymentGateway) {
            throw new PaymentGatewayException('Payment gateway not found');
        }

        return $paymentGateway;
    }

    protected function resolveCallbackCorrelationId(Request $request, ?string $eventId = null): string
    {
        $candidates = [
            $eventId,
            $request->header('X-Request-Id'),
            $request->header('PayPal-Transmission-Id'),
            $request->input('trade_id'),
            $request->input('order_id'),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return Str::limit($value, 120, '');
            }
        }

        return (string) Str::uuid();
    }

    protected function callbackContext(Request $request, array $context = []): array
    {
        $eventId = $context['event_id'] ?? null;
        $order = $context['order_id'] ?? null;

        return array_merge([
            'gateway' => $this->paymentGateway->slug,
            'correlation_id' => $this->resolveCallbackCorrelationId($request, is_scalar($eventId) ? (string) $eventId : null),
            'event_id' => is_scalar($eventId) ? (string) $eventId : null,
            'order_id' => is_scalar($order) ? (string) $order : null,
        ], $context);
    }
}
