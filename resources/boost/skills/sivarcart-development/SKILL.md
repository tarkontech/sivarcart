---
name: sivarcart-development
description: >
  Install and adopt the SivarCart package in Laravel applications: a
  self-hosted online store for El Salvador with Wompi as the payment gateway.
license: MIT
metadata:
  author: Ronald Miranda
---

# SivarCart

Use this skill when a Laravel application needs the SivarCart package, the data
model and the domain of an online store for El Salvador.

## Primary Goal

- apply the `tarkontech/sivarcart` package's public API in the smallest correct way

## Workflow

### 1. Install the store

- require the package and run the install command, which publishes the migrations,
  runs them and seeds the base data of El Salvador:

```bash
composer require tarkontech/sivarcart
php artisan sivarcart:install
```

- the command accepts `--no-seed` to skip the base data and `--force` to overwrite
  the migrations that were already published
- the server needs the **`exif` extension of PHP** and, for the thumbnails, the `gd` one:
  the package stores the images with Spatie Media Library and that library requires `exif`,
  so without it Composer does not even install the package
- do not publish the migration of the media library (`media-library-migrations`): the
  package keeps the files in its own table of images, so the `media` table is never used

### 2. Read the model

- the models live in `Tarkontech\SivarCart\Models` and every table is prefixed
  with `sivarcart_`
- a product never holds a price or a stock: those live in its variants, so a simple
  product owns exactly one variant and a variable product owns one per combination
- an order freezes what was bought in `sivarcart_order_items`, so editing the catalog
  never rewrites an order that was already placed
- an order carries **two axes**: `status` says where the order is
  (`Tarkontech\SivarCart\Enums\OrderStatus`) and `payment_status` says where
  the money is (`Tarkontech\SivarCart\Enums\PaymentStatus`). They move apart:
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
  `config/sivarcart.php`
- add the trait of the roles to the user model of the application:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tarkontech\SivarCart\Models\Concerns\HasStoreRoles;

class User extends Authenticatable
{
    use HasStoreRoles;
}
```

- with the trait the user knows the roles of the store it holds and the permissions those
  roles grant, and the package reads the authenticated user with
  `Tarkontech\SivarCart\Support\Auth::user()`
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

### 4. Use the administration panel of the package

- the package brings the **administration panel of the staff**: the dashboard, the orders, the
  catalog (the products, the categories, the brands, the shipping methods and the payment
  methods), the customers, the settings of the store, the roles of the staff and the staff
  itself. It is mounted under `sivarcart/admin` (the `admin.prefix` of the configuration) and it
  is turned off with `admin.enabled`
- the panel **does not authenticate anybody**: it uses the users, the guard and the session of
  the application, so the `HasStoreRoles` trait of the section above is required. Without it the
  panel shows its setup screen
- every screen asks for the permissions the package already seeds (`orders.view`,
  `catalog.view`, `customers.view`, `settings.view`), and the writing goes through the routes
  of the panel: moving an order asks `orders.update` and saving the settings asks
  `settings.update`
- **reading and writing are different permissions**: `catalog.update` writes the catalog,
  `orders.update` moves and refunds the orders, `settings.update` writes the settings and their
  images, `roles.update` writes the permissions of the roles and `staff.update` writes the staff.
  The panel hides the buttons the staff cannot use, and the routes refuse them anyway
- **taking a member of the staff off does not delete the user of the application**: it takes the
  roles of the store away, so they stop seeing the panel. The users belong to the application
- the panel is an **Inertia and React** application: its screens are components of React written
  in TypeScript, and it has its own root view and its own middleware, so an application that
  already has an Inertia of its own keeps it untouched
- **the panel does not ask the application for Node**: the bundle travels with the package
  already compiled and the package serves it, so the merchant installs the package and the panel
  is there. If the merchant compiles a bundle of its own, **theirs wins**
- to change the whole interface, publish the source of the panel with the tag
  `sivarcart-admin-source`, write `resources/js/sivarcart/admin.tsx`, add the entry to the
  `vite.config.js` of the application and compile. To change one component, edit the file of
  that component
- to install the panel with the shadcn CLI, the package publishes a registry:
  `npx shadcn@latest add @sivarcart/admin`
- to add a screen of its own, register it in the panel and it appears in the navigation with the
  permission it needs. The component is the **name** of the component, the same one the bundle
  resolves:

```php
use Tarkontech\SivarCart\Facades\SivarCart;

SivarCart::admin()->page(
    key: 'quick-view',
    label: 'Vista rapida',
    permission: 'orders.view',
    component: 'Admin/QuickView',
);
```

- the stylesheet of the panel is **Tailwind 4 with the design system of shadcn/ui over Base UI**,
  and **no color of the panel is a color of the Tailwind palette**: every one of them is a
  semantic token (`bg-background`, `text-foreground`, `bg-primary`, `bg-muted`,
  `border-border`). A theme is a change of the CSS, never of the components, and the tokens come
  from the `admin.theme` section of the configuration

### 5. Sell with the store of the package

- the package brings **the store the shopper buys in**: the home, the catalog, the product page,
  the cart, the checkout, the order confirmation and the account of the buyer (sign in, sign up
  and password reset). It lives under the prefix of the `storefront` section of
  `config/sivarcart.php` (`tienda` by default) and it is **on by default**
- **the store and the store API are two ways of mounting the same store and they live
  together**: the API (`api/sivarcart`) is the headless mode for the clients that are not a
  browser, and the store is the mode with an interface. Turn each one off with its own flag
  (`api.enabled` and `storefront.enabled`); turning one off never turns the other off
- the store is **a second Inertia application**: it has its own root view
  (`sivarcart-storefront::app`) and its own middleware, so an application that already
  has an Inertia of its own keeps it untouched. Its pages are `Storefront/...` and the panel's
  are `Admin/...`, so they never mix
- **the store does not call the API over HTTP**: it uses the services of the domain, the same
  ones the API and the panel use, with its own `Tarkontech\SivarCart\Storefront\StorefrontPresenter`.
  The totals are computed on the server and the client only shows them
- **the store asks for a signed in buyer** on the cart, the checkout and the orders, the same
  way the domain does: the buyer of the store is a `Customer`, not the user of the application
- the store draws the screen that changes the password, so the link of the reset email points
  there when the store is on. An application that wants its own screen names its route in
  `buyers.password_reset_route`. The **email verification stays with the application**: the
  store does not draw it, so `buyers.email_verification_route` is still the route of the
  application
- the store brings its own compiled bundle and its own tokens, and it is themed from the
  `storefront.theme` section of the configuration, separately from the panel's theme

```php
// config/sivarcart.php
'storefront' => [
    'enabled' => true,
    'prefix' => 'tienda',
    'middleware' => ['web'],
    'theme' => ['primary' => '#1d4ed8', 'radius' => '0.5rem'],
    'bundle' => ['entry' => 'resources/js/sivarcart/storefront.tsx', 'directory' => 'build'],
],
```

- a merchant that wants its own storefront publishes the source and compiles its own bundle;
  the merchant's bundle wins, without touching the configuration:

```bash
php artisan vendor:publish --tag="sivarcart-storefront-source"
php artisan vendor:publish --tag="sivarcart-storefront-view"
```

### 6. Sell to buyers with an account

- the **buyer** signs in with the guard of the package, not with the guard of the
  application: the package registers its own guard, its own provider and its own password
  broker when it boots, so the application does not touch its `config/auth.php`
- the guard is `sivarcart` por defecto and the names come from the `buyers` section of
  `config/sivarcart.php`
- the buyer signs in and out with `CustomerService`:

```php
use Tarkontech\SivarCart\Services\CustomerService;

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
- the emails of the account point at `buyers.password_reset_route` and
  `buyers.email_verification_route`. The reset screen is the one of the store when the store
  is on, and the verification screen is always the one of the application
- the document of identity is checked against its type with the check digit of each one;
  the rule `Tarkontech\SivarCart\Rules\CustomerDocument` works in the forms and in
  the domain

### 7. Sell

- the services in `Tarkontech\SivarCart\Services` are the spine of the domain:
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
use Tarkontech\SivarCart\Actions\PlaceOrder;
use Tarkontech\SivarCart\Data\CheckoutData;

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

### 8. Collect the money with Wompi

- the credentials of the gateway live in `sivarcart_payment_methods` and are stored
  encrypted; the package `rmirandasv/laravel-wompi` handles the OAuth2, the HMAC
  signature and the payload normalization
- ask for the payment link with the `StartPayment` action; the merchant reference of the
  link is the reference of the payment, so the webhook finds the order without guessing
- the package registers the route of the webhook: `POST webhooks/wompi`, named
  `sivarcart.wompi.webhook`. The application must **not** publish the route of the
  `laravel-wompi` package, because both would answer the same path
- the controller always answers 200: a webhook that cannot be applied is logged, and
  answering with an error would make Wompi deliver it over and over
- mark an order as paid only when the transaction was approved **and** was a real
  charge: a test transaction (`EsProductiva` false) was never billed to the customer
- the webhook is idempotent: `payment_transactions.idempotency_key` has a unique index,
  so a repeated delivery never charges or moves the stock twice

### 9. Move the order

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

### 10. Write to the buyer

- the package writes to the buyer when the order moves: every notification of
  `Tarkontech\SivarCart\Enums\OrderNotification` delivers a markdown mailable, and
  the notifications are queued, so a mail never holds the checkout
- **which emails leave the store is a decision of the merchant, and it lives in the
  database**: `sivarcart_notification_settings` holds one row per notification the
  administration panel turned on or off. A notification without a row follows
  `config('sivarcart.notifications.enabled')`, which comes enabled
- the notifications that exist are the cases of `OrderNotification`: `order_placed`,
  `order_paid`, `order_shipped`, `order_delivered`, `order_cancelled`, `order_refunded`
  and `payment_failed`
- to rewrite an email, publish the templates and edit them; the views of the
  `sivarcart` namespace are read from the application first, so the published copy
  wins without touching PHP:

```bash
php artisan sivarcart:publish-mailables
```

- the texts of the emails live in the language files (`lang/en/messages.php` and
  `lang/es/messages.php`), which are published with the tag `sivarcart-lang`
- the sender comes from `config('sivarcart.notifications.from')`, and when it is
  null the `mail.from` of the application is used

### 11. Keep the images of the store

- the images of the products, of the variants, of the brands and of the store are stored with
  Spatie Media Library, but in the table of the package (`sivarcart_product_images`), so every
  table keeps the `sivarcart_` prefix and the migration of the library is never published
- upload them with the `MediaService`: it validates the file, decides which product the image
  belongs to and which one is the main image, and it leaves nothing behind when it refuses a
  file (no row and no file on the disk)

```php
use Tarkontech\SivarCart\Services\MediaService;

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
  `media` section of `config/sivarcart.php`
- the store API returns the images with their path, their disk and their text, and also
  with `url` and `thumbnail_url`

### 12. Extend the store

- a module keeps its own settings in its own table: the package does not have a table
  of free form settings
- the shipping cost comes from the `ShippingRateCalculator` contract and the payment
  from the `PaymentGateway` contract; both can be replaced without touching the package
- another gateway is registered as a driver of the payment gateway manager:

```php
app(PaymentGatewayManager::class)->extend('mi-pasarela', fn () => new MiPasarela());
```

- the publish tags are `sivarcart`, `sivarcart-config`,
  `sivarcart-migrations`, `sivarcart-views`, `sivarcart-mailables`,
  `sivarcart-lang`, `sivarcart-assets`, `sivarcart-admin-source` (the source
  of the panel), `sivarcart-panel-view` (the root view of the panel),
  `sivarcart-storefront-source` (the source of the store) and
  `sivarcart-storefront-view` (the root view of the store)

## Rules, References, and Templates

Read before executing:

- `README.md` — the table by table data model, the install flow and the domain layer
- `config/sivarcart.php` — store defaults, numbering prefixes, the Wompi settings, the
  notifications of the buyer, the media of the store, the model and guard of the users, the
  `admin` section of the administration panel and the `storefront` section of the store

## Examples

- publish the migrations and seed the base data, then read the store settings:

```php
use Tarkontech\SivarCart\Models\Store;

$store = Store::query()->firstOrFail();

$store->currency;             // "USD"
$store->prices_include_tax;   // true
```

- build a cart, apply a coupon and place the order:

```php
use Tarkontech\SivarCart\Services\CartService;

$carts = app(CartService::class);

$cart = $carts->findOrCreate($customer->getKey());
$cart = $carts->addItem($cart, $variant, 2);
$cart = $carts->applyCoupon($cart, 'BIENVENIDA');
```

- ask for the payment link of an order and read the URL to send the shopper:

```php
use Tarkontech\SivarCart\Actions\StartPayment;

$link = app(StartPayment::class)->execute($order);

$link->url;        // https://checkout.wompi.sv/l/...
$link->reference;  // the identifier of the link on Wompi
```

- listen to the events of the domain:

```php
use Tarkontech\SivarCart\Events\OrderPaid;

Event::listen(function (OrderPaid $event): void {
    $event->order;
    $event->payment;
});
```

- move an order through its dispatch and read both axes:

```php
use Tarkontech\SivarCart\Actions\ShipOrder;

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
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Models\NotificationSetting;

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
- do not build a second administration panel before using the one of the package: it already
  brings the orders, the catalog, the customers, the settings and the roles
- do not edit the views of the package in place; write them in
  `resources/views/vendor/sivarcart/` so the customization survives an update
- do not put a color of the Tailwind palette in a component of the panel: every color of the
  panel is a semantic token (`bg-background`, `text-foreground`, `bg-primary`, `bg-muted`,
  `border-border`), and a test of the package fails when one of them is not
- do not build the bundle of the panel in the application of the merchant: the package brings it
  compiled, and only the merchant that wants its own interface needs Node
- do not document the columns of a table as public API; the models and the enums are
