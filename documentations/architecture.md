# Architecture

## Core Domain

- `Product`: billable item for one-time or recurring sale types.
- `Plan`: recurring subscription plan definition.
- `Order`: payment intent + checkout record.
- `OrderItem`: normalized line item snapshot (name, unit_amount, interval metadata).
- `Transaction`: immutable payment event record (charge/renewal/refund).
- `Subscription`: recurring lifecycle state and current billing window.
- `PaymentGateway`: gateway configuration and processor resolver.

## Service Layer

- `OrderManager`
- Responsibilities:
  - Create one-time orders (`createOneTimeOrder`)
  - Create subscription orders (`createSubscriptionOrder`)
  - Normalize items and totals
  - Mark paid/failed with idempotent transaction recording
- `PlanManager`
- Responsibilities:
  - Activate/renew subscription from paid orders
  - Compute period boundaries
  - Create renewal orders for due subscriptions

## Processor Layer

Gateway processors are resolved by `PaymentGateway::processor()` in this priority:

1. `Secretwebmaster\\WncmsEcommerce\\PaymentGateways\\{Driver}`
2. `App\\PaymentGateways\\{Driver}`

Driver defaults to `payment_gateways.driver`, then falls back to `slug`/`type`.

## Extension Boundary

Project-specific behavior should be implemented through:

- App-side gateway processors (`App\\PaymentGateways\\*`)
- App-side listeners/jobs for provisioning/tenant operations
- Host project routes/controllers that orchestrate package services

This package should stay focused on reusable billing + subscription mechanics.
