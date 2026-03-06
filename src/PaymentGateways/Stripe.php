<?php

namespace Secretwebmaster\WncmsEcommerce\PaymentGateways;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Secretwebmaster\WncmsEcommerce\Facades\OrderManager;
use Secretwebmaster\WncmsEcommerce\Interfaces\PaymentGatewayInterface;
use Secretwebmaster\WncmsEcommerce\Models\Order;

class Stripe extends BasePaymentGateway implements PaymentGatewayInterface
{
    public function process($orderId)
    {
        try {
            $order = $this->checkOrder($orderId);
            $secretKey = $this->resolveSecretKey();

            if ($secretKey === '') {
                return back()->with('error', __('wncms::word.tgp_stripe_gateway_secret_required'));
            }

            $amount = max(1, (int) round((float) $order->total_amount * 100));
            $currency = strtolower((string) ($order->currency ?: $this->paymentGateway->currency ?: 'USD'));
            $response = Http::timeout(20)
                ->asForm()
                ->withToken($secretKey)
                ->acceptJson()
                ->post($this->apiBaseUrl() . '/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $this->resolveSuccessUrl($order),
                    'cancel_url' => $this->resolveCancelUrl($order),
                    'client_reference_id' => (string) $order->slug,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => $currency,
                    'line_items[0][price_data][unit_amount]' => $amount,
                    'line_items[0][price_data][product_data][name]' => 'Order ' . $order->slug,
                    'metadata[order_slug]' => (string) $order->slug,
                    'metadata[order_id]' => (string) $order->id,
                    'payment_intent_data[metadata][order_slug]' => (string) $order->slug,
                    'payment_intent_data[metadata][order_id]' => (string) $order->id,
                ]);

            $payload = $response->json() ?: [];
            if (!$response->successful()) {
                info('Stripe create checkout session failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $payload,
                ]);

                return back()->with('error', __('wncms::word.tgp_stripe_checkout_start_failed'));
            }

            $sessionId = (string) data_get($payload, 'id', '');
            $checkoutUrl = (string) data_get($payload, 'url', '');

            if ($sessionId === '' || $checkoutUrl === '') {
                info('Stripe checkout session missing id/url', [
                    'order_id' => $order->id,
                    'response' => $payload,
                ]);

                return back()->with('error', __('wncms::word.tgp_stripe_checkout_start_failed'));
            }

            $order->update([
                'payment_gateway_id' => $this->paymentGateway->id,
                'payment_method' => $this->paymentGateway->slug,
                'tracking_code' => $sessionId,
                'gateway_reference' => (string) data_get($payload, 'payment_intent', $sessionId),
                'payload' => $this->mergeStripePayload($order->payload, [
                    'create_checkout_session' => $payload,
                    'created_at' => now()->toDateTimeString(),
                ]),
            ]);

            return redirect()->away($checkoutUrl);
        } catch (\Throwable $e) {
            info('Stripe process exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('wncms::word.tgp_stripe_checkout_start_failed'));
        }
    }

    public function notify(Request $request)
    {
        try {
            $payload = (string) $request->getContent();
            $signatureHeader = (string) $request->header('Stripe-Signature', '');
            $webhookSecret = trim((string) $this->paymentGateway->webhook_secret);

            if ($webhookSecret === '' || !$this->isValidSignature($payload, $signatureHeader, $webhookSecret)) {
                info('Stripe webhook signature invalid', [
                    'gateway' => $this->paymentGateway->slug,
                    'has_secret' => $webhookSecret !== '',
                ]);

                return response('invalid signature', 400);
            }

            $event = json_decode($payload, true);
            if (!is_array($event)) {
                return response('invalid payload', 400);
            }

            $eventType = strtolower((string) data_get($event, 'type', ''));
            $eventObject = data_get($event, 'data.object');
            if (!is_array($eventObject)) {
                return response('ok', 200);
            }

            $order = $this->resolveOrder($eventObject);
            if (!$order) {
                return response('ok', 200);
            }

            $externalId = (string) (
                data_get($eventObject, 'payment_intent')
                ?: data_get($eventObject, 'id')
                ?: data_get($event, 'id')
                ?: ('STRIPE-' . $order->slug)
            );

            $transactionPayload = [
                'source' => 'stripe_webhook',
                'event_type' => $eventType,
                'event_id' => (string) data_get($event, 'id', ''),
                'object' => $eventObject,
            ];

            if ($this->isSuccessEvent($eventType, $eventObject)) {
                OrderManager::markPaid($order, [
                    'status' => 'succeeded',
                    'external_id' => $externalId,
                    'gateway_reference' => $externalId,
                    'ref_id' => $externalId,
                    'payment_method' => $this->paymentGateway->slug,
                    'payload' => $transactionPayload,
                    'processed_at' => now(),
                ]);
            } elseif ($this->isFailedEvent($eventType)) {
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

            return response('ok', 200);
        } catch (\Throwable $e) {
            info('Stripe notify exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 500);
        }
    }

    protected function resolveOrder(array $eventObject): ?Order
    {
        $orderSlug = trim((string) (
            data_get($eventObject, 'metadata.order_slug')
            ?: data_get($eventObject, 'client_reference_id')
        ));
        $orderId = trim((string) data_get($eventObject, 'metadata.order_id', ''));
        $sessionId = trim((string) data_get($eventObject, 'id', ''));
        $paymentIntent = trim((string) data_get($eventObject, 'payment_intent', ''));

        if ($orderSlug !== '') {
            $order = Order::query()->where('slug', $orderSlug)->first();
            if ($order) {
                return $order;
            }
        }

        if ($orderId !== '' && ctype_digit($orderId)) {
            $order = Order::query()->find((int) $orderId);
            if ($order) {
                return $order;
            }
        }

        if ($sessionId !== '') {
            $order = Order::query()->where('tracking_code', $sessionId)->first();
            if ($order) {
                return $order;
            }
        }

        if ($paymentIntent !== '') {
            return Order::query()->where('gateway_reference', $paymentIntent)->first();
        }

        return null;
    }

    protected function isSuccessEvent(string $eventType, array $eventObject): bool
    {
        if ($eventType === 'checkout.session.completed') {
            return strtolower((string) data_get($eventObject, 'payment_status', '')) === 'paid';
        }

        return in_array($eventType, [
            'checkout.session.async_payment_succeeded',
            'payment_intent.succeeded',
            'charge.succeeded',
        ], true);
    }

    protected function isFailedEvent(string $eventType): bool
    {
        return in_array($eventType, [
            'checkout.session.async_payment_failed',
            'payment_intent.payment_failed',
            'charge.failed',
        ], true);
    }

    protected function isValidSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($signatureHeader === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, null);
            if ($key === 't' && $value !== null) {
                $timestamp = (int) $value;
            }
            if ($key === 'v1' && $value !== null) {
                $signatures[] = $value;
            }
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        $isMatched = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $isMatched = true;
                break;
            }
        }

        if (!$isMatched) {
            return false;
        }

        $tolerance = (int) ($this->paymentGateway->getParameter('stripe_signature_tolerance') ?: 300);
        return abs(time() - $timestamp) <= $tolerance;
    }

    protected function resolveSecretKey(): string
    {
        $secretKey = trim((string) $this->paymentGateway->client_secret);
        if ($secretKey === '' && function_exists('gss')) {
            $secretKey = trim((string) gss('ecommerce_stripe_api_key'));
        }

        return $secretKey;
    }

    protected function apiBaseUrl(): string
    {
        if (!empty($this->paymentGateway->endpoint)) {
            return rtrim((string) $this->paymentGateway->endpoint, '/');
        }

        return 'https://api.stripe.com';
    }

    protected function mergeStripePayload($existingPayload, array $stripePayload): array
    {
        $payload = is_array($existingPayload) ? $existingPayload : [];
        $payload['stripe'] = $stripePayload;
        return $payload;
    }

    protected function resolveSuccessUrl(Order $order): string
    {
        if (Route::has('frontend.orders.success')) {
            return route('frontend.orders.success', ['slug' => $order->slug]) . '?session_id={CHECKOUT_SESSION_ID}';
        }

        return url('/orders/' . rawurlencode((string) $order->slug) . '/success?session_id={CHECKOUT_SESSION_ID}');
    }

    protected function resolveCancelUrl(Order $order): string
    {
        if (Route::has('frontend.orders.waiting')) {
            return route('frontend.orders.waiting', ['slug' => $order->slug]);
        }

        if (Route::has('frontend.orders.show')) {
            return route('frontend.orders.show', ['slug' => $order->slug]);
        }

        return url('/orders/' . rawurlencode((string) $order->slug));
    }
}
