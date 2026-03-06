# Upgrade Guide

This guide covers safe upgrade steps for projects that already installed older versions of `secretwebmaster/wncms-ecommerce`.

## Compatibility Principle

- Historical released `create_*` migrations must be treated as immutable.
- New schema requirements are delivered via additive migrations.
- Upgrade path must be safe for both:
  - Existing production databases
  - Fresh installs

## New Compatibility Migration

- Migration file:
  - `database/migrations/2026_03_06_121800_add_backward_compatibility_columns_for_billing_refactor.php`
- Purpose:
  - Add newly required billing/gateway/subscription columns if missing
  - Add required indexes/unique constraints defensively
  - Avoid destructive schema operations during upgrade

## Upgrade Steps

1. Backup database and `.env`.
2. Upgrade package version via composer.
3. Run migrations:
   - `php artisan migrate --force`
4. Clear cached config/routes/views:
   - `php artisan optimize:clear`
5. Re-seed payment gateways if needed:
   - `php artisan db:seed --class=\"Secretwebmaster\\WncmsEcommerce\\Database\\Seeders\\PaymentGatewaySeeder\"`
6. Validate key tables:
   - `orders`, `order_items`, `transactions`, `subscriptions`, `payment_gateways`, `plans`, `products`

## Post-Upgrade Checks

- Checkout callback endpoints return expected status for invalid signatures (4xx).
- Existing pending/failed orders still load and can be processed.
- Renewal command still executes:
  - `php artisan wncms-ecommerce:renew-subscriptions`
- Payment gateway settings include expected fields and existing secrets are intact.

## Rollback Strategy

- If upgrade fails, restore DB backup and previous lockfile/vendor state.
- The compatibility migration is forward-only by design; use backup restore for rollback.
