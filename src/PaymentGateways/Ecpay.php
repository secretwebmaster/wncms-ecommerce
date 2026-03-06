<?php

namespace Secretwebmaster\WncmsEcommerce\PaymentGateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsEcommerce\Facades\OrderManager;
use Secretwebmaster\WncmsEcommerce\Interfaces\PaymentGatewayInterface;
use Secretwebmaster\WncmsEcommerce\Models\Order;

class Ecpay extends BasePaymentGateway implements PaymentGatewayInterface
{
    public function process($orderId)
    {
        try {
            $order = $this->checkOrder($orderId);

            [$merchantId, $hashKey, $hashIv] = $this->credentials();
            if ($merchantId === '' || $hashKey === '' || $hashIv === '') {
                return back()->with('error', __('wncms-ecommerce::word.ecpay_gateway_credentials_required'));
            }

            $merchantTradeNo = $this->resolveMerchantTradeNo($order);
            $tradeDate = now()->format('Y/m/d H:i:s');
            $totalAmount = max(1, (int) round((float) $order->total_amount));

            $parameters = [
                'MerchantID' => $merchantId,
                'MerchantTradeNo' => $merchantTradeNo,
                'MerchantTradeDate' => $tradeDate,
                'PaymentType' => 'aio',
                'TotalAmount' => $totalAmount,
                'TradeDesc' => $this->resolveTradeDesc($order),
                'ItemName' => $this->resolveItemName($order),
                'ReturnURL' => $this->resolveNotifyUrl(),
                'ChoosePayment' => 'ALL',
                'EncryptType' => 1,
                'ClientBackURL' => $this->resolveReturnUrl($order),
                'CustomField1' => (string) $order->id,
                'CustomField2' => (string) $order->slug,
            ];
            $parameters['CheckMacValue'] = $this->generateCheckMacValue($parameters, $hashKey, $hashIv);

            $order->update([
                'payment_gateway_id' => $this->paymentGateway->id,
                'payment_method' => $this->paymentGateway->slug,
                'tracking_code' => $merchantTradeNo,
                'gateway_reference' => $merchantTradeNo,
                'payload' => $this->mergeEcpayPayload($order->payload, [
                    'merchant_trade_no' => $merchantTradeNo,
                    'merchant_trade_date' => $tradeDate,
                    'total_amount' => $totalAmount,
                    'created_at' => now()->toDateTimeString(),
                ]),
            ]);

            return response()->view('wncms-ecommerce::frontend.payment_gateways.ecpay-redirect', [
                'action' => $this->checkoutUrl(),
                'parameters' => $parameters,
            ]);
        } catch (\Throwable $e) {
            info('ECPay process exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('wncms-ecommerce::word.ecpay_checkout_start_failed'));
        }
    }

    public function notify(Request $request)
    {
        try {
            $merchantTradeNo = trim((string) $request->input('MerchantTradeNo', ''));
            $order = $this->resolveOrder($request);

            if (!$order) {
                info('ECPay callback rejected: order not found', $this->callbackContext($request, [
                    'order_id' => $merchantTradeNo !== '' ? $merchantTradeNo : null,
                    'event_id' => $request->input('TradeNo') ?: $merchantTradeNo,
                    'verification_result' => 'failed',
                    'reason' => 'order not found',
                ]));

                return response('fail', 404);
            }

            if ($order->payment_gateway_id && (int) $order->payment_gateway_id !== (int) $this->paymentGateway->id) {
                info('ECPay callback rejected: gateway mismatch', $this->callbackContext($request, [
                    'order_id' => $order->id,
                    'event_id' => $request->input('TradeNo') ?: $merchantTradeNo,
                    'verification_result' => 'failed',
                    'reason' => 'gateway mismatch',
                ]));

                return response('fail', 400);
            }

            $verification = $this->verifyCallback($request, $order);
            if (!$verification['verified']) {
                info('ECPay callback verification failed', $this->callbackContext($request, [
                    'order_id' => $order->id,
                    'event_id' => $verification['event_id'],
                    'verification_result' => 'failed',
                    'reason' => $verification['message'],
                ]));

                return response('fail', (int) $verification['status']);
            }

            info('ECPay callback verification passed', $this->callbackContext($request, [
                'order_id' => $order->id,
                'event_id' => $verification['event_id'],
                'verification_result' => 'passed',
            ]));

            if (in_array($order->status, ['paid', 'completed'], true)) {
                return response('1|OK', 200);
            }

            if (!in_array($order->status, ['pending_payment', 'failed'], true)) {
                return response('1|OK', 200);
            }

            $externalId = trim((string) ($request->input('TradeNo') ?: $merchantTradeNo));
            if ($externalId === '') {
                $externalId = 'ECPAY-' . $order->slug;
            }

            if ($order->transactions()->where('external_id', $externalId)->exists()) {
                return response('1|OK', 200);
            }

            $rtnCode = (int) $request->input('RtnCode', 0);
            $transactionPayload = [
                'source' => 'ecpay_callback',
                'rtn_code' => $rtnCode,
                'rtn_msg' => (string) $request->input('RtnMsg', ''),
                'payload' => $request->all(),
            ];

            if ($rtnCode === 1) {
                OrderManager::markPaid($order, [
                    'status' => 'succeeded',
                    'external_id' => $externalId,
                    'gateway_reference' => $externalId,
                    'ref_id' => $externalId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => $transactionPayload,
                    'processed_at' => now(),
                ]);
            } else {
                OrderManager::markFailed($order, [
                    'status' => 'failed',
                    'external_id' => $externalId,
                    'gateway_reference' => $externalId,
                    'ref_id' => $externalId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => $transactionPayload,
                    'processed_at' => now(),
                ]);
            }

            // ECPay server callback expects exact ack text.
            return response('1|OK', 200);
        } catch (\Throwable $e) {
            info('ECPay callback exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 500);
        }
    }

    public function verifyCallback(Request $request, ?Order $order = null): array
    {
        $eventId = trim((string) ($request->input('TradeNo') ?: $request->input('MerchantTradeNo', '')));
        $checkMacValue = strtoupper(trim((string) $request->input('CheckMacValue', '')));

        if ($checkMacValue === '') {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'missing check mac value',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        [$merchantId, $hashKey, $hashIv] = $this->credentials();
        if ($merchantId === '' || $hashKey === '' || $hashIv === '') {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'missing gateway credentials',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        $payloadMerchantId = trim((string) $request->input('MerchantID', ''));
        if ($payloadMerchantId !== '' && !hash_equals($merchantId, $payloadMerchantId)) {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'merchant id mismatch',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        $callbackData = $request->except(['CheckMacValue', 'checkmacvalue']);
        if (!is_array($callbackData) || empty($callbackData)) {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'invalid payload',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        $computedMac = $this->generateCheckMacValue($callbackData, $hashKey, $hashIv);
        if (!hash_equals($computedMac, $checkMacValue)) {
            return [
                'verified' => false,
                'status' => 400,
                'message' => 'check mac mismatch',
                'event_id' => $eventId !== '' ? $eventId : null,
            ];
        }

        if ($order) {
            $merchantTradeNo = trim((string) $request->input('MerchantTradeNo', ''));
            if ($merchantTradeNo !== '') {
                $expectedMerchantTradeNo = trim((string) $order->tracking_code);
                if ($expectedMerchantTradeNo !== '' && !hash_equals($expectedMerchantTradeNo, $merchantTradeNo)) {
                    return [
                        'verified' => false,
                        'status' => 400,
                        'message' => 'merchant trade no mismatch',
                        'event_id' => $eventId !== '' ? $eventId : null,
                    ];
                }
            }

            $tradeAmount = (int) $request->input('TradeAmt', $request->input('TotalAmount', 0));
            if ($tradeAmount > 0) {
                $expectedAmount = max(1, (int) round((float) $order->total_amount));
                if ($tradeAmount !== $expectedAmount) {
                    return [
                        'verified' => false,
                        'status' => 400,
                        'message' => 'amount mismatch',
                        'event_id' => $eventId !== '' ? $eventId : null,
                    ];
                }
            }
        }

        return [
            'verified' => true,
            'status' => 200,
            'message' => 'verified',
            'event_id' => $eventId !== '' ? $eventId : null,
        ];
    }

    protected function credentials(): array
    {
        $merchantId = trim((string) $this->paymentGateway->client_id);
        $hashKey = trim((string) $this->paymentGateway->client_secret);
        $hashIv = trim((string) $this->paymentGateway->webhook_secret);

        return [$merchantId, $hashKey, $hashIv];
    }

    protected function checkoutUrl(): string
    {
        if (!empty($this->paymentGateway->endpoint)) {
            return trim((string) $this->paymentGateway->endpoint);
        }

        return (bool) $this->paymentGateway->is_sandbox
            ? 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5'
            : 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5';
    }

    protected function resolveNotifyUrl(): string
    {
        return (string) ($this->paymentGateway->getNotifyUrl() ?: route('api.v1.payment.notify.gateway', [
            'payment_gateway' => $this->paymentGateway->slug,
        ]));
    }

    protected function resolveReturnUrl(Order $order): string
    {
        $configured = trim((string) $this->paymentGateway->return_url);
        if ($configured !== '') {
            if (str_starts_with($configured, '/')) {
                $configured = url($configured);
            }

            return strtr($configured, [
                '{order_slug}' => (string) $order->slug,
                '{order_id}' => (string) $order->id,
                '{gateway_slug}' => (string) $this->paymentGateway->slug,
            ]);
        }

        if (Route::has('frontend.orders.success')) {
            return route('frontend.orders.success', ['slug' => $order->slug]);
        }

        return url('/orders/' . rawurlencode((string) $order->slug) . '/success');
    }

    protected function resolveMerchantTradeNo(Order $order): string
    {
        $existing = trim((string) $order->tracking_code);
        if (
            $existing !== ''
            && (int) ($order->payment_gateway_id ?? 0) === (int) $this->paymentGateway->id
        ) {
            return $this->normalizeMerchantTradeNo($existing, $order);
        }

        return $this->normalizeMerchantTradeNo('WNO' . (string) $order->id, $order);
    }

    protected function normalizeMerchantTradeNo(string $value, Order $order): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($value)) ?? '';
        $normalized = substr($normalized, 0, 20);

        if ($normalized === '') {
            $normalized = 'WNO' . (string) $order->id;
        }

        return substr($normalized, 0, 20);
    }

    protected function resolveTradeDesc(Order $order): string
    {
        return 'Order ' . (string) $order->slug;
    }

    protected function resolveItemName(Order $order): string
    {
        return 'Order ' . (string) $order->slug;
    }

    protected function resolveOrder(Request $request): ?Order
    {
        $merchantTradeNo = trim((string) $request->input('MerchantTradeNo', ''));
        $customOrderId = trim((string) $request->input('CustomField1', ''));
        $customOrderSlug = trim((string) $request->input('CustomField2', ''));

        if ($merchantTradeNo !== '') {
            $order = Order::query()->where('tracking_code', $merchantTradeNo)->first();
            if ($order) {
                return $order;
            }
        }

        if ($customOrderSlug !== '') {
            $order = Order::query()->where('slug', $customOrderSlug)->first();
            if ($order) {
                return $order;
            }
        }

        if ($customOrderId !== '' && ctype_digit($customOrderId)) {
            $order = Order::query()->find((int) $customOrderId);
            if ($order) {
                return $order;
            }
        }

        if ($merchantTradeNo !== '' && ctype_digit($merchantTradeNo)) {
            return Order::query()->find((int) $merchantTradeNo);
        }

        return null;
    }

    protected function mergeEcpayPayload($existingPayload, array $ecpayPayload): array
    {
        $payload = is_array($existingPayload) ? $existingPayload : [];
        $payload['ecpay'] = $ecpayPayload;
        return $payload;
    }

    protected function generateCheckMacValue(array $parameters, string $hashKey, string $hashIv): string
    {
        $filtered = [];
        foreach ($parameters as $key => $value) {
            if ($key === 'CheckMacValue') {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $filtered[$key] = (string) $value;
        }

        ksort($filtered);

        $pairs = [];
        foreach ($filtered as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        $raw = 'HashKey=' . $hashKey . '&' . implode('&', $pairs) . '&HashIV=' . $hashIv;
        $encoded = strtolower(urlencode($raw));
        $encoded = str_replace(
            ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'],
            ['-', '_', '.', '!', '*', '(', ')'],
            $encoded
        );

        return strtoupper(hash('sha256', $encoded));
    }
}
