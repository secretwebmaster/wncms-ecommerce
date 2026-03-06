# WNCMS Ecommerce

`secretwebmaster/wncms-ecommerce` is a reusable WNCMS billing package for online store and SaaS scenarios.

## Scope

- One-time checkout: `Order`, `OrderItem`, `Transaction`
- Recurring subscriptions: `Plan`, `Subscription`, renewal orders
- Payment gateway processing: `PaymentGateway` + processor classes

## Requirements

- Host project includes `secretwebmaster/wncms-core:^6.0`
- Host project can run Laravel migrations and seeders

## Installation (Fresh Install)

```bash
composer require secretwebmaster/wncms-ecommerce
php artisan vendor:publish --tag=wncms-ecommerce-config
php artisan migrate
php artisan db:seed --class="Secretwebmaster\\WncmsEcommerce\\Database\\Seeders\\PaymentGatewaySeeder"
```

After installation:

- confirm backend payment gateway pages load
- configure gateway credentials in backend settings

## Upgrade (Existing Project)

```bash
composer update secretwebmaster/wncms-ecommerce
php artisan migrate --force
php artisan optimize:clear
```

Optional after upgrade:

```bash
php artisan db:seed --class="Secretwebmaster\\WncmsEcommerce\\Database\\Seeders\\PaymentGatewaySeeder"
```

See `documentations/upgrade-guide.md` for version-specific checks.

## Commands

```bash
php artisan wncms-ecommerce:pay-order {orderSlug} {paymentGatewaySlug}
php artisan wncms-ecommerce:renew-subscriptions
php artisan wncms-ecommerce:advance-subscriptions
php artisan wncms-ecommerce:reconcile-transactions {--gateway=} {--date-from=} {--date-to=} {--json}
```

## Testing

```bash
composer test
```

## Documentation

- `CHANGELOG.md`
- `documentations/architecture.md`
- `documentations/model-reference.md`
- `documentations/payment-lifecycle.md`
- `documentations/production-readiness-design.md`
- `documentations/upgrade-guide.md`
- `documentations/release-checklist.md`
- `documentations/test-matrix.md`
- `documentations/operations-runbook.md`
- `documentations/to-do.md`

## Design Goal

Keep package logic reusable for any online store/SaaS. Project-specific tenant/domain/provision orchestration should be implemented in host project layers, not hard-coded in this package.
