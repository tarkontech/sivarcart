<div align="center">
    <h1>SivarCart Core</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/tarkontech/sivarcart-core"><img src="https://img.shields.io/packagist/v/tarkontech/sivarcart-core.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/tarkontech/sivarcart-core"><img src="https://img.shields.io/packagist/php-v/tarkontech/sivarcart-core.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/tarkontech/sivarcart-core"><img src="https://img.shields.io/badge/laravel-cloud-badge/tarkontech/sivarcart-core?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/tarkontech/sivarcart-core/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/tarkontech/sivarcart-core/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/tarkontech/sivarcart-core"><img src="https://img.shields.io/packagist/dt/tarkontech/sivarcart-core.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Tienda en linea para comercios de El Salvador.

Este es el **núcleo** de SivarCart: el modelo de datos de una tienda en linea auto
hospedada, con Wompi como pasarela de pago. El software es open source: instálalo en
tus servidores, publícalo, y úsalo como quieras.

## Installation

Instala el paquete con Composer:

```bash
composer require tarkontech/sivarcart-core
```

Y luego instala la tienda con un solo comando:

```bash
php artisan sivarcart-core:install
```

El comando publica las migraciones en `database/migrations`, las ejecuta y siembra los
datos base: los 14 departamentos de El Salvador, las categorías de IVA, los roles del
personal, la tienda, los métodos de pago y los métodos de envío.

> **La aplicación necesita su propia tabla de usuarios.** El personal de la tienda son
> los usuarios de la aplicación, así que la migración que crea la tabla `users` tiene que
> existir antes de correr las migraciones del paquete.

Si prefieres hacerlo paso a paso:

```bash
php artisan vendor:publish --tag="sivarcart-core-migrations"
php artisan migrate
php artisan db:seed --class="SivarcartCore\\SivarcartCore\\Database\\Seeders\\DatabaseSeeder"
```

### Publicar los demás recursos

```bash
php artisan vendor:publish --tag="sivarcart-core"           # todo
php artisan vendor:publish --tag="sivarcart-core-config"    # config/sivarcart-core.php
php artisan vendor:publish --tag="sivarcart-core-views"     # resources/views/vendor/sivarcart-core
php artisan vendor:publish --tag="sivarcart-core-mailables" # resources/views/vendor/sivarcart-core/mail
php artisan vendor:publish --tag="sivarcart-core-lang"      # lang/vendor/sivarcart-core
php artisan vendor:publish --tag="sivarcart-core-assets"    # public/vendor/sivarcart-core
```

> **Las migraciones se publican, no se cargan desde el paquete.** Laravel deriva la clase
> de una migración quitándole la fecha, así que una copia publicada y una del paquete
> terminarían declarando la misma clase y `php artisan migrate` fallaría. Publicarlas
> además deja el esquema en manos del comerciante, y `vendor:publish` nunca sobrescribe
> un archivo que ya existe: una personalización sobrevive a una actualización.

## Modelo de datos

Todas las tablas llevan el prefijo `sivarcart_`. Los modelos viven en
`SivarcartCore\SivarcartCore\Models`.

### Tienda y configuración

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_stores` | `Store` | Perfil y ajustes de la tienda: nombre, NIT, logo, moneda, zona horaria, IVA por defecto. |
| `sivarcart_settings` | `Setting` | Ajustes libres en clave y valor. Es el punto de extensión de los módulos opcionales. |
| `sivarcart_notification_settings` | `NotificationSetting` | Qué correos al comprador salen y cuáles no. El panel de administración la edita. |
| `sivarcart_departments` | `Department` | Los 14 departamentos de El Salvador. |
| `sivarcart_document_sequences` | `DocumentSequence` | Contadores por tipo y periodo para numerar órdenes y pagos sin colisiones. |

### Personal de la tienda

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_store_roles` | `StoreRole` | Roles y permisos. |
| `sivarcart_store_role_user` | — | Pivote entre el personal y los roles. |

El personal de la tienda son los usuarios de la aplicación: el paquete no tiene
contraseñas, ni sesiones, ni recuperación de contraseña. La llave foránea del pivote
apunta a la tabla `users` de la aplicación anfitriona, y su tipo de columna se deriva
del modelo de usuario, así que una aplicación con llaves `uuid` o con otra tabla de
usuarios no necesita ningún ajuste.

### Autenticación

El paquete usa la autenticación de Laravel. Toma el modelo de usuario del proveedor
`users` de `config/auth.php`, que es `App\Models\User` por defecto:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

Para que un usuario tenga los roles de la tienda, agregale el trait a tu modelo:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use SivarcartCore\SivarcartCore\Models\Concerns\HasStoreRoles;

class User extends Authenticatable
{
    use HasStoreRoles;
}
```

Con el trait el usuario tiene sus roles y sus permisos:

```php
$user->storeRoles;                          // Los roles de la tienda que tiene.
$user->hasStoreRole('owner');               // ¿Tiene el rol indicado?
$user->hasStorePermission('orders.view');   // ¿Alguno de sus roles da el permiso?
$user->assignStoreRole('manager');          // Le da un rol.
$user->revokeStoreRole('manager');          // Se lo quita.
```

Y el paquete resuelve el usuario autenticado con el guard configurado:

```php
use SivarcartCore\SivarcartCore\Support\Auth;

Auth::user();     // El usuario autenticado, o null.
Auth::model();    // La clase del modelo de usuario.
Auth::guard();    // El nombre del guard.
```

El trait es opcional: sin él, el paquete sigue funcionando y los roles se pueden
asignar desde el pivote o desde el panel de administración.

### Compradores

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_customers` | `Customer` | Los compradores. El correo y la contraseña son opcionales, lo que permite el checkout de invitado. |
| `sivarcart_customer_addresses` | `CustomerAddress` | Las direcciones guardadas del cliente. |

### Catálogo

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_categories` | `Category` | Árbol de categorías. |
| `sivarcart_category_product` | — | Pivote entre categorías y productos. |
| `sivarcart_brands` | `Brand` | Marcas. |
| `sivarcart_taxes` | `Tax` | Categorías fiscales y su tasa (IVA 13%, exento, sin IVA). |
| `sivarcart_products` | `Product` | La ficha del producto. **No tiene precio ni stock.** |
| `sivarcart_product_options` | `ProductOption` | Opciones del producto, como "Talla" o "Color". |
| `sivarcart_product_option_values` | `ProductOptionValue` | Los valores que acepta cada opción. |
| `sivarcart_product_variants` | `ProductVariant` | La unidad vendible: precio, SKU y stock. |
| `sivarcart_product_variant_values` | — | Pivote entre variantes y valores de opción. |
| `sivarcart_product_images` | `ProductImage` | Imágenes del producto o de una variante. |

Todo producto tiene **al menos una variante**. Un producto simple tiene exactamente una
variante sin valores de opción, así el precio, el descuento y el stock siempre pasan por
un solo camino de código:

```php
use SivarcartCore\SivarcartCore\Models\Product;

$product = Product::query()->published()->with('variants')->first();

$product->defaultVariant()?->price;   // 25.50
$product->sku();                      // el SKU de la variante por defecto
$product->priceRange();               // ['min' => 10.0, 'max' => 25.0]
```

### Inventario

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_inventory_movements` | `InventoryMovement` | Libro mayorista de existencias. Solo se agrega: explica cómo el stock llegó a su valor actual. |

### Carrito y descuentos

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_carts` | `Cart` | Carritos de compra, con o sin cliente. |
| `sivarcart_cart_items` | `CartItem` | Las líneas del carrito. Guardan una copia del precio y de las opciones. |
| `sivarcart_coupons` | `Coupon` | Códigos de descuento: monto fijo, porcentaje o envío gratis. |

### Envío

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_shipping_methods` | `ShippingMethod` | Tarifa fija, envío gratis o recoger en tienda. Las zonas por departamento quedan para un módulo opcional. |

### Pagos

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_payment_methods` | `PaymentMethod` | Métodos de pago en línea (Wompi) y fuera de línea (contra entrega, transferencia). Las credenciales de Wompi se guardan cifradas. |
| `sivarcart_payments` | `Payment` | El pago de una orden. |
| `sivarcart_payment_transactions` | `PaymentTransaction` | Cada transacción que reporta la pasarela. Es un libro que solo se agrega. |

La conexión con Wompi está aislada en estas dos tablas. El paquete
[`rmirandasv/laravel-wompi`](https://github.com/rmirandasv/laravel-wompi) resuelve el
OAuth2, la firma HMAC de los webhooks y la normalización del payload; el núcleo solo
persiste lo que llega:

| Columna | Viene de Wompi |
|---|---|
| `gateway_transaction_id` | `IdTransaccion` |
| `gateway_payment_attempt_id` | `IdIntentoPago` |
| `idempotency_key` | `idTransaccion:idIntentoPago` |
| `merchant_reference` | `IdentificadorEnlaceComercio` |
| `is_approved` | `EsAprobada` |
| `is_production` | `EsProductiva` |
| `result_code` | `ResultadoTransaccion` |
| `payment_method` | `FormaPagoUtilizada` |
| `authorization_code` | `CodigoAutorizacion` |
| `raw_payload` | el payload original, intacto |

`idempotency_key` tiene un índice único. Wompi reintenta la entrega de un webhook cuando
no recibe un 2xx, así que el mismo pago puede llegar más de una vez: la base de datos,
no la caché, garantiza que solo se procese una vez.

Una orden **nunca** se marca como pagada si la transacción fue de prueba. La regla es
`is_approved AND is_production`, la misma de `WompiWebhook::isSuccessful()`:

```php
$transaction->isSuccessful();   // true solo si fue aprobada y fue un cobro real
```

### Órdenes

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_orders` | `Order` | La orden, con los totales y las direcciones ya congelados. |
| `sivarcart_order_items` | `OrderItem` | Lo comprado: nombre, SKU, opciones, precio y tasa de impuesto son una copia. |
| `sivarcart_order_status_histories` | `OrderStatusHistory` | Cada cambio de una orden: los de su ciclo de vida y los de su pago. |

Cambiar el precio de un producto o renombrarlo nunca reescribe una orden ya colocada. Las
líneas de la orden guardan su propia copia, y el producto puede quedar borrado sin que
la orden deje de poder leerse.

La orden lleva **dos ejes**, y nunca dicen lo mismo:

- `status` dice **dónde está la orden**: pendiente, en preparación, enviada, entregada,
  devuelta, cancelada o reembolsada.
- `payment_status` dice **dónde está el dinero**: sin pagar, pendiente, pagada, fallida,
  cancelada o reembolsada.

El ciclo de vida de la orden vive en el enum `OrderStatus`, y el del pago en
`PaymentStatus`:

```php
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;

$order->transitionTo(OrderStatus::SHIPPED, note: 'Salió con el courier');
$order->transitionPaymentTo(PaymentStatus::PAID, note: 'Pago recibido');

$order->status;                 // OrderStatus::SHIPPED
$order->payment_status;         // PaymentStatus::PAID
$order->statusHistories;        // la transición queda registrada
```

Los dos ejes se mueven por separado porque no siempre van al mismo paso: una orden
contra entrega sale del store sin estar pagada, y una orden pagada puede esperar días a
que la preparen. Por eso una orden se lee con dos campos y no con uno.

Los dos enums comparten algunos nombres (`pending`, `cancelled`, `refunded`), pero cada
valor vive en su propia columna, así que el campo dice siempre a qué eje pertenece:
`status = cancelled` es una orden cancelada y `payment_status = cancelled` sería un
pago cancelado.

Las transiciones que no están permitidas lanzan `InvalidOrderStatusTransition`.
`cancelled` y `refunded` son estados finales. Las transiciones del pago lanzan
`InvalidPaymentStatusTransition`, y no hacen nada cuando el pago ya está en ese estado:
el mismo pago se puede confirmar dos veces.

El historial de la orden guarda **un renglón por eje**, y la columna `axis` los
distingue:

```php
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;

$order->statusHistories()->forAxis(OrderHistoryAxis::STATUS)->get();  // el ciclo de la orden
$order->statusHistories()->forAxis(OrderHistoryAxis::PAYMENT)->get(); // el estado del pago
```

**Las etiquetas de los enums salen de los archivos de idioma.** `label()` no escribe el texto
dentro del código: lo busca en el archivo de idioma del paquete, así que el enum habla el
idioma de la aplicación.

```php
app()->setLocale('en');

OrderStatus::PENDING->label();   // Pending

app()->setLocale('es');

OrderStatus::PENDING->label();   // Pendiente
```

Cada enum tiene su grupo en `lang/en/messages.php` y `lang/es/messages.php` (y en
`lang/es_SV/messages.php`), con una clave por caso: `order_status.pending`,
`payment_status.unpaid`, `inventory_movement_type.sale`. El texto en español que los enums
tenían escrito antes es el que sale por defecto, porque es el que vive en el archivo de
español.

## Capa de dominio

El paquete no es solo un esquema: es el comportamiento de la tienda. Los servicios viven
en `SivarcartCore\SivarcartCore\Services` y son la espina dorsal del dominio. Las
acciones (`SivarcartCore\SivarcartCore\Actions`) orquestan los servicios y emiten los
eventos; los modelos no llevan reglas de negocio.

### El flujo de una compra

```php
use SivarcartCore\SivarcartCore\Actions\PlaceOrder;
use SivarcartCore\SivarcartCore\Data\CheckoutData;
use SivarcartCore\SivarcartCore\Services\CartService;

$cart = app(CartService::class)->findOrCreate($customer?->getKey(), $sessionId);

$cart = app(CartService::class)->addItem($cart, $variant, 2);
$cart = app(CartService::class)->applyCoupon($cart, 'BIENVENIDA');

$order = app(PlaceOrder::class)->execute($cart, new CheckoutData(
    customerName: 'Ana Guzmán',
    customerEmail: 'ana@example.com',
    shippingAddress: ['municipality' => 'San Salvador', 'department' => 'San Salvador'],
    shippingMethodCode: 'estandar',
    paymentMethodCode: 'wompi',
));
```

Colocar la orden reserva el stock, congela lo comprado en la orden y cierra el carrito,
todo en una sola transacción: si algo falla, no queda nada escrito. El carrito no se
puede convertir dos veces; el segundo intento lanza `InvalidCartState`.

### Los servicios

| Servicio | Qué hace |
|---|---|
| `CartService` | El carrito del comprador: sus líneas, sus totales y sus cupones. Los totales son una caché de lo que valen las líneas: se recalculan desde los productos, nunca se confían solos. |
| `PricingService` | Cuánto cuesta cada línea y cuánto cuesta el total. Redondea a dos decimales y siempre arma el total desde las partes ya redondeadas. |
| `CouponService` | Valida los códigos de descuento y calcula lo que valen. El valor de un cupón porcentual **es** el porcentaje: 15 significa 15 %. |
| `InventoryService` | Mueve el stock y escribe el libro mayorista de cada movimiento. |
| `OrderService` | Coloca, paga, prepara, envía, entrega, cancela y reembolsa las órdenes. |
| `PaymentService` | Los pagos y las transacciones que reporta la pasarela. |
| `SequenceService` | Numera los documentos: `SIV-2026-00001`, `PAY-2026-00001`. |
| `StoreService` | Los ajustes de la tienda única de la instalación. |
| `ShippingMethodRateCalculator` | Cuánto cuesta el envío según el método elegido. |

### El stock

El stock tiene tres momentos y el libro mayorista (`sivarcart_inventory_movements`)
explica cada uno:

| Momento | Stock | Reservado | Movimiento |
|---|---|---|---|
| Se coloca la orden | igual | + unidades | `reservation` |
| Se cobra la orden | − unidades | − unidades | `sale` |
| Se cancela la orden | igual | − unidades | `release` |
| Se reembolsa la orden | + unidades | igual | `return` |

El stock disponible nunca cuenta dos veces una unidad reservada, así que la última
unidad de un producto no se vende dos veces. Al cobrar, la reserva se convierte en
venta: **el stock disponible no cambia** al pagar.

**Despachar la orden no mueve el stock.** El libro mayorista tiene cuatro momentos y
ninguno es "enviar": preparar, enviar y entregar solo describen la realidad física de
la orden. Las unidades vuelven al stock cuando la orden se cancela o se reembolsa.

### El despacho de la orden

El despacho es la mitad física del ciclo de vida de la orden, y lo maneja el personal de
la tienda con las acciones del paquete:

```php
use SivarcartCore\SivarcartCore\Actions\DeliverOrder;
use SivarcartCore\SivarcartCore\Actions\PrepareOrder;
use SivarcartCore\SivarcartCore\Actions\ReturnOrder;
use SivarcartCore\SivarcartCore\Actions\ShipOrder;

app(PrepareOrder::class)->execute($order);        // pendiente → en preparación
app(ShipOrder::class)->execute($order);           // → enviada
app(DeliverOrder::class)->execute($order);        // → entregada
app(ReturnOrder::class)->execute($order, null, 'El comprador la rechazó');
```

El ciclo es este, y las transiciones que no están permitidas lanzan
`InvalidOrderStatusTransition`:

```
pending ──→ processing ──→ shipped ──→ delivered
   │             │             │            │
   │             │             └─→ returned ┘
   └─→ shipped   │
                 └──→ cancelled
```

Un par de reglas que vale la pena conocer:

- **Una orden sin pagar se puede enviar.** El grafo lo permite porque en el mercado
  local la orden contra entrega sale del store antes de que el comprador pague. Por eso
  `payment_status` es un eje aparte: una orden puede estar `delivered` y `unpaid` a la
  vez.
- **`cancelled` y `refunded` son finales.** Una orden que ya salió no se cancela: se
  devuelve. Y una orden devuelta todavía se puede reembolsar.

### Los precios y el IVA

Con `prices_include_tax` en la tienda (el caso de El Salvador), el precio del catálogo
ya incluye el IVA:

```
subtotal  = Σ (precio × unidades)
total     = subtotal − descuento + envío
```

El `tax_total` es informativo: es la parte del total que pertenece al Estado. Cuando la
tienda no incluye el IVA en los precios, el total lo suma:

```
total     = subtotal − descuento + IVA + envío
```

El descuento se reparte entre las líneas con el método del mayor resto, en centavos
enteros: nunca se pierde ni se inventa un centavo. Un cupón de envío gratis deja el
envío cotizado intacto y mete el ahorro en el descuento, para que la fórmula del total
siga siendo la misma.

### Cobrar con Wompi

```php
use SivarcartCore\SivarcartCore\Actions\StartPayment;

$link = app(StartPayment::class)->execute($order);

$link->url;        // https://checkout.wompi.sv/l/...
$link->reference;  // el identificador del enlace en Wompi
```

La llamada a la pasarela **nunca** ocurre dentro de una transacción: si Wompi falla, la
orden y su reserva sobreviven y el comprador puede pagar después. Cada intento queda
contado en `payments.attempt_count`, y el enlace se guarda en `payments.meta` para que
reintentar no cree un enlace nuevo.

El paquete registra la ruta del webhook y la application **no** tiene que publicarla:

```
POST {config('sivarcart-core.wompi.webhook_path')}   →  sivarcart-core.wompi.webhook
```

El middleware `wompi.webhook` del paquete [`rmirandasv/laravel-wompi`](https://github.com/rmirandasv/laravel-wompi)
valida la firma HMAC antes de que corra el controlador. El controlador **siempre**
responde 200: un webhook que no se puede aplicar se registra en el log, pero responder
con un error haría que Wompi entregara el mismo webhook una y otra vez.

La orden solo se marca pagada si la transacción fue aprobada **y** fue un cobro real
(`EsAprobada && EsProductiva`). Una transacción de prueba deja el pago fallido, la orden
sin pagar y su reserva intacta.

El webhook es idempotente: la segunda entrega del mismo pago no vuelve a cobrar ni a
mover el stock, porque `payment_transactions.idempotency_key` tiene un índice único.

### Los eventos

Las acciones emiten eventos **después** del commit, así que ningún listener ve un estado
que todavía puede revertirse:

| Evento | Cuándo |
|---|---|
| `OrderPlaced` | Se colocó la orden. |
| `OrderPaid` | Se pagó la orden. |
| `OrderPrepared` | Empezó a preparar la orden. |
| `OrderShipped` | Se envió la orden. |
| `OrderDelivered` | Se entregó la orden. |
| `OrderReturned` | La orden volvió a la tienda. |
| `OrderCancelled` | Se canceló la orden. |
| `OrderRefunded` | Se registró un reembolso. |
| `PaymentFailed` | Un pago no pasó. |

```php
use SivarcartCore\SivarcartCore\Events\OrderPaid;

Event::listen(function (OrderPaid $event): void {
    $event->order;      // la orden que se pagó
    $event->payment;    // el pago, cuando lo hubo
});
```

### Los correos al comprador

El paquete escribe al comprador cuando la orden avanza. Cada correo es una notificación
de Laravel que entrega un *mailable* markdown, así que el comprador recibe el resumen
de su orden con los componentes de correo de Laravel (`x-mail::message`, `x-mail::table`,
`x-mail::button`).

| Notificación | Cuándo |
|---|---|
| `order_placed` | Se colocó la orden. |
| `order_paid` | Se pagó la orden. |
| `order_shipped` | Se envió la orden. |
| `order_delivered` | Se entregó la orden. |
| `order_cancelled` | Se canceló la orden. |
| `order_refunded` | Se registró un reembolso. |
| `payment_failed` | Un pago no pasó. |

Los correos salen **encolados** (`ShouldQueue`), así que nunca alargan el checkout, y un
mailer caído no rompe una orden que ya está guardada.

**Qué correos salen lo decide el comerciante, en la base de datos.** La tabla
`sivarcart_notification_settings` guarda una fila por notificación que el panel de
administración haya cambiado:

```php
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Models\NotificationSetting;

NotificationSetting::query()->updateOrCreate(
    ['notification' => OrderNotification::ORDER_PLACED],
    ['is_enabled' => false],   // este correo ya no sale
);
```

Una notificación **sin fila** no está apagada: sigue el valor de
`config('sivarcart-core.notifications.enabled')`, que viene habilitado. El conjunto de
notificaciones que existen lo define el código, en `OrderNotification::cases()`, que es
lo que el panel lista.

**Para reescribir un correo**, publica las plantillas y edítalas: las vistas del
namespace `sivarcart-core` se leen primero en la aplicación, así que la copia gana sin
tocar una línea de PHP.

```bash
php artisan sivarcart-core:publish-mailables
```

El comando publica las siete plantillas en
`resources/views/vendor/sivarcart-core/mail`. Sin `--force` no pisa las que ya están
publicadas; con `--force` las sobrescribe. El texto de los correos vive en los archivos
de idioma (`lang/en/messages.php` y `lang/es/messages.php`), que también se publican, y
ahí viven también las etiquetas de los enums.

### Cancelar y reembolsar

```php
use SivarcartCore\SivarcartCore\Actions\CancelOrder;
use SivarcartCore\SivarcartCore\Actions\MarkOrderAsPaid;
use SivarcartCore\SivarcartCore\Actions\RefundOrder;

app(CancelOrder::class)->execute($order, 'El comprador se arrepintió');
app(MarkOrderAsPaid::class)->execute($order);          // el pago se cobró fuera de línea
app(RefundOrder::class)->execute($order, 25.50, 'Producto devuelto');
```

Cancelar devuelve las unidades reservadas al stock y cancela el pago que nunca se
cobró. `MarkOrderAsPaid` es la forma de confirmar un pago que la tienda cobró por su
cuenta, como el efectivo de una orden contra entrega.

> **Pagar no mueve la orden.** `MarkOrderAsPaid` solo cambia el eje del pago: la orden
> sigue `pending` hasta que el personal la prepara y la envía. Por eso una orden
> pagada se lee con los dos campos, `status` y `payment_status`.

> **El reembolso se registra, no se ejecuta.** La API de Wompi no expone la forma de
> devolver el dinero de un pago, así que el núcleo deja constancia del reembolso, repone
> el stock y cierra la orden; el comerciante aplica la devolución en el panel de Wompi.
> El stock se repone por valor: las unidades se calculan a prorrata del monto devuelto,
> en enteros y nunca por encima de lo que se vendió.

### Los puntos de extensión

Solo hay dos contratos, y los dos tienen una columna que ya los respalda:

| Contrato | Lo que trae el paquete | Cómo lo reemplaza un módulo |
|---|---|---|
| `ShippingRateCalculator` | `ShippingMethodRateCalculator`: el precio del método. | Enlaza su propia implementación: `app()->bind(ShippingRateCalculator::class, ...)`. |
| `PaymentGateway` | `WompiPaymentGateway`. | Registra un driver en el `PaymentGatewayManager`: `app(PaymentGatewayManager::class)->extend('mi-pasarela', ...)`. |

```php
use SivarcartCore\SivarcartCore\Contracts\ShippingRateCalculator;
use SivarcartCore\SivarcartCore\Payments\PaymentGatewayManager;

// Un módulo que cobra según el departamento.
app()->bind(ShippingRateCalculator::class, MiCalculadora::class);

// Un módulo que cobra con otra pasarela.
app(PaymentGatewayManager::class)->extend('mi-pasarela', fn () => new MiPasarela());
```

El driver de un método de pago sale de `payment_methods.driver`; cuando no dice nada,
se usa `config('sivarcart-core.wompi.driver')`.

No hay un contrato para el IVA: la tasa sale de la categoría fiscal del producto y, si
no tiene, del IVA por defecto de la tienda. Un módulo que necesite otra cosa (precios
sin IVA por producto, retenciones, impuestos por departamento) lo resuelve con sus
propias categorías fiscales, no reemplazando el cálculo del núcleo.

### La facade

La facade solo es azúcar: agrupa los servicios para llegar a ellos desde una plantilla o
un comando.

```php
use SivarcartCore\SivarcartCore\Facades\SivarcartCore;

SivarcartCore::carts();       // CartService
SivarcartCore::orders();      // OrderService
SivarcartCore::payments();    // PaymentService
SivarcartCore::inventory();   // InventoryService
```

## Configuration

`config/sivarcart-core.php` trae los valores por defecto de la tienda, los prefijos de
numeración de órdenes y pagos, la configuración de Wompi (ruta del webhook, ruta del
redirect y las formas de pago habilitadas) y las dos claves de autenticación.

Las claves de autenticación son `null` por defecto, y en ese caso el paquete lee el
modelo del proveedor `users` y el guard por defecto de `config/auth.php`. Solo hay que
tocarlas cuando la aplicación usa otro modelo de usuario u otro guard:

```php
'auth' => [
    'model' => null,   // Por defecto: config('auth.providers.users.model').
    'guard' => null,   // Por defecto: config('auth.defaults.guard').
],
```

Los correos al comprador se controlan desde la base de datos (ver *Los correos al
comprador*), y la configuración solo pone el valor por defecto:

```php
'notifications' => [
    // El valor que se usa para las notificaciones que no tienen fila guardada.
    'enabled' => true,

    // La dirección desde la que salen los correos. Con null gana el "mail.from"
    // de la aplicación.
    'from' => null,
],
```

Las credenciales de Wompi se guardan cifradas en `sivarcart_payment_methods.credentials`,
de modo que el comerciante puede tener una cuenta por tienda. Cuando el método de pago
no tiene credenciales propias, se usan las del paquete `rmirandasv/laravel-wompi`
(`WOMPI_CLIENT_ID` y `WOMPI_CLIENT_SECRET`).

Los ajustes que el comerciante cambia seguido, como el nombre, el logo, el IVA o el
horario, viven en la tabla `sivarcart_stores` y se editan desde el panel de administración.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to SivarCart Core! Please review our
[contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Ronald Miranda](https://github.com/tarkontech)
- [All Contributors](../../contributors)

## License

SivarCart Core is open-sourced software licensed under the [MIT license](LICENSE.md).
