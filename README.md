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
| `sivarcart_departments` | `Department` | Los 14 departamentos de El Salvador. |
| `sivarcart_document_sequences` | `DocumentSequence` | Contadores por tipo y periodo para numerar órdenes y pagos sin colisiones. |

### Personal de la tienda

| Tabla | Modelo | Para qué sirve |
|---|---|---|
| `sivarcart_store_users` | `StoreUser` | El dueño y los empleados. El paquete trae su propia autenticación y no depende de la tabla `users` de la aplicación. |
| `sivarcart_store_roles` | `StoreRole` | Roles y permisos. |
| `sivarcart_store_role_user` | — | Pivote entre el personal y los roles. |

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
| `sivarcart_order_status_histories` | `OrderStatusHistory` | Cada cambio de estado de una orden. |

Cambiar el precio de un producto o renombrarlo nunca reescribe una orden ya colocada. Las
líneas de la orden guardan su propia copia, y el producto puede quedar borrado sin que
la orden deje de poder leerse.

El ciclo de vida de la orden vive en el enum `OrderStatus`:

```php
use SivarcartCore\SivarcartCore\Enums\OrderStatus;

$order->transitionTo(OrderStatus::PAID, note: 'Pago recibido');

$order->status;                 // OrderStatus::PAID
$order->payment_status;         // PaymentStatus::PAID
$order->statusHistories;        // la transición queda registrada
```

Las transiciones que no están permitidas lanzan `InvalidOrderStatusTransition`.
`cancelled` y `refunded` son estados finales.

## Configuration

`config/sivarcart-core.php` trae los valores por defecto de la tienda, los prefijos de
numeración de órdenes y pagos, y la configuración de Wompi (ruta del webhook, ruta del
redirect y las formas de pago habilitadas).

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
