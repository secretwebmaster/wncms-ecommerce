# Production Readiness Design

This document defines missing production contracts that must be completed before tagging a stable composer release.

## Scope

- In scope:
  - Payment callback trust and idempotency contracts.
  - Checkout finalization contracts (especially PayPal return/capture flow).
  - Backward-compatible schema upgrade policy.
  - Gateway configuration/secret management policy.
  - Renewal lifecycle automation requirements.
  - Observability and release quality gates.
- Out of scope:
  - Host-project-specific provisioning/tenant business logic.
  - Non-core payment channels not in current release target.

## D1. Callback Trust Boundary

Every gateway callback path must verify authenticity before any order/subscription state transition.

Required rules:
- Verification first, mutation second.
- Verification failure returns 4xx and records a redacted audit log.
- No silent fallback to "success" for unverifiable payloads.
- Each callback event maps to a stable idempotency key (`external_id`), and repeated events must not duplicate side effects.

Processor contract requirement:
- Add a shared verification contract in gateway processors.
- Processor `notify()` should only call order state methods after verification passes and payload schema is valid.

## D2. PayPal Return/Capture Contract

Target flow:
1. `process()` creates remote PayPal order and stores local tracking metadata.
2. User returns to a dedicated package route for PayPal return.
3. Return handler resolves local order, validates token/order relation, and captures payment.
4. Capture result and webhook result converge on one idempotent finalization path.

Required safety checks:
- Returned token cannot finalize another order.
- Completed return flow and webhook replay must converge to one transaction result.
- Pending capture states must remain visible to user/operator.

## D3. Schema Compatibility Policy

Historical released migrations are immutable.

Rules:
- Never retrofit new columns into old `create_*` migrations for released versions.
- Add forward-only additive migrations for new columns/indexes/constraints.
- Every release must validate both:
  - Fresh install from zero.
  - Upgrade from previous stable tag.

Release note requirement:
- Include "upgrade required migrations" and "breaking schema changes" sections.

## D4. Gateway Configuration And Secret Handling

Gateway admin updates must be validated and safe by default.

Rules:
- Use strict request validation for create/update.
- Keep existing secret values when update payload omits secret fields.
- Redact secrets from logs, exceptions, and debug payloads.
- Validate endpoint and callback URL format.
- Keep driver/slug mapping deterministic and documented.

## D5. Renewal Lifecycle Contract

Recurring lifecycle must be explicit and automatable.

Required transition model:
- `active -> past_due -> (grace window) -> suspended`
- `suspended/past_due -> active` on successful recovery payment
- terminal transitions (cancelled/expired) are explicit and audited

Required behaviors:
- Time-based transitions are scheduler-driven and idempotent.
- Every transition stores reason and source (`scheduler`, `callback`, `admin`, `manual`).

## D6. Observability And Reconciliation Contract

Production operations require queryable financial traces.

Required logs/fields:
- `gateway`, `order_id`, `subscription_id`, `external_id`, `event_type`, `verification_result`, `transition_result`, `correlation_id`
- Always redact secrets/tokens

Required tooling:
- Reconciliation command/report by gateway + date range.
- Runbook for duplicate callbacks, pending-payment drift, failed verification spikes.

## D7. Release Quality Gates

Before tagging release:
- PHP lint and package test matrix must pass.
- Callback authenticity tests must pass for all supported gateways.
- Idempotency replay tests must pass.
- Fresh install + upgrade migration smoke tests must pass.
- Sandbox end-to-end checkout tests (success + fail paths) must pass.

Release candidate checklist output should be attached to changelog/release notes.
