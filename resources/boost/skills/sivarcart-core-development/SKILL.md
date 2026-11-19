---
name: sivarcart-core-development
description: >
  Install and adopt the SivarCart Core package in Laravel applications: a
  self-hosted online store for El Salvador with Wompi as the payment gateway.
license: MIT
metadata:
  author: Ronald Miranda
---

# SivarCart Core

Use this skill when a Laravel application needs the SivarCart Core package, the
data model of an online store for El Salvador.

## Primary Goal

- apply the `tarkontech/sivarcart-core` package's public API in the smallest correct way

## Workflow

### 1. Install the store

- require the package and run the install command, which publishes the migrations,
  runs them and seeds the base data of El Salvador:

```bash
composer require tarkontech/sivarcart-core
php artisan sivarcart-core:install
```

- the command accepts `--no-seed` to skip the base data and `--force` to overwrite
  the migrations that were already published

### 2. Read the model

- the models live in `SivarcartCore\SivarcartCore\Models` and every table is prefixed
  with `sivarcart_`
- a product never holds a price or a stock: those live in its variants, so a simple
  product owns exactly one variant and a variable product owns one per combination
- an order freezes what was bought in `sivarcart_order_items`, so editing the catalog
  never rewrites an order that was already placed
- the order lifecycle lives in `SivarcartCore\SivarcartCore\Enums\OrderStatus`; move an
  order with `$order->transitionTo(OrderStatus::PAID)` and never write the status by hand

### 3. Collect the money with Wompi

- the credentials of the gateway live in `sivarcart_payment_methods` and are stored
  encrypted; the package `rmirandasv/laravel-wompi` handles the OAuth2, the HMAC
  signature and the payload normalization
- keep the merchant reference of a payment in `sivarcart_orders.reference` and send it
  as the `identificadorEnlaceComercio` of the payment link: that is how the webhook is
  matched with the order
- record every delivery of the webhook in `sivarcart_payment_transactions` with its
  `idempotency_key`; the column has a unique index because Wompi retries deliveries
- mark an order as paid only when the transaction was approved **and** was a real
  charge: a test transaction (`EsProductiva` false) was never billed to the customer

### 4. Extend the store

- store module settings in `sivarcart_settings` instead of changing the schema
- the publish tags are `sivarcart-core`, `sivarcart-core-config`,
  `sivarcart-core-migrations`, `sivarcart-core-views`, `sivarcart-core-lang` and
  `sivarcart-core-assets`

## Rules, References, and Templates

Read before executing:

- `README.md` — the table by table data model and the install flow
- `config/sivarcart-core.php` — store defaults, numbering prefixes and the Wompi settings

## Examples

- publish the migrations and seed the base data, then read the store settings:

```php
use SivarcartCore\SivarcartCore\Models\Store;

$store = Store::query()->firstOrFail();

$store->currency;             // "USD"
$store->prices_include_tax;   // true
```

- build a product with its variants and read the price range for the listing:

```php
use SivarcartCore\SivarcartCore\Models\Product;

$product = Product::factory()->variable()->create();

$product->variants()->create(['sku' => 'CAM-AZUL-M', 'price' => 25.50]);

$product->fresh()->priceRange();   // ['min' => 25.5, 'max' => 25.5]
```

- record a webhook of Wompi and mark the order as paid only for a real charge:

```php
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Models\PaymentTransaction;

$transaction = PaymentTransaction::factory()->create([
    'payment_id' => $payment->getKey(),
    'idempotency_key' => $webhook->idempotencyKey(),
    'is_approved' => $webhook->isApproved(),
    'is_production' => $webhook->isProduction(),
]);

if ($transaction->isSuccessful()) {
    $order->transitionTo(OrderStatus::PAID);
}
```

## Anti-patterns

- do not put a price or a stock in `sivarcart_products`; it belongs to the variant
- do not mark an order as paid without checking `is_production`, a test transaction was
  never billed
- do not load the migrations of the package with `loadMigrationsFrom`; they are
  published, and loading both copies makes `migrate` fail with a duplicate class
- do not write the status of an order directly; use `transitionTo` so the history stays
  consistent
- do not document the columns of a table as public API; the models and the enums are
