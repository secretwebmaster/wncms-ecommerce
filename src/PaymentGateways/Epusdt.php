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

    private function sign(array $parameters, string $signKey): string
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
            $order = Order::query()
                ->where('slug', $request->input('order_id'))
                ->first();

            if (!$order) {
                info('Epusdt notify fail: order not found', ['order_id' => $request->input('order_id')]);
                return response('fail', 404);
            }

            if (!in_array($order->status, ['pending_payment', 'failed'], true)) {
                return response('ok', 200);
            }

            $data = $request->all();
            unset($data['payment_gateway']);
            $computed = $this->sign($data, (string) ($order->payment_gateway?->client_secret ?: $this->paymentGateway->client_secret));

            if (!hash_equals($computed, (string) $request->input('signature'))) {
                info('Epusdt notify fail: signature mismatch', ['order_id' => $order->id]);
                return response('fail', 400);
            }

            OrderManager::markPaid($order, [
                'status' => 'succeeded',
                'external_id' => $request->input('trade_id'),
                'gateway_reference' => $request->input('trade_id'),
                'ref_id' => $request->input('trade_id'),
                'payment_method' => $this->paymentGateway->slug,
                'payload' => $request->all(),
                'processed_at' => now(),
            ]);

            return response('ok', 200);
        } catch (\Throwable $e) {
            info('Epusdt notify exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 500);
        }
    }
}
