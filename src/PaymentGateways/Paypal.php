<?php

namespace Secretwebmaster\WncmsEcommerce\PaymentGateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsEcommerce\Facades\OrderManager;
use Secretwebmaster\WncmsEcommerce\Interfaces\PaymentGatewayInterface;
use Secretwebmaster\WncmsEcommerce\Models\Order;

class Paypal extends BasePaymentGateway implements PaymentGatewayInterface
{
    public function process($orderId)
    {
        try {
            $order = $this->checkOrder($orderId);

            [$clientId, $clientSecret] = $this->credentials();
            if ($clientId === '' || $clientSecret === '') {
                return back()->with('error', __('wncms::word.tgp_paypal_gateway_credentials_required'));
            }

            $accessToken = $this->requestAccessToken($clientId, $clientSecret);
            if ($accessToken === null) {
                return back()->with('error', __('wncms::word.tgp_paypal_checkout_start_failed'));
            }

            $response = Http::timeout(20)
                ->withToken($accessToken)
                ->acceptJson()
                ->post($this->apiBaseUrl() . '/v2/checkout/orders', $this->buildCreateOrderPayload($order));

            $payload = $response->json() ?: [];
            if (!$response->successful()) {
                info('PayPal create order failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $payload,
                ]);

                return back()->with('error', data_get($payload, 'details.0.description', __('wncms::word.tgp_paypal_checkout_start_failed')));
            }

            $paypalOrderId = (string) ($payload['id'] ?? '');
            $approveUrl = $this->resolveApproveUrl($payload);

            if ($paypalOrderId === '' || $approveUrl === '') {
                info('PayPal create order missing id/approve_url', [
                    'order_id' => $order->id,
                    'response' => $payload,
                ]);

                return back()->with('error', __('wncms::word.tgp_paypal_checkout_start_failed'));
            }

            $order->update([
                'payment_gateway_id' => $this->paymentGateway->id,
                'payment_method' => $this->paymentGateway->slug,
                'tracking_code' => $paypalOrderId,
                'gateway_reference' => $paypalOrderId,
                'payload' => $this->mergePaypalPayload($order->payload, [
                    'create_order' => $payload,
                    'created_at' => now()->toDateTimeString(),
                ]),
            ]);

            return redirect()->away($approveUrl);
        } catch (\Throwable $e) {
            info('PayPal process exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('wncms::word.tgp_paypal_checkout_start_failed'));
        }
    }

    public function capture(Order $order, Request $request): array
    {
        try {
            if (in_array($order->status, ['paid', 'completed'], true)) {
                return ['success' => true];
            }

            if (!in_array($order->status, ['pending_payment', 'failed'], true)) {
                info('PayPal capture skipped due non-payable status', [
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'gateway_slug' => $this->paymentGateway->slug,
                ]);
                return ['success' => false, 'message' => __('wncms::word.tgp_order_not_payable')];
            }

            $paypalToken = trim((string) ($request->query('token') ?: $request->input('token')));
            if ($paypalToken === '') {
                info('PayPal capture failed: missing token', [
                    'order_id' => $order->id,
                    'gateway_slug' => $this->paymentGateway->slug,
                    'query' => $request->query(),
                ]);
                return ['success' => false, 'message' => __('wncms::word.tgp_paypal_callback_missing_token')];
            }

            [$clientId, $clientSecret] = $this->credentials();
            if ($clientId === '' || $clientSecret === '') {
                info('PayPal capture failed: missing credentials', [
                    'order_id' => $order->id,
                    'gateway_slug' => $this->paymentGateway->slug,
                ]);
                return ['success' => false, 'message' => __('wncms::word.tgp_paypal_gateway_credentials_required')];
            }

            $accessToken = $this->requestAccessToken($clientId, $clientSecret);
            if ($accessToken === null) {
                return ['success' => false, 'message' => __('wncms::word.tgp_paypal_capture_failed')];
            }

            $response = Http::timeout(20)
                ->withToken($accessToken)
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody('{}', 'application/json')
                ->post($this->apiBaseUrl() . '/v2/checkout/orders/' . $paypalToken . '/capture');

            $payload = $response->json() ?: [];
            if (!$response->successful()) {
                info('PayPal capture failed', [
                    'order_id' => $order->id,
                    'paypal_token' => $paypalToken,
                    'status' => $response->status(),
                    'response' => $payload,
                ]);

                return [
                    'success' => false,
                    'message' => data_get($payload, 'details.0.description', __('wncms::word.tgp_paypal_capture_failed')),
                ];
            }

            $capture = data_get($payload, 'purchase_units.0.payments.captures.0', []);
            $captureStatus = strtoupper((string) ($capture['status'] ?? data_get($payload, 'status', '')));
            $captureId = (string) ($capture['id'] ?? data_get($payload, 'id') ?? $paypalToken);

            if ($captureStatus === 'COMPLETED') {
                OrderManager::markPaid($order, [
                    'status' => 'succeeded',
                    'external_id' => $captureId,
                    'gateway_reference' => $captureId,
                    'ref_id' => $captureId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => [
                        'source' => 'paypal_capture_return',
                        'paypal_order_id' => (string) data_get($payload, 'id', $paypalToken),
                        'paypal_capture' => $payload,
                    ],
                    'processed_at' => now(),
                ]);

                return ['success' => true];
            }

            if (in_array($captureStatus, ['FAILED', 'DENIED', 'DECLINED'], true)) {
                OrderManager::markFailed($order, [
                    'status' => 'failed',
                    'external_id' => $captureId,
                    'gateway_reference' => $captureId,
                    'ref_id' => $captureId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => [
                        'source' => 'paypal_capture_return',
                        'paypal_order_id' => (string) data_get($payload, 'id', $paypalToken),
                        'paypal_capture' => $payload,
                    ],
                    'processed_at' => now(),
                ]);
            }

            info('PayPal capture not completed', [
                'order_id' => $order->id,
                'gateway_slug' => $this->paymentGateway->slug,
                'capture_status' => $captureStatus,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => __('wncms::word.tgp_paypal_capture_not_completed', ['status' => $captureStatus ?: 'UNKNOWN']),
            ];
        } catch (\Throwable $e) {
            info('PayPal capture exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'message' => __('wncms::word.tgp_paypal_capture_failed')];
        }
    }

    public function notify(Request $request)
    {
        try {
            $eventType = strtoupper((string) $request->input('event_type', ''));
            $resource = $request->input('resource');

            if (!is_array($resource)) {
                return response('ok', 200);
            }

            $orderRef = (string) (
                data_get($resource, 'invoice_id')
                ?: data_get($resource, 'custom_id')
                ?: data_get($resource, 'purchase_units.0.invoice_id')
                ?: data_get($resource, 'purchase_units.0.custom_id')
            );

            if ($orderRef === '') {
                return response('ok', 200);
            }

            $order = Order::query()->where('slug', $orderRef)->first();
            if (!$order && ctype_digit($orderRef)) {
                $order = Order::query()->find((int) $orderRef);
            }
            if (!$order) {
                return response('ok', 200);
            }

            $externalId = (string) (
                data_get($resource, 'id')
                ?: data_get($resource, 'supplementary_data.related_ids.capture_id')
                ?: data_get($resource, 'supplementary_data.related_ids.order_id')
                ?: ('PAYPAL-' . $order->slug)
            );

            if (str_contains($eventType, 'COMPLETED')) {
                OrderManager::markPaid($order, [
                    'status' => 'succeeded',
                    'external_id' => $externalId,
                    'gateway_reference' => $externalId,
                    'ref_id' => $externalId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => [
                        'source' => 'paypal_webhook',
                        'event_type' => $eventType,
                        'resource' => $resource,
                    ],
                    'processed_at' => now(),
                ]);
            } elseif (str_contains($eventType, 'FAILED') || str_contains($eventType, 'DENIED') || str_contains($eventType, 'DECLINED')) {
                OrderManager::markFailed($order, [
                    'status' => 'failed',
                    'external_id' => $externalId,
                    'gateway_reference' => $externalId,
                    'ref_id' => $externalId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => [
                        'source' => 'paypal_webhook',
                        'event_type' => $eventType,
                        'resource' => $resource,
                    ],
                    'processed_at' => now(),
                ]);
            }

            return response('ok', 200);
        } catch (\Throwable $e) {
            info('PayPal notify exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 500);
        }
    }

    protected function credentials(): array
    {
        $clientId = trim((string) $this->paymentGateway->client_id);
        $clientSecret = trim((string) $this->paymentGateway->client_secret);

        if (($clientId === '' || $clientSecret === '') && function_exists('gss')) {
            $legacyClientId = trim((string) gss('ecommerce_paypal_client_id'));
            $legacyClientSecret = trim((string) gss('ecommerce_paypal_client_secret'));

            if ($legacyClientId !== '' && $legacyClientSecret !== '') {
                $clientId = $clientId !== '' ? $clientId : $legacyClientId;
                $clientSecret = $clientSecret !== '' ? $clientSecret : $legacyClientSecret;

                $this->paymentGateway->update([
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
            }
        }

        return [$clientId, $clientSecret];
    }

    protected function requestAccessToken(string $clientId, string $clientSecret): ?string
    {
        $response = Http::timeout(20)
            ->asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post($this->apiBaseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            info('PayPal access token request failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        }

        $accessToken = (string) data_get($response->json(), 'access_token');
        return $accessToken !== '' ? $accessToken : null;
    }

    protected function apiBaseUrl(): string
    {
        if (!empty($this->paymentGateway->endpoint)) {
            return rtrim((string) $this->paymentGateway->endpoint, '/');
        }

        return (bool) $this->paymentGateway->is_sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    protected function buildCreateOrderPayload(Order $order): array
    {
        $currency = strtoupper((string) ($order->currency ?: $this->paymentGateway->currency ?: 'USD'));
        $amount = number_format((float) $order->total_amount, 2, '.', '');

        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => (string) $order->slug,
                    'invoice_id' => (string) $order->slug,
                    'custom_id' => (string) $order->id,
                    'description' => 'Order ' . $order->slug,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => (string) config('app.name', 'WNCMS'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $this->resolveReturnUrl($order),
                'cancel_url' => $this->resolveCancelUrl($order),
            ],
        ];
    }

    protected function resolveApproveUrl(array $payload): string
    {
        $links = $payload['links'] ?? [];
        if (!is_array($links)) {
            return '';
        }

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            if (($link['rel'] ?? null) === 'approve' && !empty($link['href'])) {
                return (string) $link['href'];
            }
        }

        return '';
    }

    protected function mergePaypalPayload($existingPayload, array $paypalPayload): array
    {
        $payload = is_array($existingPayload) ? $existingPayload : [];
        $payload['paypal'] = $paypalPayload;
        return $payload;
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

        if (Route::has('frontend.orders.paypal.return')) {
            return route('frontend.orders.paypal.return', ['slug' => $order->slug]);
        }

        return route('frontend.orders.success', ['slug' => $order->slug]);
    }

    protected function resolveCancelUrl(Order $order): string
    {
        if (Route::has('frontend.orders.waiting')) {
            return route('frontend.orders.waiting', ['slug' => $order->slug]);
        }

        return route('frontend.orders.show', ['slug' => $order->slug]);
    }
}
