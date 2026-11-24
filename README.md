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
| `sivarcart_notification_settings` | `NotificationSetting` | Qué correos al comprador salen y cuáles no. La aplicación que instala el paquete la edita. |
| `sivarcart_departments` | `Department` | Los 14 departamentos de El Salvador. |
| `sivarcart_document_sequences` | `DocumentSequence` | Contadores por tipo y periodo para numerar órdenes y pagos sin colisiones. |

### Personal de la tienda

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_store_roles` | `StoreRole` | Roles y permisos. |
| `sivarcart_store_role_user` | — | Pivote entre el personal y los roles. |

El personal de la tienda son los usuarios de la aplicación: el paquete no tiene
contraseñas, ni sesiones, ni recuperación de contraseña **del personal**. La llave
foránea del pivote apunta a la tabla `users` de la aplicación anfitriona, y su tipo de
columna se deriva del modelo de usuario, así que una aplicación con llaves `uuid` o con
otra tabla de usuarios no necesita ningún ajuste.

#### Proteger las rutas con los permisos

El paquete trae un middleware y un *ability* de `Gate` para que la aplicación proteja
sus rutas con los permisos que ya guarda en los roles del personal. El alias
`store.permission` y el ability `store-permission` están registrados por el paquete:
la aplicación no tiene que registrar nada.

```php
// routes/web.php de la aplicación
Route::middleware(['auth', 'store.permission:orders.view'])
    ->group(function (): void {
        Route::get('/pedidos', OrderController::class)->name('orders.index');
    });
```

El permiso sale del parámetro del middleware y, cuando no hay, del parámetro
`permission` de la ruta:

```php
Route::get('/reportes/{permission}', ReportController::class)
    ->middleware('store.permission');
```

Las plantillas preguntan por el permiso con `@can`, que usa el mismo criterio:

```blade
@can('store-permission', 'orders.update')
    <a href="{{ route('orders.edit', $order) }}">Editar</a>
@endcan
```

Una petición sin permiso, sin usuario autenticado, o con un usuario cuyo modelo no
lleva el trait de los roles recibe un **403**. El middleware no autentica a nadie: la
aplicación pone su middleware `auth` delante del suyo.

### Autenticación

El paquete **no autentica al personal de la tienda**: las credenciales, las sesiones y
los reseteos de contraseña del personal pertenecen a la aplicación que instala el
paquete, así que el personal de la tienda son los usuarios de la aplicación.

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
asignar desde el pivote o desde el panel de la aplicación que instala el paquete. Un
usuario cuyo modelo no lleva el trait no tiene ningún permiso, así que el middleware de
los permisos le responde con un 403.

### La cuenta del comprador

El comprador es otra cosa: vive en el paquete y **se autentica con el guard del
paquete**, no con el de la aplicación. El paquete registra su provider y su guard al
arrancar, así que la aplicación no toca su `config/auth.php`:

```php
// El guard que el paquete registra.
'auth.guards.sivarcart' => ['driver' => 'session', 'provider' => 'sivarcart'];

// El provider que el paquete registra.
'auth.providers.sivarcart' => ['driver' => 'eloquent', 'model' => Customer::class];
```

El comprador inicia sesión con el servicio de la cuenta:

```php
use SivarcartCore\SivarcartCore\Services\CustomerService;

$customers = app(CustomerService::class);

$customers->attempt('ana@example.com', 'su-contrasena');   // El comprador, o null.
$customers->user();                                          // El comprador que entró, o null.
$customers->logout();                                        // Cierra la sesión del comprador.
```

El comprador y el personal de la tienda viven en sesiones distintas: el primero en
`login_sivarcart_*` y el segundo en `login_web_*`, así que pueden estar los dos dentro
al mismo tiempo.

La aplicación pone su propio middleware delante de las rutas que piden un comprador
iniciado sesión:

```php
Route::middleware(['auth:sivarcart'])->group(function (): void {
    Route::get('/mi-cuenta', AccountController::class)->name('account');
});
```

El alta, la verificación del correo y el restablecimiento de la contraseña los trae
el paquete:

```php
$customer = $customers->register([
    'first_name' => 'Ana',
    'email' => 'ana@example.com',
    'password' => 'su-contrasena',
]);

$customers->sendPasswordResetLink('ana@example.com');   // Manda el enlace al correo.
$customers->resetPassword('ana@example.com', $token, 'su-contrasena-nueva');
```

Los enlaces del correo apuntan a las rutas que declara la aplicación, que el paquete no
renderiza:

```php
// config/sivarcart-core.php
'buyers' => [
    'password_reset_route' => 'password.reset',
    'email_verification_route' => 'verification.verify',
],
```

La tabla del token del restablecimiento es `sivarcart_password_reset_tokens`, que sale
con las migraciones del paquete. Sin ella publicada, el restablecimiento no funciona.

El documento de identidad del comprador se valida contra el tipo que declara, con el
dígito verificador de cada uno: el DUI (módulo 10), el NIT y el RUC (módulo 11), el
pasaporte y el resto. La regla está lista para usarla en los formularios:

```php
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;
use SivarcartCore\SivarcartCore\Rules\CustomerDocument;

'document_number' => [new CustomerDocument(CustomerDocumentType::DUI)],
```

El documento se valida como texto y nunca como número: el DUI empieza con el código
del municipio y ese cero inicial es parte del documento.

> **Ojo con el hasher.** La contraseña del comprador la hashea el cast `hashed` del
> modelo, como hace Laravel. Ese cast lanza una excepción si el hash guardado no
> cuadra con la configuración del hasher del momento, así que cambiar los rounds del
> bcrypt después de tener compradores rompe el guardado de su cuenta hasta volver a
> hashear las contraseñas.

### Compradores

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_customers` | `Customer` | Los compradores. Son usuarios del paquete: se autentican con su guard y sin cuenta no pueden comprar. |
| `sivarcart_customer_addresses` | `CustomerAddress` | Las direcciones guardadas del comprador. |
| `sivarcart_password_reset_tokens` | — | Los tokens del restablecimiento de la contraseña. |

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
| `sivarcart_carts` | `Cart` | Carritos de compra. Siempre son de un comprador. Caducan a los `cart.ttl_days` días. |
| `sivarcart_cart_items` | `CartItem` | Las líneas del carrito. Guardan una copia del precio y de las opciones. |
| `sivarcart_coupons` | `Coupon` | Códigos de descuento: monto fijo, porcentaje o envío gratis. |

El carrito caducado no se le vuelve a dar al comprador: se le crea uno nuevo, y el viejo lo
borra la poda (ver *La caducidad del carrito*).

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

El comprador inicia sesión antes de comprar: el carrito es de un comprador y la orden
sin comprador no existe.

```php
use SivarcartCore\SivarcartCore\Actions\PlaceOrder;
use SivarcartCore\SivarcartCore\Data\CheckoutData;
use SivarcartCore\SivarcartCore\Services\CartService;

$cart = app(CartService::class)->findOrCreate($customer->getKey());

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
puede convertir dos veces; el segundo intento lanza `InvalidCartState`. Un carrito sin
comprador tampoco se convierte en una orden: lanza `CustomerRequired`.

La dirección del checkout se guarda como `CustomerAddress` del comprador, y en el
pedido siguiente se vuelve a leer: el comprador no la escribe otra vez.

### La caducidad del carrito

El carrito tiene fecha de caducidad: vive `cart.ttl_days` días (30 por defecto) desde que se
crea. Un carrito caducado no se le vuelve a dar al comprador — se le crea uno nuevo — y el
viejo queda en la base de datos hasta que la poda lo borra.

```bash
php artisan sivarcart:prune-carts
```

El comando borra los carritos vencidos y sus líneas, e informa cuántos borró. El paquete lo
programa solo una vez al día, así que la aplicación no tiene que hacer nada: si la tienda no
quiere la poda automática, basta con quitar la tarea del `Schedule` de la aplicación.

### Los servicios

| Servicio | Qué hace |
|---|---|
| `CartService` | El carrito del comprador: sus líneas, sus totales y sus cupones. Los totales son una caché de lo que valen las líneas: se recalculan desde los productos, nunca se confían solos. |
| `CatalogService` | La lectura del catálogo: filtra por categoría, marca, precio, disponibilidad y texto, ordena y pagina. El precio y el stock se miran en las variantes activas. |
| `CustomerService` | La cuenta del comprador: el alta, el perfil, la contraseña, la verificación del correo, el restablecimiento y las direcciones guardadas. |
| `PricingService` | Cuánto cuesta cada línea y cuánto cuesta el total. Redondea a dos decimales y siempre arma el total desde las partes ya redondeadas. |
| `CouponService` | Valida los códigos de descuento y calcula lo que valen. El valor de un cupón porcentual **es** el porcentaje: 15 significa 15 %. |
| `InventoryService` | Mueve el stock y escribe el libro mayorista de cada movimiento. |
| `OrderService` | Coloca, paga, prepara, envía, entrega, cancela y reembolsa las órdenes. |
| `PaymentService` | Los pagos y las transacciones que reporta la pasarela. |
| `SequenceService` | Numera los documentos: `SIV-2026-00001`, `PAY-2026-00001`. |
| `StoreService` | Los ajustes de la tienda única de la instalación. |
| `ShippingMethodRateCalculator` | Cuánto cuesta el envío según el método elegido. |

### El catálogo

El catálogo se lee con `CatalogService`: los filtros viven en `CatalogFilters` y el resultado es
un paginador de productos.

```php
use SivarcartCore\SivarcartCore\Data\CatalogFilters;
use SivarcartCore\SivarcartCore\Enums\CatalogSort;
use SivarcartCore\SivarcartCore\Services\CatalogService;

$products = app(CatalogService::class)->search(new CatalogFilters(
    categoryId: 12,        // incluye los productos de sus subcategorías
    brandId: 3,
    minPrice: 10.00,
    maxPrice: 100.00,
    inStockOnly: true,
    search: 'zapatilla',
    sort: CatalogSort::PRICE_ASC,
    perPage: 24,
    page: 1,
));

$products->total();   // cuántos productos hay
$products->items();   // los productos de la página
```

Tres cosas que conviene saber:

- **El precio y el stock son de la variante.** El producto no tiene precio ni stock, así que los
  filtros de precio y de disponibilidad miran sus variantes activas: un producto entra en el
  resultado cuando tiene al menos una que cumple el filtro. Por eso un producto sin ninguna
  variante activa nunca aparece, y solo se ven los que están publicados.
- **El orden por precio usa la variante más barata.** Un producto con varias variantes se ordena
  por el precio de la más barata, y ese criterio no cambia cuando el filtro de precio acota el
  resultado.
- **La búsqueda mira el nombre y el SKU**, no la descripción. El texto admite los comodines de
  `LIKE` (`%` y `_`), y no distingue mayúsculas en MySQL y SQLite.

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
SivarcartCore::customers();   // CustomerService
SivarcartCore::orders();      // OrderService
SivarcartCore::payments();    // PaymentService
SivarcartCore::inventory();   // InventoryService
SivarcartCore::catalog();     // CatalogService
SivarcartCore::paymentMethods();  // PaymentMethodService
SivarcartCore::shippingMethods(); // ShippingMethodService
```

## La API de tienda

El núcleo expone la **API de tienda**: el catálogo, el carrito, el checkout, las órdenes del
comprador y la cuenta del comprador. El **panel de administración no vive en el núcleo**: es
de la aplicación que instala el paquete, que ya tiene el personal, los permisos y sus propias
pantallas.

Las rutas las registra el paquete: la aplicación no tiene que publicarlas. Viven bajo el
prefijo de la sección `api` de la configuración (`api/sivarcart` por defecto) y se pueden
apagar con `api.enabled`.

| Método | Ruta | Qué hace | Sesión |
|---|---|---|---|
| `GET` | `/products` | El catálogo, con los filtros de `CatalogFilters`. | — |
| `GET` | `/products/{uuid}` | Un producto con sus variantes y sus imágenes. | — |
| `GET` | `/categories` | Las categorías del catálogo. | — |
| `GET` | `/brands` | Las marcas del catálogo. | — |
| `POST` | `/account/register` | Crea la cuenta del comprador y lo deja dentro. | — |
| `POST` | `/account/login` | Entra el comprador. | — |
| `POST` | `/account/logout` | Sale el comprador. | — |
| `POST` | `/account/password/email` | Manda el enlace que restablece la contraseña. | — |
| `GET` | `/account` | La cuenta del comprador. | ✔ |
| `PATCH` | `/account` | Actualiza el perfil del comprador. | ✔ |
| `PUT` | `/account/password` | Cambia la contraseña del comprador. | ✔ |
| `GET` | `/account/addresses` | Las direcciones guardadas del comprador. | ✔ |
| `GET` | `/cart` | El carrito del comprador. | ✔ |
| `POST` | `/cart/items` | Pone una variante en el carrito. | ✔ |
| `PATCH` | `/cart/items/{id}` | Cambia las unidades de una línea. | ✔ |
| `DELETE` | `/cart/items/{id}` | Saca una línea del carrito. | ✔ |
| `POST` | `/cart/coupon` | Aplica un código de descuento. | ✔ |
| `DELETE` | `/cart/coupon` | Quita el código de descuento. | ✔ |
| `DELETE` | `/cart` | Vacía el carrito. | ✔ |
| `GET` | `/checkout/options` | Los métodos de pago, los de envío, los departamentos y la tienda. | ✔ |
| `POST` | `/checkout/shipping-quote` | Cuánto cuesta el envío de un método. | ✔ |
| `POST` | `/checkout` | Coloca la orden del carrito. | ✔ |
| `GET` | `/orders` | Las órdenes del comprador. | ✔ |
| `GET` | `/orders/{uuid}` | Una orden del comprador. | ✔ |
| `POST` | `/orders/{uuid}/payment` | El enlace para pagar la orden. | ✔ |

```bash
# El catálogo es público.
curl "http://localhost/api/sivarcart/products?sort=price_asc&per_page=24"

# El comprador entra y su carrito lo espera.
curl -c cookies.txt -X POST http://localhost/api/sivarcart/account/login \
    -H "Content-Type: application/json" \
    -d '{"email":"ana@example.com","password":"su-contrasena"}'

curl -b cookies.txt -X POST http://localhost/api/sivarcart/cart/items \
    -H "Content-Type: application/json" \
    -d '{"variant_id":12,"quantity":2}'

curl -b cookies.txt -X POST http://localhost/api/sivarcart/checkout \
    -H "Content-Type: application/json" \
    -d '{"customer_name":"Ana Guzmán","shipping_address":{"address_line1":"Calle 1","municipality":"San Salvador","department":"San Salvador"}}'
```

El comprador se autentica con el guard que el paquete registra (`sivarcart`), así que la
sesión es una **cookie**: la API sirve para una tienda de mismo origen o para una Single Page
Application servida por la misma aplicación. Una aplicación de otro origen necesita que la
aplicación configure CORS con credenciales, y una aplicación móvil necesitaría tokens, que el
paquete no trae.

> **El panel de administración es de la aplicación.** El núcleo expone la API de tienda y el
> dominio; las pantallas del personal, los permisos y los ajustes de la tienda son de la
> aplicación que instala el paquete.

### Los errores de la API

Los errores del dominio salen con el código que los distingue y con el estado que les
corresponde:

```json
{ "message": "El cupón \"BIENVENIDA\" no existe.", "code": "coupon_not_applicable" }
```

| Error | Estado | `code` |
|---|---|---|
| El comprador no ha iniciado sesión | 401 | `customer_required` |
| El correo ya está en uso | 409 | `customer_already_exists` |
| El carrito no sirve para la operación | 409 | `invalid_cart_state` |
| El cupón no existe o no se puede aplicar | 422 | `coupon_not_applicable` |
| No hay unidades disponibles | 422 | `insufficient_stock` |
| El documento no tiene el formato de su tipo | 422 | `invalid_document` |
| La pasarela de pago falló | 502 | `payment_gateway_failed` |
| Cualquier otro error del dominio | 422 | `sivarcart_core_exception` |

Los errores de validación los responde Laravel: un **422** con la lista de los campos que no
cuadran, en el idioma de la aplicación.

## Configuration

`config/sivarcart-core.php` trae los valores por defecto de la tienda, los prefijos de
numeración de órdenes y pagos, la vida del carrito, la sección `api` de la API de tienda, la
configuración de Wompi (ruta del webhook, ruta del redirect y las formas de pago
habilitadas), las dos claves de autenticación del personal de la tienda y la sección `buyers`
de la cuenta del comprador.

Las claves de autenticación son `null` por defecto, y en ese caso el paquete lee el
modelo del proveedor `users` y el guard por defecto de `config/auth.php`. Solo hay que
tocarlas cuando la aplicación usa otro modelo de usuario u otro guard:

```php
'auth' => [
    'model' => null,   // Por defecto: config('auth.providers.users.model').
    'guard' => null,   // Por defecto: config('auth.defaults.guard').
],
```

La cuenta del comprador tiene su propia sección, con el guard que el paquete registra,
el broker de las contraseñas y las rutas de la aplicación a las que apuntan los enlaces
del correo:

```php
'buyers' => [
    'guard' => 'sivarcart',              // El guard del comprador.
    'provider' => 'sivarcart',           // El provider del comprador.
    'password_reset_table' => 'sivarcart_password_reset_tokens',
    'password_expire_minutes' => 60,     // La vida del token del restablecimiento.
    'password_reset_route' => 'password.reset',
    'email_verification_route' => 'verification.verify',
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

El carrito tiene fecha de caducidad, y su vida sale de aquí:

```php
'cart' => [
    'ttl_days' => 30,   // Los días que vive un carrito antes de caducar.
],
```

Un carrito que caducó no se le vuelve a dar al comprador: se le crea uno nuevo, y el viejo lo
borra el comando `sivarcart:prune-carts`, que el paquete programa una vez al día.

La API de tienda se monta con la sección `api`:

```php
'api' => [
    'enabled' => true,            // Apaga la API sin desinstalar el paquete.
    'prefix' => 'api/sivarcart',  // Donde viven las rutas de la API.
    'middleware' => ['api'],      // El grupo de middleware de la aplicación.
    'rate_limit' => 60,           // Peticiones por minuto y comprador.
],
```

El limitador cuenta las peticiones de cada comprador por separado, y las de los que no han
iniciado sesión por IP. La API **siempre** responde JSON, así que un cliente que no manda la
cabecera `Accept: application/json` recibe un error en JSON y no una página.

Las credenciales de Wompi se guardan cifradas en `sivarcart_payment_methods.credentials`,
de modo que el comerciante puede tener una cuenta por tienda. Cuando el método de pago
no tiene credenciales propias, se usan las del paquete `rmirandasv/laravel-wompi`
(`WOMPI_CLIENT_ID` y `WOMPI_CLIENT_SECRET`).

Los ajustes que el comerciante cambia seguido, como el nombre, el logo, el IVA o el
horario, viven en la tabla `sivarcart_stores` y se editan desde el panel de la aplicación
que instala el paquete.

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
