# Changelog

All notable changes to `secretwebmaster/wncms-ecommerce` are documented in this file.

## [Unreleased]

### Added

- Pending

## [1.1.0-rc.1] - 2026-03-06

### Added

- Callback verification contract across all gateway processors (`verifyCallback`).
- PayPal return/cancel route integration and token/order binding checks.
- Backward-compatibility migration for billing schema expansion.
- Gateway form request validation and secret-preserving update behavior.
- Subscription lifecycle advancement (`grace`, `suspended`) and command automation.
- Reconciliation command for order/transaction drift.
- Package CI workflow, phpunit scaffold, and test matrix documentation.
- Operations runbook and production readiness design docs.

### Changed

- Failed transaction persistence is now idempotent via `updateOrCreate`.
- README expanded with installation, activation, testing, and operations references.
- Upgrade guide now includes versioned `1.0.x -> 1.1.x` rollout steps.

## [1.0.0] - 2026-02-18

### Added

- Initial release with products, plans, subscriptions, orders, transactions, and gateway integration.
