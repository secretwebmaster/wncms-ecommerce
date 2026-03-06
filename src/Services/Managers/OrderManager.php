<?php

namespace Secretwebmaster\WncmsEcommerce\Services\Managers;

use Illuminate\Support\Facades\DB;
use Secretwebmaster\WncmsEcommerce\Facades\PlanManager as PlanManagerFacade;
use Secretwebmaster\WncmsEcommerce\Models\Credit;
use Secretwebmaster\WncmsEcommerce\Models\CreditTransaction;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\Plan;
use Secretwebmaster\WncmsEcommerce\Models\Product;
use Secretwebmaster\WncmsEcommerce\Models\Subscription;
use Secretwebmaster\WncmsEcommerce\Models\Transaction;

class OrderManager
{
    /**
     * Backward-compatible legacy entry.
     */
    public function create($user, $price, $quantity = 1)
    {
        $priceable = $price->priceable ?? null;

        if ($priceable instanceof Plan) {
            $order = $this->createSubscriptionOrder($user, $priceable);
            $item = $order->order_items()->first();
            if ($item) {
                $item->update([
                    'order_itemable_type' => get_class($price),
                    'order_itemable_id' => $price->id,
                    'unit_amount' => $price->amount,
                    'amount' => $price->amount,
                    'total_amount' => $price->amount,
                    'billing_interval_count' => $price->duration ?: $item->billing_interval_count,
                    'billing_interval' => $price->duration_unit ?: $item->billing_interval,
                ]);
            }

            return $this->refreshTotals($order);
        }

        if ($priceable instanceof Product) {
            return $this->createOneTimeOrder($user, $priceable, (int) $quantity);
        }

        if ($price instanceof Product) {
            return $this->createOneTimeOrder($user, $price, (int) $quantity);
        }

        throw new \InvalidArgumentException('Unsupported price/order item type for order creation.');
    }

    public function createOneTimeOrder($user, Product $product, int $quantity = 1, $paymentGateway = null): Order
    {
        $quantity = max(1, $quantity);
        $currency = $product->currency ?? config('wncms-ecommerce.currency', 'USD');

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'order_type' => 'one_time',
            'billing_reason' => 'purchase',
            'currency' => $currency,
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'payment_gateway_id' => $paymentGateway?->id,
        ]);

        $this->addOrderItem(
            order: $order,
            orderItemable: $product,
            quantity: $quantity,
            unitAmount: (float) $product->price,
            currency: $currency,
            name: $product->name,
            attributes: [
                'sale_type' => $product->sale_type,
                'product_type' => $product->type,
            ],
        );

        return $this->refreshTotals($order);
    }

    public function createSubscriptionOrder($user, Plan $plan, $paymentGateway = null, string $reason = 'subscription_initial', ?Subscription $subscription = null): Order
    {
        $amount = (float) $plan->price_amount + ($reason === 'subscription_initial' ? (float) $plan->setup_fee_amount : 0.0);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'order_type' => $reason === 'subscription_renewal' ? 'subscription_renewal' : 'subscription_initial',
            'billing_reason' => $reason,
            'currency' => $plan->currency ?? config('wncms-ecommerce.currency', 'USD'),
            'subtotal_amount' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'subscription_id' => $subscription?->id,
            'payment_gateway_id' => $paymentGateway?->id,
        ]);

        $this->addOrderItem(
            order: $order,
            orderItemable: $plan,
            quantity: 1,
            unitAmount: $amount,
            currency: $plan->currency ?? config('wncms-ecommerce.currency', 'USD'),
            name: $plan->name,
            intervalCount: (int) $plan->billing_interval_count,
            intervalUnit: $plan->billing_interval,
            attributes: [
                'is_recurring' => (bool) $plan->is_recurring,
                'setup_fee_amount' => (float) $plan->setup_fee_amount,
            ],
        );

        return $this->refreshTotals($order);
    }

    public function addOrderItem(
        Order $order,
        $orderItemable,
        int $quantity,
        float $unitAmount,
        string $currency,
        ?string $name = null,
        ?int $intervalCount = null,
        ?string $intervalUnit = null,
        array $attributes = [],
    ) {
        $quantity = max(1, $quantity);
        $totalAmount = round($unitAmount * $quantity, 2);

        return $order->order_items()->create([
            'order_itemable_type' => get_class($orderItemable),
            'order_itemable_id' => $orderItemable->id,
            'name' => $name,
            'currency' => $currency,
            'quantity' => $quantity,
            'unit_amount' => $unitAmount,
            'amount' => $unitAmount,
            'total_amount' => $totalAmount,
            'billing_interval_count' => $intervalCount,
            'billing_interval' => $intervalUnit,
            'attributes' => $attributes,
        ]);
    }

    public function refreshTotals(Order $order): Order
    {
        // Always reload items from DB to avoid stale relation cache after in-request item updates.
        $order->load('order_items');
        $subtotal = $order->order_items->sum('total_amount');

        $order->update([
            'subtotal_amount' => $subtotal,
            'total_amount' => max(0, $subtotal + (float) $order->tax_amount - (float) $order->discount_amount),
        ]);

        return $order->fresh(['order_items']);
    }

    public function markPaid(Order $order, array $transactionData = []): Order
    {
        return DB::transaction(function () use ($order, $transactionData) {
            $order->refresh();

            if (in_array($order->status, ['paid', 'completed'], true)) {
                return $order;
            }

            $status = $transactionData['status'] ?? 'succeeded';
            $externalId = $transactionData['external_id'] ?? null;

            Transaction::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'external_id' => $externalId,
                ],
                [
                    'subscription_id' => $order->subscription_id,
                    'payment_gateway_id' => $order->payment_gateway_id,
                    'type' => $order->order_type === 'subscription_renewal' ? 'renewal' : 'charge',
                    'direction' => 'debit',
                    'status' => $status,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'payment_method' => $transactionData['payment_method'] ?? ($order->payment_gateway?->slug ?? $order->payment_method),
                    'ref_id' => $transactionData['ref_id'] ?? $order->tracking_code,
                    'processed_at' => $transactionData['processed_at'] ?? now(),
                    'payload' => $transactionData['payload'] ?? null,
                    'is_fraud' => (bool) ($transactionData['is_fraud'] ?? false),
                ]
            );

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'failed_at' => null,
                'payment_method' => $transactionData['payment_method'] ?? ($order->payment_gateway?->slug ?? $order->payment_method),
                'gateway_reference' => $transactionData['gateway_reference'] ?? $order->gateway_reference,
            ]);

            if ((string) $order->billing_reason === 'credit_recharge') {
                $this->applyCreditRecharge($order);
            }

            if (in_array($order->order_type, ['subscription_initial', 'subscription_renewal'], true)) {
                PlanManagerFacade::activateFromPaidOrder($order);
            }

            return $order->fresh();
        });
    }

    public function markFailed(Order $order, array $transactionData = []): Order
    {
        $order->update([
            'status' => 'failed',
            'failed_at' => now(),
            'payload' => $transactionData['payload'] ?? $order->payload,
            'gateway_reference' => $transactionData['gateway_reference'] ?? $order->gateway_reference,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'subscription_id' => $order->subscription_id,
            'payment_gateway_id' => $order->payment_gateway_id,
            'type' => $order->order_type === 'subscription_renewal' ? 'renewal' : 'charge',
            'direction' => 'debit',
            'status' => 'failed',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'payment_method' => $transactionData['payment_method'] ?? ($order->payment_gateway?->slug ?? $order->payment_method),
            'external_id' => $transactionData['external_id'] ?? null,
            'ref_id' => $transactionData['ref_id'] ?? null,
            'processed_at' => now(),
            'payload' => $transactionData['payload'] ?? null,
            'is_fraud' => (bool) ($transactionData['is_fraud'] ?? false),
        ]);

        return $order->fresh();
    }

    /**
     * Backward-compatible alias used by existing gateway implementations.
     */
    public function complete(Order $order, $ref_id = null)
    {
        return $this->markPaid($order, [
            'ref_id' => $ref_id,
            'external_id' => $ref_id,
            'payment_method' => $order->payment_gateway?->slug ?? 'unknown',
        ]);
    }

    protected function applyCreditRecharge(Order $order): void
    {
        $payload = is_array($order->payload) ? $order->payload : [];
        if ((bool) data_get($payload, 'credit_recharge.credited', false)) {
            return;
        }

        $amount = round((float) $order->total_amount, 2);
        if ($amount <= 0) {
            return;
        }

        $credit = Credit::query()->lockForUpdate()->firstOrCreate(
            ['user_id' => $order->user_id, 'type' => 'balance'],
            ['amount' => 0]
        );
        $credit->amount = round((float) $credit->amount + $amount, 2);
        $credit->save();

        $remark = __('wncms::word.tgp_credit_recharge_order_remark', ['order' => $order->slug]);
        $hasTransaction = CreditTransaction::query()
            ->where('user_id', $order->user_id)
            ->where('transaction_type', 'recharge')
            ->where('remark', $remark)
            ->exists();

        if (!$hasTransaction) {
            CreditTransaction::query()->create([
                'user_id' => $order->user_id,
                'credit_type' => 'balance',
                'amount' => $amount,
                'transaction_type' => 'recharge',
                'remark' => $remark,
            ]);
        }

        $payload['credit_recharge'] = [
            'credited' => true,
            'amount' => $amount,
            'credited_at' => now()->toDateTimeString(),
        ];
        $order->update(['payload' => $payload]);
    }
}
