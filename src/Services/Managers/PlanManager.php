<?php

namespace Secretwebmaster\WncmsEcommerce\Services\Managers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Wncms\Facades\Wncms;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\Plan;
use Secretwebmaster\WncmsEcommerce\Models\Price;
use Secretwebmaster\WncmsEcommerce\Models\Subscription;

class PlanManager
{
    public function calculateExpiredAt($price, $from = null)
    {
        if (($price->is_lifetime ?? false) === true) {
            return null;
        }

        $from = $from ? Carbon::parse($from) : now();
        $duration = (int) ($price->duration ?? 1);
        $durationUnit = $price->duration_unit ?? 'month';

        return $this->calculatePeriodEnd($duration, $durationUnit, $from);
    }

    public function calculatePeriodEnd(int $intervalCount, string $intervalUnit, ?Carbon $from = null): Carbon
    {
        $from = $from ?: now();

        return match ($intervalUnit) {
            'day' => $from->copy()->addDays($intervalCount),
            'week' => $from->copy()->addWeeks($intervalCount),
            'month' => $from->copy()->addMonths($intervalCount),
            'year' => $from->copy()->addYears($intervalCount),
            default => $from->copy()->addMonths($intervalCount),
        };
    }

    public function activateFromPaidOrder(Order $order): Subscription
    {
        return DB::transaction(function () use ($order) {
            $order->loadMissing('order_items.order_itemable', 'user');

            $planItem = $order->order_items->first(function ($item) {
                return $item->order_itemable instanceof Plan || ($item->order_itemable instanceof Price && $item->order_itemable->priceable instanceof Plan);
            });

            if (!$planItem) {
                throw new \RuntimeException('Paid subscription order does not include a plan item.');
            }

            $plan = $planItem->order_itemable instanceof Plan
                ? $planItem->order_itemable
                : $planItem->order_itemable->priceable;

            $startAt = now();
            $intervalCount = (int) ($planItem->billing_interval_count ?: $plan->billing_interval_count ?: 1);
            $intervalUnit = $planItem->billing_interval ?: ($plan->billing_interval ?: 'month');
            $periodEnd = $this->calculatePeriodEnd($intervalCount, $intervalUnit, $startAt);

            $subscription = $order->subscription_id
                ? Subscription::query()->find($order->subscription_id)
                : null;

            if ($subscription) {
                $attributes = is_array($subscription->attributes) ? $subscription->attributes : [];
                if (in_array($subscription->status, ['past_due', 'grace', 'suspended'], true)) {
                    $attributes = $this->appendLifecycleEvent(
                        $attributes,
                        'reactivated',
                        'callback',
                        'paid_subscription_order',
                        ['from_status' => $subscription->status, 'order_id' => $order->id]
                    );
                }

                $subscription->update([
                    'status' => 'active',
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'billing_interval_count' => $intervalCount,
                    'billing_interval' => $intervalUnit,
                    'payment_gateway_id' => $order->payment_gateway_id,
                    'last_transaction_id' => $order->transactions()->latest('id')->value('id'),
                    'started_at' => $subscription->started_at ?: $startAt,
                    'current_period_start' => $startAt,
                    'current_period_end' => $periodEnd,
                    'next_billing_at' => $periodEnd,
                    'expired_at' => null,
                    'cancelled_at' => null,
                    'cancel_at_period_end' => false,
                    'attributes' => $attributes,
                ]);
            } else {
                $attributes = $this->appendLifecycleEvent(
                    [],
                    'created',
                    'callback',
                    'paid_subscription_order',
                    ['order_id' => $order->id]
                );

                $subscription = Subscription::create([
                    'user_id' => $order->user_id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'price_id' => $planItem->order_itemable instanceof Price ? $planItem->order_itemable->id : null,
                    'payment_gateway_id' => $order->payment_gateway_id,
                    'last_transaction_id' => $order->transactions()->latest('id')->value('id'),
                    'currency' => $order->currency,
                    'amount' => $order->total_amount,
                    'billing_interval_count' => $intervalCount,
                    'billing_interval' => $intervalUnit,
                    'grace_days' => (int) ($plan->grace_days ?: config('wncms-ecommerce.default_grace_days', 3)),
                    'subscribed_at' => $startAt,
                    'started_at' => $startAt,
                    'current_period_start' => $startAt,
                    'current_period_end' => $periodEnd,
                    'next_billing_at' => $periodEnd,
                    'trial_ends_at' => ($plan->free_trial_duration ?? 0) > 0 ? $startAt->copy()->addDays((int) $plan->free_trial_duration) : null,
                    'attributes' => $attributes,
                ]);

                $order->update(['subscription_id' => $subscription->id]);
            }

            if ($order->status === 'paid') {
                $order->update(['status' => 'completed']);
            }

            return $subscription->fresh();
        });
    }

    /**
     * Backward-compatible direct subscribe method.
     */
    public function subscribe($user, $plan, $price)
    {
        $existing = $user->subscriptions()
            ->where('plan_id', $plan->id)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->first();

        $now = now();
        $expiredAt = $this->calculateExpiredAt($price, $existing?->current_period_end ?: $now);

        if ($existing) {
            $existing->update([
                'status' => 'active',
                'price_id' => $price->id,
                'amount' => $price->amount,
                'currency' => $plan->currency ?? 'USD',
                'billing_interval_count' => (int) ($price->duration ?: 1),
                'billing_interval' => $price->duration_unit ?: 'month',
                'current_period_start' => $existing->current_period_end ?: $now,
                'current_period_end' => $expiredAt,
                'next_billing_at' => $expiredAt,
                'expired_at' => null,
                'cancelled_at' => null,
            ]);

            return $existing->fresh();
        }

        return $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'price_id' => $price->id,
            'status' => 'active',
            'amount' => $price->amount,
            'currency' => $plan->currency ?? 'USD',
            'billing_interval_count' => (int) ($price->duration ?: 1),
            'billing_interval' => $price->duration_unit ?: 'month',
            'subscribed_at' => $now,
            'started_at' => $now,
            'current_period_start' => $now,
            'current_period_end' => $expiredAt,
            'next_billing_at' => $expiredAt,
            'grace_days' => (int) ($plan->grace_days ?: config('wncms-ecommerce.default_grace_days', 3)),
        ]);
    }

    public function unsubscribe($user, $subscription)
    {
        $subscriptionClass = wncms()->getModelClass('subscription');

        if (!($subscription instanceof $subscriptionClass)) {
            $subscription = $user->subscriptions()->find($subscription);
        }

        if (!$subscription || $subscription->user_id !== $user->id) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        if ($subscription->status === 'cancelled') {
            return response()->json(['error' => 'Subscription already cancelled'], 400);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_at_period_end' => true,
        ]);

        return response()->json(['message' => 'Subscription cancelled'], 200);
    }

    public function createRenewalOrders(): int
    {
        $created = 0;
        $orderManager = app('order-manager');

        Subscription::query()
            ->dueForRenewal()
            ->with('plan', 'payment_gateway', 'user')
            ->chunkById((int) config('wncms-ecommerce.renewal_chunk_size', 100), function ($subscriptions) use (&$created, $orderManager) {
                foreach ($subscriptions as $subscription) {
                    if (!$subscription->plan || !$subscription->user || !$subscription->payment_gateway_id) {
                        continue;
                    }

                    $existingPending = Order::query()
                        ->where('subscription_id', $subscription->id)
                        ->where('order_type', 'subscription_renewal')
                        ->where('status', 'pending_payment')
                        ->exists();

                    if ($existingPending) {
                        continue;
                    }

                    $orderManager->createSubscriptionOrder(
                        user: $subscription->user,
                        plan: $subscription->plan,
                        paymentGateway: $subscription->payment_gateway,
                        reason: 'subscription_renewal',
                        subscription: $subscription,
                    );

                    $subscription->update(['status' => 'past_due']);
                    $created++;
                }
            });

        return $created;
    }

    public function advanceLifecycleStates(): array
    {
        $result = [
            'to_grace' => 0,
            'to_suspended' => 0,
            'failed_orders' => 0,
        ];
        $orderManager = app('order-manager');
        $now = now();

        Subscription::query()
            ->whereIn('status', ['past_due', 'grace'])
            ->whereNotNull('next_billing_at')
            ->with('payment_gateway')
            ->chunkById((int) config('wncms-ecommerce.lifecycle_chunk_size', 100), function ($subscriptions) use (&$result, $orderManager, $now) {
                foreach ($subscriptions as $subscription) {
                    if (!$subscription->next_billing_at) {
                        continue;
                    }

                    $graceDays = max(0, (int) ($subscription->grace_days ?: config('wncms-ecommerce.default_grace_days', 3)));
                    $graceDeadline = $subscription->next_billing_at->copy()->addDays($graceDays);

                    if ($now->lessThanOrEqualTo($graceDeadline)) {
                        if ($subscription->status !== 'grace') {
                            $attributes = is_array($subscription->attributes) ? $subscription->attributes : [];
                            $attributes = $this->appendLifecycleEvent(
                                $attributes,
                                'grace_entered',
                                'scheduler',
                                'renewal_unpaid',
                                ['grace_deadline' => $graceDeadline->toDateTimeString()]
                            );

                            $subscription->update([
                                'status' => 'grace',
                                'attributes' => $attributes,
                            ]);
                            $result['to_grace']++;
                        }
                        continue;
                    }

                    if ($subscription->status !== 'suspended') {
                        $pendingRenewals = Order::query()
                            ->where('subscription_id', $subscription->id)
                            ->where('order_type', 'subscription_renewal')
                            ->where('status', 'pending_payment')
                            ->get();

                        foreach ($pendingRenewals as $order) {
                            $externalId = 'AUTO-SUSPEND-' . $order->id;
                            $orderManager->markFailed($order, [
                                'status' => 'failed',
                                'external_id' => $externalId,
                                'gateway_reference' => $externalId,
                                'ref_id' => $externalId,
                                'payment_method' => $subscription->payment_gateway?->slug ?? $order->payment_method,
                                'payload' => [
                                    'source' => 'subscription_lifecycle_scheduler',
                                    'reason' => 'grace_expired',
                                    'subscription_id' => $subscription->id,
                                ],
                            ]);
                            $result['failed_orders']++;
                        }

                        $attributes = is_array($subscription->attributes) ? $subscription->attributes : [];
                        $attributes = $this->appendLifecycleEvent(
                            $attributes,
                            'suspended',
                            'scheduler',
                            'grace_expired',
                            ['grace_deadline' => $graceDeadline->toDateTimeString()]
                        );

                        $subscription->update([
                            'status' => 'suspended',
                            'expired_at' => $now,
                            'attributes' => $attributes,
                        ]);
                        $result['to_suspended']++;
                    }
                }
            });

        return $result;
    }

    public function canSubscribe($user, $plan, $price = null): bool
    {
        if ($user->subscriptions()->where('plan_id', $plan->id)->whereIn('status', ['active', 'trialing', 'grace'])->exists()) {
            return false;
        }

        if ($price && isset($price->amount) && isset($user->balance) && (float) $user->balance < (float) $price->amount) {
            return false;
        }

        return true;
    }

    public function create($data)
    {
        $plan = wncms()->getModelClass('plan')::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Wncms::getUniqueSlug('plans'),
            'description' => $data['description'] ?? null,
            'free_trial_duration' => $data['free_trial_duration'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'is_recurring' => (bool) ($data['is_recurring'] ?? true),
            'billing_interval_count' => $data['billing_interval_count'] ?? 1,
            'billing_interval' => $data['billing_interval'] ?? 'month',
            'grace_days' => $data['grace_days'] ?? config('wncms-ecommerce.default_grace_days', 3),
            'price_amount' => $data['price_amount'] ?? 0,
            'setup_fee_amount' => $data['setup_fee_amount'] ?? 0,
            'currency' => $data['currency'] ?? config('wncms-ecommerce.currency', 'USD'),
            'attributes' => $data['attributes'] ?? null,
        ]);

        if (!empty($data['prices']) && is_array($data['prices'])) {
            foreach ($data['prices'] as $priceData) {
                $plan->prices()->create([
                    'amount' => $priceData['amount'],
                    'duration' => $priceData['duration'] ?? null,
                    'duration_unit' => $priceData['duration_unit'] ?? null,
                    'is_lifetime' => (bool) ($priceData['is_lifetime'] ?? false),
                ]);
            }
        }

        return $plan;
    }

    public function update($plan, $data)
    {
        $plan->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $plan->slug,
            'description' => $data['description'] ?? $plan->description,
            'free_trial_duration' => $data['free_trial_duration'] ?? $plan->free_trial_duration,
            'status' => $data['status'] ?? $plan->status,
            'is_recurring' => (bool) ($data['is_recurring'] ?? $plan->is_recurring),
            'billing_interval_count' => $data['billing_interval_count'] ?? $plan->billing_interval_count,
            'billing_interval' => $data['billing_interval'] ?? $plan->billing_interval,
            'grace_days' => $data['grace_days'] ?? $plan->grace_days,
            'price_amount' => $data['price_amount'] ?? $plan->price_amount,
            'setup_fee_amount' => $data['setup_fee_amount'] ?? $plan->setup_fee_amount,
            'currency' => $data['currency'] ?? $plan->currency,
            'attributes' => $data['attributes'] ?? $plan->attributes,
        ]);

        if (!empty($data['prices']) && is_array($data['prices'])) {
            $priceIds = [];

            foreach ($data['prices'] as $priceData) {
                $price = $plan->prices()->updateOrCreate(
                    [
                        'duration' => $priceData['duration'] ?? null,
                        'duration_unit' => $priceData['duration_unit'] ?? null,
                        'is_lifetime' => (bool) ($priceData['is_lifetime'] ?? false),
                    ],
                    [
                        'amount' => $priceData['amount'],
                    ]
                );

                $priceIds[] = $price->id;
            }

            $plan->prices()->whereNotIn('id', $priceIds)->delete();
        }

        return $plan;
    }

    protected function appendLifecycleEvent(array $attributes, string $event, string $source, string $reason, array $extra = []): array
    {
        $events = $attributes['lifecycle_events'] ?? [];
        if (!is_array($events)) {
            $events = [];
        }

        $events[] = array_merge([
            'event' => $event,
            'source' => $source,
            'reason' => $reason,
            'at' => now()->toDateTimeString(),
        ], $extra);

        $attributes['lifecycle_events'] = array_slice($events, -50);
        return $attributes;
    }
}
