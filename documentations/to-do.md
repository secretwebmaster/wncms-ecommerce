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
| EC8 | Composer release packaging + upgrade guide + RC checklist | P0 | yes | todo | - | - | - | EC1, EC2, EC3, EC4, EC5, EC6, EC7 |

## Execution Order

1. EC1
2. EC2
3. EC3
4. EC4
5. EC5
6. EC6
7. EC7
8. EC8

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
  - pending
- Blocker:
  - none
