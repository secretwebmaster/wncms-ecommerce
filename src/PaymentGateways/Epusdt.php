<?php

namespace Secretwebmaster\WncmsEcommerce\PaymentGateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Secretwebmaster\WncmsEcommerce\Facades\OrderManager;
use Secretwebmaster\WncmsEcommerce\Interfaces\PaymentGatewayInterface;
use Secretwebmaster\WncmsEcommerce\Models\Order;

class Epusdt extends BasePaymentGateway implements PaymentGatewayInterface
{
    public function process($orderId)
    {
        try {
            $order = $this->checkOrder($orderId);

            $parameters = [
                'amount' => (float) $order->total_amount,
                'order_id' => $order->slug,
                'redirect_url' => route('frontend.orders.success', ['slug' => $order->slug]),
                'notify_url' => route('api.v1.payment.notify.gateway', ['payment_gateway' => $this->paymentGateway->slug]),
            ];

            $parameters['signature'] = $this->sign($parameters, (string) $this->paymentGateway->client_secret);

            $apiUrl = rtrim((string) $this->paymentGateway->endpoint, '/') . '/api/v1/order/create-transaction';
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($apiUrl, $parameters);
            $result = $response->json();

            if (!is_array($result) || !isset($result['status_code'])) {
                info('Epusdt process invalid response', ['order_id' => $order->id, 'response' => $result]);
                return redirect()->back()->with('error', 'Invalid response from payment gateway.');
            }

            if ((int) $result['status_code'] === 200) {
                $order->update([
                    'payment_gateway_id' => $this->paymentGateway->id,
                    'tracking_code' => data_get($result, 'data.trade_id'),
                    'gateway_reference' => data_get($result, 'data.trade_id'),
                ]);

                $paymentUrl = data_get($result, 'data.payment_url');
                if ($paymentUrl) {
                    return redirect()->away($paymentUrl);
                }
            }

            if ((int) $result['status_code'] === 10002 && $order->tracking_code) {
                return redirect()->away(rtrim((string) $this->paymentGateway->endpoint, '/') . '/pay/checkout-counter/' . $order->tracking_code);
            }

            info('Epusdt process unsupported status', ['order_id' => $order->id, 'response' => $result]);
            return redirect()->back()->with('error', 'Payment gateway returned unsupported status.');
        } catch (\Throwable $e) {
            info('Epusdt process exception', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error in payment process: ' . $e->getMessage());
        }
    }

    public function verifyCallback(Request $request, ?Order $order = null): array
    {
        $eventId = trim((string) $request->input('trade_id', ''));
        $signature = trim((string) $request->input('signature', ''));

        if ($signature === '') {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'missing signature',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        if (!$order) {
            return [
                'verified' => false,
                'status' => 404,
                'message' => 'order not found',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        $secret = trim((string) ($order->payment_gateway?->client_secret ?: $this->paymentGateway->client_secret));
        if ($secret === '') {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'missing gateway secret',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        $data = $request->except(['signature', 'payment_gateway']);
        $computed = $this->sign($data, $secret);

        if (!hash_equals($computed, $signature)) {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'signature mismatch',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        return [
            'verified' => true,
            'status' => 200,
            'message' => 'verified',
            'event_id' => $eventId !== '' ? $eventId : null,
        ];
    }

    protected function sign(array $parameters, string $signKey): string
    {
        ksort($parameters);
        $sign = '';

        foreach ($parameters as $key => $val) {
            if ($val === '' || $key === 'signature') {
                continue;
            }

            $sign .= ($sign !== '' ? '&' : '') . "$key=$val";
        }

        return md5($sign . $signKey);
    }

    public function notify(Request $request)
    {
        try {
            $orderSlug = trim((string) $request->input('order_id', ''));
            $order = Order::query()
                ->where('slug', $orderSlug)
                ->first();

            if (!$order) {
                info('Epusdt callback rejected: order not found', $this->callbackContext($request, [
                    'order_id' => $orderSlug !== '' ? $orderSlug : null,
                    'event_id' => $request->input('trade_id'),
                    'verification_result' => 'failed',
                    'reason' => 'order not found',
                ]));
                return response('fail', 404);
            }

            if ($order->payment_gateway_id && (int) $order->payment_gateway_id !== (int) $this->paymentGateway->id) {
                info('Epusdt callback rejected: gateway mismatch', $this->callbackContext($request, [
                    'order_id' => $order->id,
                    'event_id' => $request->input('trade_id'),
                    'verification_result' => 'failed',
                    'reason' => 'gateway mismatch',
                ]));
                return response('fail', 400);
            }

            $verification = $this->verifyCallback($request, $order);
            if (!$verification['verified']) {
                info('Epusdt callback verification failed', $this->callbackContext($request, [
                    'order_id' => $order->id,
                    'event_id' => $verification['event_id'],
                    'verification_result' => 'failed',
                    'reason' => $verification['message'],
                ]));
                return response('fail', (int) $verification['status']);
            }

            info('Epusdt callback verification passed', $this->callbackContext($request, [
                'order_id' => $order->id,
                'event_id' => $verification['event_id'],
                'verification_result' => 'passed',
            ]));

            if (!in_array($order->status, ['pending_payment', 'failed'], true)) {
                return response('ok', 200);
            }

            $status = strtolower(trim((string) $request->input('status', '')));
            $tradeId = trim((string) $request->input('trade_id', ''));

            if (in_array($status, ['success', 'succeeded', 'paid', 'completed'], true)) {
                OrderManager::markPaid($order, [
                    'status' => 'succeeded',
                    'external_id' => $tradeId !== '' ? $tradeId : null,
                    'gateway_reference' => $tradeId !== '' ? $tradeId : null,
                    'ref_id' => $tradeId !== '' ? $tradeId : null,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => $request->all(),
                    'processed_at' => now(),
                ]);
            } elseif (in_array($status, ['fail', 'failed', 'canceled', 'cancelled', 'denied', 'expired'], true)) {
                OrderManager::markFailed($order, [
                    'status' => 'failed',
                    'external_id' => $tradeId !== '' ? $tradeId : null,
                    'gateway_reference' => $tradeId !== '' ? $tradeId : null,
                    'ref_id' => $tradeId !== '' ? $tradeId : null,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => $request->all(),
                    'processed_at' => now(),
                ]);
            } else {
                info('Epusdt callback ignored: unsupported status', $this->callbackContext($request, [
                    'order_id' => $order->id,
                    'event_id' => $verification['event_id'],
                    'verification_result' => 'passed',
                    'status' => $status,
                ]));
            }

            return response('ok', 200);
        } catch (\Throwable $e) {
            info('Epusdt callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 500);
        }
    }
}
