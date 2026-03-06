# Payment And Subscription Lifecycle

## One-time Flow

1. Create order via `OrderManager::createOneTimeOrder()`
2. Redirect to selected gateway processor `process()`
3. For PayPal, return route (`frontend.orders.paypal.return`) captures checkout token and validates token/order binding.
4. Gateway callback hits `api.v1.payment.notify(.gateway)`
5. Processor verifies callback authenticity first, then calls `OrderManager::markPaid()`/`markFailed()`
6. Order becomes `paid` (or `completed` after downstream handling)
7. Transaction is recorded idempotently with stable `external_id`

## Recurring Flow

1. Initial subscription checkout creates `order_type=subscription_initial`
2. On paid callback, `PlanManager::activateFromPaidOrder()` creates/updates subscription
3. Scheduler/command runs `wncms-ecommerce:renew-subscriptions`
4. Due subscriptions create `order_type=subscription_renewal` and enter `past_due`
5. Renewal payment callback marks order paid and advances subscription period

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
- `cancelled`
- `expired`

## Idempotency Rule

Gateway callbacks must provide stable identifiers (`trade_id` / `external_id`).
`OrderManager::markPaid()` uses `updateOrCreate` on (`order_id`, `external_id`) to avoid duplicate payment side effects.
