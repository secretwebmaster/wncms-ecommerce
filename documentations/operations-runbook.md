# Operations Runbook

This runbook covers payment callback failures, duplicate callbacks, and reconciliation drift.

## 1) Callback Verification Failures

Symptoms:
- Callback endpoint returns `400 invalid signature`.
- Orders remain `pending_payment` after gateway payment.

Checklist:
1. Confirm gateway config:
   - Stripe: `webhook_secret`
   - PayPal: webhook id (`webhook_secret`/configured webhook id)
   - EPUSDT: `client_secret`
   - ECPay: `client_id=MerchantID`, `client_secret=HashKey`, `webhook_secret=HashIV`
2. Confirm callback URL matches current gateway slug.
3. Re-send callback from gateway dashboard once config is corrected.
4. Review logs by `correlation_id`, `gateway`, `event_id`, `verification_result`.

## 2) Duplicate Callback Events

Symptoms:
- Same gateway event appears multiple times.
- Concern about duplicate transaction/order side effects.

Checklist:
1. Confirm stable event identifier exists (`external_id` / trade id / event id).
2. Verify idempotent behavior:
   - `markPaid` uses `updateOrCreate(order_id + external_id)`
   - `markFailed` uses `updateOrCreate(order_id + external_id)`
3. If duplicates still appear, inspect whether gateway is sending a different event id each retry.

## 3) Reconciliation Drift

Use reconciliation command:

```bash
php artisan wncms-ecommerce:reconcile-transactions
php artisan wncms-ecommerce:reconcile-transactions --gateway=stripe --date-from=2026-03-01 --date-to=2026-03-31
php artisan wncms-ecommerce:reconcile-transactions --json
```

Drift patterns detected:
- `paid/completed` order without succeeded transaction
- `pending_payment` order with succeeded transaction
- `failed` order without failed/succeeded transaction

## 4) Renewal Lifecycle Operations

Commands:

```bash
php artisan wncms-ecommerce:renew-subscriptions
php artisan wncms-ecommerce:advance-subscriptions
```

Expected progression:
- `active` -> `past_due` -> `grace` -> `suspended`
- Successful renewal payment reactivates subscription to `active`

## 5) Incident Notes

- Record incident time window and impacted order/subscription ids.
- Attach command outputs and key correlation ids.
- Add postmortem items to package `documentations/to-do.md` if product-level fixes are required.
