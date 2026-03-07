# WNCMS Ecommerce

`secretwebmaster/wncms-ecommerce` is a reusable WNCMS billing package for online store and SaaS scenarios.

## Scope

- One-time checkout: `Order`, `OrderItem`, `Transaction`
- Recurring subscriptions: `Plan`, `Subscription`, renewal orders
- Payment gateway processing: `PaymentGateway` + processor classes

## Documentation

- `documentations/architecture.md`
- `documentations/payment-lifecycle.md`
- `documentations/model-reference.md`
- `documentations/production-readiness-design.md`
- `documentations/to-do.md`
- `documentations/upgrade-guide.md`
- `documentations/test-matrix.md`

## Installation

```bash
composer require secretwebmaster/wncms-ecommerce
```

## Commands

```bash
php artisan wncms-ecommerce:pay-order {orderSlug} {paymentGatewaySlug}
php artisan wncms-ecommerce:renew-subscriptions
php artisan wncms-ecommerce:advance-subscriptions
```

## Testing

```bash
composer test
```

## Design Goal

Keep package logic reusable for any online store/SaaS. Project-specific tenant/domain/provision orchestration should be implemented in host project layers, not hard-coded in this package.
