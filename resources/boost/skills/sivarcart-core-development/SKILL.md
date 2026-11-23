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

Use this skill when a Laravel application needs the SivarCart Core package, the data
model and the domain of an online store for El Salvador.

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
- an order carries **two axes**: `status` says where the order is
  (`SivarcartCore\SivarcartCore\Enums\OrderStatus`) and `payment_status` says where
  the money is (`SivarcartCore\SivarcartCore\Enums\PaymentStatus`). They move apart:
  an order paid on delivery is `delivered` and `unpaid` at the same time
- move the order with `$order->transitionTo(OrderStatus::SHIPPED)` and the payment
  with `$order->transitionPaymentTo(PaymentStatus::PAID)`; never write either field
  by hand
- the history of the order keeps one row per axis: the `axis` column tells them
  apart, and `OrderStatusHistory::scopeForAxis()` filters them
- the staff of the store are the users of the application: `sivarcart_store_role_user`
  points at the table `users` of the application, and the type of that column is derived
  from the user model

### 3. Authenticate with the application

- the package does not authenticate anybody: the credentials, the sessions and the
  password resets belong to the application that installs it
- the user model comes from the `users` provider of `config/auth.php` and the guard from
  its default guard; both can be overridden in the `auth` section of
  `config/sivarcart-core.php`
- add the trait of the roles to the user model of the application:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use SivarcartCore\SivarcartCore\Models\Concerns\HasStoreRoles;

class User extends Authenticatable
{
    use HasStoreRoles;
}
```

- with the trait the user knows the roles of the store it holds and the permissions those
  roles grant, and the package reads the authenticated user with
  `SivarcartCore\SivarcartCore\Support\Auth::user()`
- the application needs its own table of users before the migrations of the package run

### 4. Sell

- the services in `SivarcartCore\SivarcartCore\Services` are the spine of the domain:
  `CartService`, `OrderService`, `PaymentService` and `InventoryService` are the ones a
  storefront needs
- the totals of a cart are a cache of what its items are worth: they are recalculated
  from the items on every change and are never trusted on their own when the order is
  placed
- place the order with the `PlaceOrder` action and a `CheckoutData`; it reserves the
  stock, freezes what was bought and closes the cart in a single transaction
- the coupon of a cart is applied with `CartService::applyCoupon()`; the value of a
  percentage coupon **is** the percentage, so 15 means 15 %

```php
use SivarcartCore\SivarcartCore\Actions\PlaceOrder;
use SivarcartCore\SivarcartCore\Data\CheckoutData;

$order = app(PlaceOrder::class)->execute($cart, new CheckoutData(
    customerName: 'Ana Guzmán',
    customerEmail: 'ana@example.com',
    shippingAddress: ['municipality' => 'San Salvador', 'department' => 'San Salvador'],
    shippingMethodCode: 'estandar',
    paymentMethodCode: 'wompi',
));
```

### 5. Collect the money with Wompi

- the credentials of the gateway live in `sivarcart_payment_methods` and are stored
  encrypted; the package `rmirandasv/laravel-wompi` handles the OAuth2, the HMAC
  signature and the payload normalization
- ask for the payment link with the `StartPayment` action; the merchant reference of the
  link is the reference of the payment, so the webhook finds the order without guessing
- the package registers the route of the webhook: `POST webhooks/wompi`, named
  `sivarcart-core.wompi.webhook`. The application must **not** publish the route of the
  `laravel-wompi` package, because both would answer the same path
- the controller always answers 200: a webhook that cannot be applied is logged, and
  answering with an error would make Wompi deliver it over and over
- mark an order as paid only when the transaction was approved **and** was a real
  charge: a test transaction (`EsProductiva` false) was never billed to the customer
- the webhook is idempotent: `payment_transactions.idempotency_key` has a unique index,
  so a repeated delivery never charges or moves the stock twice

### 6. Move the order

- `MarkOrderAsPaid` confirms a payment the store collected on its own, like the cash of
  a delivery order. It only moves the payment axis: the order stays `pending` until the
  staff prepare and ship it
- `PrepareOrder`, `ShipOrder`, `DeliverOrder` and `ReturnOrder` move the order through
  its physical half: pending → processing → shipped → delivered, and back to returned.
  An order that is not paid yet can be shipped, because an order paid on delivery
  leaves the store before the money arrives
- dispatching **does not move the stock**: the units were reserved when the order was
  placed and they become a sale when the order is paid
- `CancelOrder` gives the reserved units back to the stock and cancels the payment that
  was never settled
- `RefundOrder` registers a refund: the Wompi API does not offer a way to refund a
  payment, so the core records the refund, gives the units back and closes the order,
  and the merchant applies the money back on the Wompi dashboard
- the actions emit `OrderPlaced`, `OrderPaid`, `OrderPrepared`, `OrderShipped`,
  `OrderDelivered`, `OrderReturned`, `OrderCancelled`, `OrderRefunded` and
  `PaymentFailed` after the commit, so a listener never sees a state that can still be
  reverted

### 7. Extend the store

- store module settings in `sivarcart_settings` instead of changing the schema
- the shipping cost comes from the `ShippingRateCalculator` contract and the payment
  from the `PaymentGateway` contract; both can be replaced without touching the package
- another gateway is registered as a driver of the payment gateway manager:

```php
app(PaymentGatewayManager::class)->extend('mi-pasarela', fn () => new MiPasarela());
```

- the publish tags are `sivarcart-core`, `sivarcart-core-config`,
  `sivarcart-core-migrations`, `sivarcart-core-views`, `sivarcart-core-lang` and
  `sivarcart-core-assets`

## Rules, References, and Templates

Read before executing:

- `README.md` — the table by table data model, the install flow and the domain layer
- `config/sivarcart-core.php` — store defaults, numbering prefixes, the Wompi settings and
  the model and guard of the users

## Examples

- publish the migrations and seed the base data, then read the store settings:

```php
use SivarcartCore\SivarcartCore\Models\Store;

$store = Store::query()->firstOrFail();

$store->currency;             // "USD"
$store->prices_include_tax;   // true
```

- build a cart, apply a coupon and place the order:

```php
use SivarcartCore\SivarcartCore\Services\CartService;

$carts = app(CartService::class);

$cart = $carts->findOrCreate($customer?->getKey(), $sessionId);
$cart = $carts->addItem($cart, $variant, 2);
$cart = $carts->applyCoupon($cart, 'BIENVENIDA');
```

- ask for the payment link of an order and read the URL to send the shopper:

```php
use SivarcartCore\SivarcartCore\Actions\StartPayment;

$link = app(StartPayment::class)->execute($order);

$link->url;        // https://checkout.wompi.sv/l/...
$link->reference;  // the identifier of the link on Wompi
```

- listen to the events of the domain:

```php
use SivarcartCore\SivarcartCore\Events\OrderPaid;

Event::listen(function (OrderPaid $event): void {
    $event->order;
    $event->payment;
});
```

- move an order through its dispatch and read both axes:

```php
use SivarcartCore\SivarcartCore\Actions\ShipOrder;

app(ShipOrder::class)->execute($order);

$order->fresh()->status;         // OrderStatus::SHIPPED
$order->fresh()->payment_status; // PaymentStatus::UNPAID
$order->fresh()->fulfilled_at;   // the moment it left the store
```

- give a member of the staff a role of the store and check what it may do:

```php
$user->assignStoreRole('manager');

$user->hasStoreRole('manager');           // true
$user->hasStorePermission('orders.view'); // true
$user->hasStorePermission('settings.edit'); // false
```

## Anti-patterns

- do not put a price or a stock in `sivarcart_products`; it belongs to the variant
- do not create a table of staff: the staff of the store are the users of the application,
  and the package relates them to the `users` provider of `config/auth.php`
- do not mark an order as paid without checking `is_production`, a test transaction was
  never billed
- do not publish the route of the `laravel-wompi` package; the package already registers
  the webhook and both would answer the same path
- do not call the payment gateway inside a database transaction; the order and its
  reservation have to survive a failure of the gateway
- do not trust the totals of a cart; they are recalculated from the items
- do not load the migrations of the package with `loadMigrationsFrom`; they are
  published, and loading both copies makes `migrate` fail with a duplicate class
- do not write the status of an order directly; use `transitionTo` so the history stays
  consistent
- do not write the payment status of an order directly; use `transitionPaymentTo` so the
  history stays consistent and the change is idempotent
- do not move the stock when an order is dispatched; the reservation becomes a sale when
  the order is paid
- do not document the columns of a table as public API; the models and the enums are
