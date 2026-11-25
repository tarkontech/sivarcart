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
- the server needs the **`exif` extension of PHP** and, for the thumbnails, the `gd` one:
  the package stores the images with Spatie Media Library and that library requires `exif`,
  so without it Composer does not even install the package
- do not publish the migration of the media library (`media-library-migrations`): the
  package keeps the files in its own table of images, so the `media` table is never used

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

### 3. Authenticate the staff with the application

- the package does not authenticate the **staff of the store**: their credentials, their
  sessions and their password resets belong to the application that installs the package
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
- protect the routes of the application with the permissions of the staff: the package
  registers the middleware with the alias `store.permission` and the ability
  `store-permission` of the Gate, so the application does not have to register anything

```php
Route::middleware(['auth', 'store.permission:orders.view'])->group(function (): void {
    Route::get('/orders', OrderController::class)->name('orders.index');
});
```

- a request without the permission, without a user, or with a user whose model does not
  carry the trait of the roles is refused with a 403; the middleware does not authenticate
  anybody, the application puts its own `auth` middleware in front of it
- the application needs its own table of users before the migrations of the package run

### 4. Sell to buyers with an account

- the **buyer** signs in with the guard of the package, not with the guard of the
  application: the package registers its own guard, its own provider and its own password
  broker when it boots, so the application does not touch its `config/auth.php`
- the guard is `sivarcart` por defecto and the names come from the `buyers` section of
  `config/sivarcart-core.php`
- the buyer signs in and out with `CustomerService`:

```php
use SivarcartCore\SivarcartCore\Services\CustomerService;

$customers = app(CustomerService::class);

$customers->attempt('ana@example.com', 'su-contrasena');   // El comprador, o null.
$customers->user();                                          // El comprador que entró, o null.
$customers->logout();
```

- protect the routes of the buyer with the guard of the package:

```php
Route::middleware(['auth:sivarcart'])->group(function (): void {
    Route::get('/mi-cuenta', AccountController::class)->name('account');
});
```

- the package brings the whole account cycle: `register()`, `updateProfile()`,
  `changePassword()`, `verifyEmail()`, `sendPasswordResetLink()` and `resetPassword()`
- the emails of the account point at the routes the application declares:
  `buyers.password_reset_route` and `buyers.email_verification_route`. The package does
  not render those pages
- the document of identity is checked against its type with the check digit of each one;
  the rule `SivarcartCore\SivarcartCore\Rules\CustomerDocument` works in the forms and in
  the domain

### 5. Sell

- the services in `SivarcartCore\SivarcartCore\Services` are the spine of the domain:
  `CartService`, `CustomerService`, `OrderService`, `PaymentService` and
  `InventoryService` are the ones a storefront needs
- the totals of a cart are a cache of what its items are worth: they are recalculated
  from the items on every change and are never trusted on their own when the order is
  placed
- place the order with the `PlaceOrder` action and a `CheckoutData`; it reserves the
  stock, freezes what was bought and closes the cart in a single transaction
- **the cart belongs to a buyer**: `CartService::findOrCreate()` takes the id of the
  buyer, and an order without a buyer is refused with `CustomerRequired`
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

- the staff of the store restock a variant and correct a count with the
  `sivarcart:restock` command, which writes the movement in the stock ledger:

```bash
php artisan sivarcart:restock SKU-123 10 --reason="Llegó del proveedor"
php artisan sivarcart:restock SKU-123 --stock=25
```

### 6. Collect the money with Wompi

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

### 7. Move the order

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
  was never settled. The payment axis of the order becomes `cancelled` too, **unless the
  order was already paid**: that one keeps `paid`, because giving the money back is the
  job of the refund
- `RefundOrder` registers a refund: the Wompi API does not offer a way to refund a
  payment, so the core records the refund, gives the units back and closes the order,
  and the merchant applies the money back on the Wompi dashboard
- the actions emit `OrderPlaced`, `OrderPaid`, `OrderPrepared`, `OrderShipped`,
  `OrderDelivered`, `OrderReturned`, `OrderCancelled`, `OrderRefunded` and
  `PaymentFailed` after the commit, so a listener never sees a state that can still be
  reverted
- the payment axis of the order follows its own payment: `StartPayment` leaves it
  `pending` once the payment link exists, the webhook of Wompi leaves it `paid` or
  `failed`, and `CancelOrder` leaves it `cancelled`. A gateway that fails leaves the
  payment `unpaid`, so the buyer can ask for the link again

### 8. Write to the buyer

- the package writes to the buyer when the order moves: every notification of
  `SivarcartCore\SivarcartCore\Enums\OrderNotification` delivers a markdown mailable, and
  the notifications are queued, so a mail never holds the checkout
- **which emails leave the store is a decision of the merchant, and it lives in the
  database**: `sivarcart_notification_settings` holds one row per notification the
  administration panel turned on or off. A notification without a row follows
  `config('sivarcart-core.notifications.enabled')`, which comes enabled
- the notifications that exist are the cases of `OrderNotification`: `order_placed`,
  `order_paid`, `order_shipped`, `order_delivered`, `order_cancelled`, `order_refunded`
  and `payment_failed`
- to rewrite an email, publish the templates and edit them; the views of the
  `sivarcart-core` namespace are read from the application first, so the published copy
  wins without touching PHP:

```bash
php artisan sivarcart-core:publish-mailables
```

- the texts of the emails live in the language files (`lang/en/messages.php` and
  `lang/es/messages.php`), which are published with the tag `sivarcart-core-lang`
- the sender comes from `config('sivarcart-core.notifications.from')`, and when it is
  null the `mail.from` of the application is used

### 9. Keep the images of the store

- the images of the products, of the variants, of the brands and of the store are stored with
  Spatie Media Library, but in the table of the package (`sivarcart_product_images`), so every
  table keeps the `sivarcart_` prefix and the migration of the library is never published
- upload them with the `MediaService`: it validates the file, decides which product the image
  belongs to and which one is the main image, and it leaves nothing behind when it refuses a
  file (no row and no file on the disk)

```php
use SivarcartCore\SivarcartCore\Services\MediaService;

$media = app(MediaService::class);

$media->storeProductImage($product, $request->file('foto'), 'Una foto del producto');
$media->storeVariantImage($variant, $request->file('foto'));
$media->storeBrandLogo($brand, $request->file('logo'));
$media->storeStoreLogo($store, $request->file('logo'));
$media->storeStoreFavicon($store, $request->file('favicon'));

$media->makePrimary($image);
$media->delete($image);
```

- a file the store does not accept raises `InvalidMediaFile`, and nothing is written
- the image of a variant **belongs to the product too**: the gallery of a product holds them
  and numbers them in the same order
- the disk, the maximum size, the kinds of file and the map of thumbnails come from the
  `media` section of `config/sivarcart-core.php`
- the store API returns the images with their path, their disk and their text, and also
  with `url` and `thumbnail_url`

### 10. Extend the store

- a module keeps its own settings in its own table: the package does not have a table
  of free form settings
- the shipping cost comes from the `ShippingRateCalculator` contract and the payment
  from the `PaymentGateway` contract; both can be replaced without touching the package
- another gateway is registered as a driver of the payment gateway manager:

```php
app(PaymentGatewayManager::class)->extend('mi-pasarela', fn () => new MiPasarela());
```

- the publish tags are `sivarcart-core`, `sivarcart-core-config`,
  `sivarcart-core-migrations`, `sivarcart-core-views`, `sivarcart-core-mailables`,
  `sivarcart-core-lang` and `sivarcart-core-assets`

## Rules, References, and Templates

Read before executing:

- `README.md` — the table by table data model, the install flow and the domain layer
- `config/sivarcart-core.php` — store defaults, numbering prefixes, the Wompi settings, the
  notifications of the buyer, the media of the store and the model and guard of the users

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

$cart = $carts->findOrCreate($customer->getKey());
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

- protect a route of the application with a permission of the staff, and ask for the same
  permission in a template:

```php
Route::middleware(['auth', 'store.permission:orders.update'])
    ->get('/orders/{order}/edit', OrderController::class);

// @can('store-permission', 'orders.update')
```

- turn one of the emails to the buyer off:

```php
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Models\NotificationSetting;

NotificationSetting::query()->updateOrCreate(
    ['notification' => OrderNotification::ORDER_SHIPPED],
    ['is_enabled' => false],
);
```

## Anti-patterns

- do not put a price or a stock in `sivarcart_products`; it belongs to the variant
- do not create a table of staff: the staff of the store are the users of the application,
  and the package relates them to the `users` provider of `config/auth.php`
- do not place an order without a buyer: the buyer signs in with the guard of the
  package, and the cart is what says who buys
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
- do not write an image of the catalog by hand; use the `MediaService`, so the file, the
  thumbnail and the columns of the domain stay together
- do not publish the migration of the media library; the package keeps the files in its own
  table of images and the `media` table would stay empty
- do not document the columns of a table as public API; the models and the enums are
