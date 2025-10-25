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
            // find order
            $order = $this->checkOrder($orderId);

            // data
            $parameters = [
                "amount" => (float)$order->total_amount,
                "order_id" => $order->slug,
                'redirect_url' => route('frontend.orders.success', ['slug' => $order->slug]),
                'notify_url' => route('api.v1.payment.notify', ['payment_gateway' => $this->paymentGateway->slug]),
            ];

            // fetch api
            $parameters['signature'] = $this->sign($parameters, $this->paymentGateway->client_secret);
            $apiUrl = rtrim($this->paymentGateway->endpoint, "/") . "/api/v1/order/create-transaction";
            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($apiUrl, $parameters);

            // get result
            $result = $response->json();

            // error
            if (!isset($result['status_code'])) {
                dd("no status code", $result);
            }

            // new order
            if ($result['status_code'] == 200) {
                $order->update([
                    'payment_gateway_id' => $this->paymentGateway->id,
                    'tracking_code' => $result['data']['trade_id'],
                ]);

                $payment_url = $result['data']['payment_url'];
            }

            // existing order
            elseif ($result['status_code'] == 10002 && $order->tracking_code) {
                $payment_url = rtrim($this->paymentGateway->endpoint, "/") . "/pay/checkout-counter/" . $order->tracking_code;
            } else {
                dd('handle other status code', $result);
            }

            return redirect()->away($payment_url);
        } catch (\Exception $e) {
            info($e->getMessage());
            return redirect()->back()->with('error', 'Error in payment process:' . $e->getMessage());
        }
    }

    private function sign(array $parameters, string $signKey)
    {
        ksort($parameters);
        reset($parameters);
        $sign = '';
        $urls = '';
        foreach ($parameters as $key => $val) {
            if ($val == '') continue;
            if ($key != 'signature') {
                if ($sign != '') {
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val";
                $urls .= "$key=" . urlencode($val);
            }
        }
        $sign = md5($sign . $signKey);
        return $sign;
    }

    public function notify(Request $request)
    {
        try {
            info('Epusdt notify started', [
                'payload' => $request->all(),
            ]);
    
            // Step 1 — Find order
            $order = Order::where('status', 'pending_payment')
                ->where('slug', $request->order_id)
                ->first();
    
            if (!$order) {
                info('Epusdt notify fail: order not found or not pending_payment', [
                    'order_id' => $request->order_id,
                ]);
                return 'fail';
            }
    
            info('Epusdt notify step 1 ok: order found', [
                'order_id' => $order->id,
                'status' => $order->status,
            ]);
    
            // Step 2 — Check signature
            $data = $request->all();
            unset($data['payment_gateway']);
            $sign = $this->sign($data, $order->payment_gateway->client_secret);
    
            info('Epusdt notify computed sign', [
                'computed' => $sign,
                'received' => $request->signature,
            ]);
    
            if ($sign !== $request->signature) {
                info('Epusdt notify fail: signature mismatch');
                return 'fail';
            }
    
            info('Epusdt notify step 2 ok: signature verified');
    
            // Step 3 — Process order completion
            $result = OrderManager::complete($order, $data['trade_id']);
            info('Epusdt notify OrderManager result', [
                'result' => $result,
                'trade_id' => $data['trade_id'] ?? null,
            ]);
    
            if (!$result) {
                info('Epusdt notify fail: OrderManager returned false');
                return 'fail';
            }
    
            // Step 4 — Success
            info('Epusdt notify success', [
                'order_id' => $order->id,
            ]);
            return 'ok';
    
        } catch (\Exception $e) {
            info('Epusdt notify exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 'fail';
        }
    }
    
}
