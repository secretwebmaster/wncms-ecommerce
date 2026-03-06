# Release Candidate Checklist

Use this checklist before tagging a stable composer release.

Release target: `v____________`
Executed by: `____________`
Executed at (UTC): `____________`

## 1. Package Metadata

- [ ] `composer.json` dependencies are final and valid.
- [ ] Package version constants/metadata are updated for release target.
- [ ] `README.md` installation and command docs are up to date.
- [ ] `CHANGELOG.md` has release notes for target version.

## 2. Fresh Install Smoke

- [ ] `composer require secretwebmaster/wncms-ecommerce`
- [ ] `php artisan migrate`
- [ ] `php artisan db:seed --class="Secretwebmaster\\WncmsEcommerce\\Database\\Seeders\\PaymentGatewaySeeder"`
- [ ] Backend payment gateway pages load without errors.
- [ ] `php artisan wncms-ecommerce:pay-order` command resolves and runs help output.

## 3. Upgrade Smoke

- [ ] Upgrade from previous stable tag in a non-empty database.
- [ ] Run `php artisan migrate --force`.
- [ ] Verify compatibility migration did not fail.
- [ ] Existing orders/subscriptions remain queryable.

## 4. Gateway Sandbox E2E

- [ ] Stripe callback verification pass/fail paths validated.
- [ ] PayPal return capture path validated.
- [ ] PayPal webhook verification pass/fail paths validated.
- [ ] EPUSDT callback signature verification validated.
- [ ] ECPay callback `CheckMacValue` verification pass/fail paths validated.

## 5. Subscription Lifecycle

- [ ] `php artisan wncms-ecommerce:renew-subscriptions`
- [ ] `php artisan wncms-ecommerce:advance-subscriptions`
- [ ] unpaid renewal transitions through `past_due -> grace -> suspended`.
- [ ] successful renewal reactivates to `active`.

## 6. Reconciliation and Ops

- [ ] `php artisan wncms-ecommerce:reconcile-transactions --json` executes successfully.
- [ ] Runbook reviewed by operations owner.
- [ ] Alerting/logging pipeline receives callback verification failures.

## 7. Tests and CI

- [ ] `composer test` passes.
- [ ] CI workflow `.github/workflows/package-ci.yml` is green.
- [ ] No untracked local debug changes remain.

## 8. Tagging

- [ ] Create release commit if needed.
- [ ] Tag semantic version.
- [ ] Publish release notes from `CHANGELOG.md`.

## 9. Evidence Capture

- [ ] Attach fresh install command log.
- [ ] Attach upgrade command log.
- [ ] Attach gateway sandbox evidence (request/response IDs).
- [ ] Attach CI workflow URL/hash for release commit.
