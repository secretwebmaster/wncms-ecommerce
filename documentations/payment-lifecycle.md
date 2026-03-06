# Payment And Subscription Lifecycle

## One-time Flow

1. Create order via `OrderManager::createOneTimeOrder()`
2. Redirect to selected gateway processor `process()` (PayPal/Stripe/EPUSDT/ECPay)
3. For PayPal, return route (`frontend.orders.paypal.return`) captures checkout token and validates token/order binding.
4. For ECPay, user is redirected by hosted auto-submit form to ECPay checkout.
5. Gateway callback hits `api.v1.payment.notify(.gateway)`
6. Processor verifies callback authenticity first, then calls `OrderManager::markPaid()`/`markFailed()`
7. Order becomes `paid` (or `completed` after downstream handling)
8. Transaction is recorded idempotently with stable `external_id`

## Recurring Flow

1. Initial subscription checkout creates `order_type=subscription_initial`
2. On paid callback, `PlanManager::activateFromPaidOrder()` creates/updates subscription
3. Scheduler/command runs `wncms-ecommerce:renew-subscriptions`
4. Due subscriptions create `order_type=subscription_renewal` and enter `past_due`
5. Scheduler/command runs `wncms-ecommerce:advance-subscriptions` to move overdue subscriptions through `grace`/`suspended`
6. Renewal payment callback marks order paid and advances subscription period (reactivation supported from `past_due`/`grace`/`suspended`)

## Statuses

### Order

- `draft`
- `pending_payment`
- `paid`
- `processing`
- `completed`
- `failed`
- `cancelled`
- `refunded`

### Transaction

- `pending`
- `succeeded`
- `failed`
- `refunded`
- `cancelled`

### Subscription

- `pending`
- `trialing`
- `active`
- `past_due`
- `grace`
- `suspended`
- `cancelled`
- `expired`

## Idempotency Rule

Gateway callbacks must provide stable identifiers (`trade_id` / `external_id`).
`OrderManager::markPaid()` uses `updateOrCreate` on (`order_id`, `external_id`) to avoid duplicate payment side effects.
