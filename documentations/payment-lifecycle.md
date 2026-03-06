# Payment And Subscription Lifecycle

## One-time Flow

1. Create order via `OrderManager::createOneTimeOrder()`
2. Redirect to selected gateway processor `process()`
3. Gateway callback hits `api.v1.payment.notify(.gateway)`
4. Processor validates signature and calls `OrderManager::markPaid()`
5. Order becomes `paid` (or `completed` after downstream handling)
6. Transaction is recorded with status `succeeded`

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
