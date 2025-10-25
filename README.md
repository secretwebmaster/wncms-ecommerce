# WNCMS E-commerce

`secretwebmaster/wncms-ecommerce` is the official E-commerce module for **WNCMS**, providing full management of products, orders, transactions, plans, subscriptions, and payment gateways.

---

## Features

- Product management (CRUD)
- Order and OrderItem management
- Subscription and Plan management
- Payment Gateway integration
- Credit and Transaction tracking
- Auto-generated permissions via `wncms:create-model-permission`
- Backend and frontend views
- Multi-language support (EN, zh_TW, zh_CN, JA)
- Auto-activation support in WNCMS backend

---

## Installation

This package is designed for seamless integration with WNCMS.  
Simply run:

```bash
composer require secretwebmaster/wncms-ecommerce
````

No manual migration or seeding is required — everything is automatically handled when activating the package in the WNCMS backend.

---

## Activation

Once installed, open **WNCMS backend → Packages → E-commerce**, then click **Activate**.
This will automatically:

* Run the migrations
* Register all models, managers, and controllers
* Generate permissions for `admin` and `superadmin`
* Display new backend menu items

---

## Changelog

### [v1.0.0] – 2025-10-23

#### Added

* Initial release of `wncms-ecommerce`
* Includes products, orders, transactions, plans, subscriptions, and payment gateways
* Full backend and frontend view integration
* Permission auto-registration via `wncms:create-model-permission`

```
