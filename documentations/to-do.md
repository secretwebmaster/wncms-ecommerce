# WNCMS Ecommerce Production To-Do

Purpose: this is the shared execution board for production hardening of `secretwebmaster/wncms-ecommerce`.

## Status Rules (For All Agents)

- Status values:
  - `todo`: not started
  - `in_progress`: claimed by one agent (locked)
  - `blocked`: cannot proceed, waiting for dependency/decision
  - `completed`: implemented and verified
- Before starting a task:
  - Change task status to `in_progress`
  - Fill `assignee` and `started_at_utc`
- While working:
  - Keep only one `in_progress` owner per task
  - If blocked, set status to `blocked` and write blocker in task detail
- After finishing:
  - Change status to `completed`
  - Fill `completed_at_utc`
  - Add verification notes under the task section

## Agent Work Board

| task_id | assignee | started_at_utc | notes |
| --- | --- | --- | --- |
| - | - | - | - |

## Task Registry

| id | title | priority | release_blocker | status | assignee | started_at_utc | completed_at_utc | dependencies |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| EC1 | Callback trust model + webhook verification hardening | P0 | yes | completed | codex | 2026-03-06T11:51:38Z | 2026-03-06T11:57:12Z | - |
| EC2 | PayPal return/capture finalization contract | P0 | yes | completed | codex | 2026-03-06T12:15:49Z | 2026-03-06T12:17:17Z | EC1 |
| EC3 | Additive migration strategy for backward-compatible upgrades | P0 | yes | completed | codex | 2026-03-06T12:17:47Z | 2026-03-06T12:19:49Z | EC1 |
| EC4 | Gateway config validation + secret handling hardening | P1 | yes | completed | codex | 2026-03-06T12:20:10Z | 2026-03-06T12:22:47Z | EC1 |
| EC5 | Renewal grace/suspension/reactivation state machine | P1 | yes | completed | codex | 2026-03-06T12:23:10Z | 2026-03-06T12:25:13Z | EC2, EC3 |
| EC6 | Automated test matrix + CI release gate | P0 | yes | completed | codex | 2026-03-06T12:25:34Z | 2026-03-06T12:28:38Z | EC1, EC2, EC3, EC4 |
| EC7 | Observability, reconciliation, and failure runbook | P1 | yes | completed | codex | 2026-03-06T12:29:09Z | 2026-03-06T12:30:32Z | EC6 |
| EC8 | Composer release packaging + upgrade guide + RC checklist | P0 | yes | completed | codex | 2026-03-06T12:30:56Z | 2026-03-06T12:34:15Z | EC1, EC2, EC3, EC4, EC5, EC6, EC7 |
| EC9 | Add ECPay (綠界) gateway integration | P1 | no | completed | codex | 2026-03-06T12:42:24Z | 2026-03-06T12:49:19Z | EC1 |
| EC10 | Fix backend payment gateway controller method signature compatibility | P0 | yes | completed | codex | 2026-03-06T12:55:48Z | 2026-03-06T12:55:48Z | EC4 |
| EC11 | Normalize backend sorting naming to `sort` and fix OrderController constant usage | P0 | yes | completed | codex | 2026-03-06T14:17:24Z | 2026-03-06T14:17:24Z | EC10 |
| EC12 | Fix transaction status translation and status-set consistency | P0 | yes | completed | codex | 2026-03-06T15:32:28Z | 2026-03-06T15:32:28Z | EC6 |

## Execution Order

1. EC1
2. EC2
3. EC3
4. EC4
5. EC5
6. EC6
7. EC7
8. EC8
9. EC9
10. EC10
11. EC11
12. EC12

## Task Details

### EC1. Callback trust model + webhook verification hardening

- Scope:
  - Add unified callback verification contract for processors.
  - Enforce verification-before-mutation for PayPal, Stripe, EPUSDT.
  - Reject unverifiable callbacks with 4xx.
  - Keep idempotency keyed by stable external event id.
- Acceptance:
  - Unverified callback never changes `orders`/`transactions`.
  - Replayed verified callback does not duplicate side effects.
- Verification notes:
  - Added unified callback verification contract in `PaymentGatewayInterface::verifyCallback(...)` and implemented it in `Epusdt`, `Paypal`, and `Stripe`.
  - Enforced verify-before-mutation flow in all gateway `notify()` handlers with explicit failed-verification 4xx responses.
  - Added callback correlation context logging (`gateway`, `correlation_id`, `event_id`, `order_id`, `verification_result`) via `BasePaymentGateway`.
  - Added processor contract guard in API callback controller to require `PaymentGatewayInterface`.
  - Hardened idempotency for failed callbacks by switching `OrderManager::markFailed()` to `updateOrCreate` keyed by `order_id + external_id`.
  - Verification commands: `php -l` passed for all changed files in `src/Interfaces`, `src/PaymentGateways`, `src/Http/Controllers/Api/V1/PaymentGatewayController.php`, and `src/Services/Managers/OrderManager.php`.
- Blocker:
  - none

### EC2. PayPal return/capture finalization contract

- Scope:
  - Add dedicated PayPal return/cancel routes + handlers.
  - Validate token-to-order binding.
  - Route capture + webhook to one idempotent finalization path.
- Acceptance:
  - `process -> return -> capture` completes deterministically.
  - Wrong token cannot finalize another order.
- Verification notes:
  - Added authenticated frontend routes `frontend.orders.paypal.return` and `frontend.orders.paypal.cancel`.
  - Added `OrderController::paypalReturn()` and `paypalCancel()` to finalize/handle PayPal redirect explicitly.
  - Hardened `Paypal::capture()` with gateway-id check and strict returned-token vs `order.tracking_code` binding.
  - Updated PayPal cancel URL resolver to use new package cancel route.
  - Updated lifecycle doc to reflect return/capture + verify-before-mutation flow.
  - Verification commands: `php -l` passed for `routes/frontend.php`, `src/Http/Controllers/Frontend/OrderController.php`, and `src/PaymentGateways/Paypal.php`.
- Blocker:
  - none

### EC3. Additive migration strategy for backward-compatible upgrades

- Scope:
  - Add new forward migrations for required columns/indexes.
  - Keep released historical `create_*` migrations immutable.
  - Document upgrade path.
- Acceptance:
  - Fresh install passes.
  - Upgrade from previous tag passes without missing-column runtime errors.
- Verification notes:
  - Added forward-only compatibility migration: `2026_03_06_121800_add_backward_compatibility_columns_for_billing_refactor.php`.
  - Migration adds missing billing/gateway/subscription columns and defensive indexes only when absent.
  - Added package upgrade instructions in `documentations/upgrade-guide.md`.
  - Updated README documentation index with upgrade guide entry.
  - Verification command: `php -l database/migrations/2026_03_06_121800_add_backward_compatibility_columns_for_billing_refactor.php`.
- Blocker:
  - none

### EC4. Gateway config validation + secret handling hardening

- Scope:
  - Add request validation for gateway create/update.
  - Preserve stored secrets when omitted in update payload.
  - Redact sensitive values in logs/errors.
- Acceptance:
  - Invalid config rejected with actionable messages.
  - Partial update never wipes existing secret fields.
- Verification notes:
  - Added `PaymentGatewayFormRequest` with slug uniqueness, URL validation, driver/currency constraints, and attributes validation.
  - Wired backend payment-gateway `store`/`update` actions to use typed form request validation.
  - Implemented secret-preserving update behavior for `client_secret` and `webhook_secret` when omitted.
  - Added backend form input for `webhook_secret` (used as webhook secret/id depending on gateway).
  - Added localization keys for validation/form labels (`webhook_secret_or_id`, `invalid_return_url`) in all package locales.
  - Verification commands: `php -l` passed for updated request/controller/view/lang files.
- Blocker:
  - none

### EC5. Renewal grace/suspension/reactivation state machine

- Scope:
  - Define lifecycle transitions for overdue renewal and recovery.
  - Implement scheduler/command transitions with reason/source metadata.
  - Ensure order/subscription status consistency.
- Acceptance:
  - Overdue subscriptions move through policy states automatically.
  - Successful repayment reactivates and advances billing window.
- Verification notes:
  - Added lifecycle state progression in `PlanManager::advanceLifecycleStates()` for `past_due -> grace -> suspended`.
  - Added lifecycle audit trail (`attributes.lifecycle_events`) with `event/source/reason/at`.
  - Added automatic pending-renewal order failure marking on suspension (idempotent via deterministic `external_id`).
  - Added command `wncms-ecommerce:advance-subscriptions` and registered it in service provider.
  - Updated subscription status model constants to include `grace` and `suspended`; renewal scope includes `grace`.
  - Updated lifecycle docs and README command list.
  - Verification commands: `php -l` passed for updated manager/command/model/provider files.
- Blocker:
  - none

### EC6. Automated test matrix + CI release gate

- Scope:
  - Add tests for callback verification, idempotency, PayPal capture, migration compatibility, renewal automation.
  - Add CI workflow for lint + tests + migration smoke.
- Acceptance:
  - Required suite passes in CI and blocks release on failure.
- Verification notes:
  - Added package test scaffold: `phpunit.xml.dist`, `tests/TestCase.php`, `tests/Feature/GatewayVerificationTest.php`.
  - Added CI workflow gate: `.github/workflows/package-ci.yml` (install, lint, run tests).
  - Added composer test script + dev dependencies (`phpunit`, `orchestra/testbench`) and autoload-dev mapping.
  - Added explicit test matrix doc: `documentations/test-matrix.md`.
  - Updated README docs index + testing command section.
  - Local verification:
    - `php -l` passed on test files.
    - `COMPOSER_ALLOW_SUPERUSER=1 composer test` failed in current workspace because package-level `vendor/bin/phpunit` is not installed yet.
- Blocker:
  - none

### EC7. Observability, reconciliation, and failure runbook

- Scope:
  - Define structured redacted log fields.
  - Add reconciliation command/report by gateway/date range.
  - Add operator runbook for failed callbacks, duplicates, stuck pending orders.
- Acceptance:
  - Operators can diagnose and reconcile from logs/reports.
- Verification notes:
  - Added command `wncms-ecommerce:reconcile-transactions` with gateway/date filters and JSON output.
  - Reconciliation detects key drift patterns between `orders` and `transactions`.
  - Registered reconciliation command in package service provider command list.
  - Added operator runbook `documentations/operations-runbook.md` for verification failures, duplicates, drift handling, and lifecycle operations.
  - Updated README documentation/command index to include reconciliation and runbook.
  - Verification commands: `php -l` passed for new command + updated provider.
- Blocker:
  - none

### EC8. Composer release packaging + upgrade guide + RC checklist

- Scope:
  - Finalize README install/activation docs.
  - Add versioned upgrade guide and changelog notes.
  - Add RC smoke checklist for fresh install + upgrade + sandbox gateway flows.
- Acceptance:
  - Package is installable/upgradable on live projects using published docs.
- Verification notes:
  - Added `CHANGELOG.md` with versioned release notes (`1.1.0-rc.1`) and upgrade-related change summary.
  - Added `documentations/release-checklist.md` with operator-facing RC validation steps for metadata, install, upgrade, sandbox gateways, lifecycle, reconciliation, CI, and evidence capture.
  - Expanded `README.md` with production install flow, upgrade flow, publish/migrate/seed commands, and release documentation index.
  - Upgraded `documentations/upgrade-guide.md` to a versioned rollout playbook (`1.0.x -> 1.1.x`) with rollback guidance and post-upgrade verification checks.
  - Verification commands:
    - `php -l src/Providers/EcommerceServiceProvider.php`
    - `php -l database/migrations/2026_03_06_121800_add_backward_compatibility_columns_for_billing_refactor.php`
- Blocker:
  - none

### EC9. Add ECPay (綠界) gateway integration

- Scope:
  - Add `Ecpay` processor implementation for hosted redirect flow.
  - Add callback verification and idempotent notify handling.
  - Add default ECPay gateway seed records and documentation updates.
  - Add automated verification tests for ECPay signature handling.
- Acceptance:
  - User can initiate checkout via ECPay redirect flow.
  - Callback verification blocks invalid signature payloads.
  - Valid callback marks orders paid/failed idempotently.
- Verification notes:
  - Added `src/PaymentGateways/Ecpay.php` implementing hosted checkout redirect (`process()`), callback verification (`verifyCallback()`), and idempotent callback mutation (`notify()`).
  - Added frontend auto-submit view `resources/views/frontend/payment_gateways/ecpay-redirect.blade.php` for ECPay form-post checkout initiation.
  - Added default gateway records in `database/seeders/PaymentGatewaySeeder.php`: `ecpay` (live) and `ecpay_stage` (sandbox), both using driver `ecpay`.
  - Added ECPay credential mapping hint to backend gateway form and package locales (`client_id=MerchantID`, `client_secret=HashKey`, `webhook_secret=HashIV`).
  - Extended `tests/Feature/GatewayVerificationTest.php` with valid/invalid ECPay `CheckMacValue` verification tests.
  - Updated operational docs (`README`, `payment-lifecycle`, `operations-runbook`, `test-matrix`, `release-checklist`, `CHANGELOG`) to include ECPay flow and verification requirements.
  - Verification commands:
    - `php -l src/PaymentGateways/Ecpay.php`
    - `php -l database/seeders/PaymentGatewaySeeder.php`
    - `php -l tests/Feature/GatewayVerificationTest.php`
    - `php -l lang/en/word.php lang/zh_TW/word.php lang/zh_CN/word.php lang/ja/word.php`
- Blocker:
  - none

### EC10. Fix backend payment gateway controller method signature compatibility

- Scope:
  - Resolve fatal error caused by child controller method signatures that were narrower than parent `BackendController` signatures.
  - Keep gateway validation behavior unchanged after signature fix.
- Acceptance:
  - Payment gateway backend controller class loads without fatal signature mismatch.
  - `store`/`update` still validate requests with `PaymentGatewayFormRequest` rules.
- Verification notes:
  - Updated `PaymentGatewayController::store()` and `update()` signatures to use `Illuminate\Http\Request` so they are compatible with `BackendController`.
  - Added `PaymentGatewayFormRequest::rulesFor($id = null)` and reused it from both `rules()` and controller runtime validation calls.
  - Verification commands:
    - `php -l src/Http/Controllers/Backend/PaymentGatewayController.php`
    - `php -l src/Http/Requests/PaymentGatewayFormRequest.php`
- Blocker:
  - none

### EC11. Normalize backend sorting naming to `sort` and fix OrderController constant usage

- Scope:
  - Remove incorrect `Order::ORDERS` usage that caused fatal class constant error.
  - Ensure backend sorting naming is `sort`/`direction` with `sorts` whitelist.
  - Align order status filter source with model constants.
- Acceptance:
  - `/panel/orders` loads without fatal error.
  - Sorting query params use `sort` + `direction` and only allow whitelisted fields.
- Verification notes:
  - Replaced invalid `orders => $this->modelClass::ORDERS` view data with `sorts` + `statuses`.
  - Added sort whitelist in `OrderController@index` and applied `orderBy($sort, $direction)` using validated inputs.
  - Updated backend orders status filter view to use `$statuses` instead of hardcoded legacy values.
  - Updated `store`/`update` status validation to use `Order::STATUSES`.
  - Verification command:
    - `php -l src/Http/Controllers/Backend/OrderController.php`
- Blocker:
  - none

### EC12. Fix transaction status translation and status-set consistency

- Scope:
  - Fix untranslated transaction statuses in backend index.
  - Align transaction status options/validation with `Transaction::STATUSES`.
- Acceptance:
  - Transactions index shows translated status labels (including `succeeded`).
  - Create/edit/index status sets are consistent across controller and views.
- Verification notes:
  - Updated transactions index to use package translation prefix (`wncms-ecommerce::word.`) for status badge rendering.
  - Added package locale keys for transaction statuses (`pending`, `succeeded`, `completed`, `failed`, `refunded`, `cancelled`) in `en`, `zh_TW`, `zh_CN`, and `ja`.
  - Updated transaction index filter + transaction form status options to use model-driven statuses.
  - Updated `TransactionController` index/store/update to use `Transaction::STATUSES` for filter exposure and validation constraints.
  - Verification commands:
    - `php -l src/Http/Controllers/Backend/TransactionController.php`
    - `php -l lang/en/word.php lang/zh_TW/word.php lang/zh_CN/word.php lang/ja/word.php`
- Blocker:
  - none
