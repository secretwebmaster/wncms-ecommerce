# Test Matrix

## Required Automated Coverage

- Callback verification:
  - Stripe: missing/invalid signature rejected
  - EPUSDT: invalid signature rejected, valid signature accepted
  - PayPal: webhook verification failure rejected
- Checkout finalization:
  - PayPal return token mismatch rejected
- Idempotency:
  - Duplicate callback event does not duplicate side effects
- Migration compatibility:
  - Fresh install migration run
  - Upgrade migration run with missing columns

## CI Release Gate

The package CI workflow must pass before release tagging:

1. `composer install`
2. PHP lint for `src`, `database`, `tests`
3. `composer test` (`phpunit`)

Workflow file:
- `.github/workflows/package-ci.yml`
