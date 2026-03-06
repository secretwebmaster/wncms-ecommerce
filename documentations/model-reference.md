# Model Reference (Billing Core)

## `orders`

Primary checkout record.

Key fields:
- `order_type`: `one_time|subscription_initial|subscription_renewal`
- `status`
- `currency`
- `subtotal_amount|tax_amount|discount_amount|total_amount`
- `subscription_id` (nullable)
- `payment_gateway_id`
- `paid_at|failed_at`

## `order_items`

Order line snapshot independent from mutable catalog data.

Key fields:
- `order_itemable_type|order_itemable_id` (morph)
- `name`
- `unit_amount|quantity|total_amount`
- `billing_interval_count|billing_interval` (nullable)
- `attributes` JSON

## `transactions`

Payment ledger and webhook reconciliation source.

Key fields:
- `order_id`
- `subscription_id` (nullable)
- `payment_gateway_id`
- `type`: `charge|renewal|refund|adjustment`
- `direction`: `debit|credit`
- `status`
- `external_id` (idempotency key)
- `payload` JSON
- `processed_at`

## `subscriptions`

Recurring contract state machine.

Key fields:
- `user_id|plan_id|payment_gateway_id`
- `status`
- `amount|currency`
- `billing_interval_count|billing_interval`
- `current_period_start|current_period_end|next_billing_at`
- `cancel_at_period_end|cancelled_at|expired_at`
- `last_transaction_id`

## `plans`

Recurring product definition.

Key fields:
- `is_recurring`
- `billing_interval_count|billing_interval`
- `price_amount|setup_fee_amount|currency`
- `grace_days|free_trial_duration`
