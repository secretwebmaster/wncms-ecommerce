# Upgrade Guide

This guide covers safe upgrade steps for already-installed projects.

## Supported Paths

| from | to | migration required | key note |
| --- | --- | --- | --- |
| `<=1.0.0` | `1.1.x` | yes | run additive compatibility migration |

## Compatibility Principle

- Historical released `create_*` migrations are immutable.
- New schema requirements ship via additive forward migrations.
- Upgrade must stay safe for existing production databases and fresh installs.

## Upgrade `1.0.x` -> `1.1.x`

### 1. Prepare rollback point

- Backup database and `.env`.
- Pause scheduler jobs that mutate orders/subscriptions during deployment.

### 2. Update package version

```bash
composer update secretwebmaster/wncms-ecommerce
```

### 3. Run additive migrations

```bash
php artisan migrate --force
```

Compatibility migration used by this upgrade:
- `database/migrations/2026_03_06_121800_add_backward_compatibility_columns_for_billing_refactor.php`

### 4. Clear cached runtime metadata

```bash
php artisan optimize:clear
```

### 5. Re-seed gateways when defaults are needed

```bash
php artisan db:seed --class="Secretwebmaster\\WncmsEcommerce\\Database\\Seeders\\PaymentGatewaySeeder"
```

## Post-Upgrade Verification

- Callback endpoints reject unverifiable payloads with 4xx.
- Existing orders/subscriptions remain queryable.
- Renewal/lifecycle commands still execute:
  - `php artisan wncms-ecommerce:renew-subscriptions`
  - `php artisan wncms-ecommerce:advance-subscriptions`
- Gateway settings preserve existing secrets when partial updates are submitted.

## Rollback Strategy

- Restore DB backup and previous dependency lock/vendor state.
- Compatibility migration is forward-only; rollback should use backup restore, not destructive down migration.
