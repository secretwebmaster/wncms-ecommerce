<?php

namespace Secretwebmaster\WncmsEcommerce\Services\Managers;

use Secretwebmaster\WncmsEcommerce\Facades\PlanManager;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\Plan;
use Secretwebmaster\WncmsEcommerce\Models\Price;
use Secretwebmaster\WncmsEcommerce\Models\Transaction;

class OrderManager
{
    public function create($user, $price, $quantity = 1)
    {
        // find pending order with the same plan and price
        $order = $user->orders()->where('status', 'pending_payment')->whereHas('order_items', function ($query) use ($price) {
            $query->where('order_itemable_type', get_class($price))->where('order_itemable_id', $price->id);
        })->first();

        if ($order) {
            return $order;
        }

        // create empty order
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'total_amount' => 0,
        ]);

        $itemClass = get_class($price);

        // creater order item for the plan
        $orderItem = $order->order_items()->create([
            'order_id' => $order->id,
            'order_itemable_type' => $itemClass,
            'order_itemable_id' => $price->id,
            'quantity' => $quantity,
            'amount' => $price->amount,
        ]);

        foreach ($order->order_items as $item) {
            $order->total_amount += $item->amount * $item->quantity;
        }

        $order->save();

        return $order;
    }

    public function complete(Order $order, $ref_id = null)
    {
        Transaction::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'status' => 'completed',
            'payment_method' => $order->payment_gateway->slug ?? 'unknown',
            'ref_id' => $ref_id,
        ]);

        foreach ($order->order_items as $item) {
            $orderable = $item->order_itemable;

            // Case 1: price-based purchase
            if ($orderable instanceof Price) {
                $price = $orderable;
                $priceable = $price->priceable;

                if ($priceable instanceof Plan) {
                    $user = $order->user;
                    PlanManager::subscribe($user, $priceable, $price);
                } 
                elseif ($priceable instanceof Product) {
                    dd('design later $priceable instanceof Product in OrderManager');
                    $user = $order->user;
                    info("User {$user->id} purchased product {$priceable->id} (via price)");
                }
            }

            // Case 2: direct product purchase
            elseif ($orderable instanceof Product) {
                $user = $order->user;
                info("User {$user->id} purchased product {$orderable->id} (direct)");
            }

            // Unknown type
            else {
                info('Unknown order item type', [
                    'type' => get_class($orderable)
                ]);
            }
        }

        $order->update([
            'payment_method' => $order->payment_gateway->slug ?? 'unknown',
            'status' => 'completed',
        ]);

        return $order;
    }
}
