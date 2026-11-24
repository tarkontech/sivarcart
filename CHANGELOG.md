# Release Notes

## [Unreleased](https://github.com/tarkontech/sivarcart-core/compare/v0.1.0...1.x)

### El núcleo expone la API de tienda

- **la capa HTTP del núcleo es una API de tienda**: el catálogo, el carrito, el cupón, el
  checkout, las órdenes del comprador y la cuenta del comprador. Las rutas las registra el
  paquete (no hay que publicarlas) y viven bajo el prefijo de la sección `api` de la
  configuración (`api/sivarcart` por defecto)
- el **panel de administración no entra en el núcleo**: vive en la aplicación que instala el
  paquete, y el README ya no lo promete. La API sí expone el catálogo, el carrito, el
  checkout y los correos del comprador
- la cuenta del comprador también está en la API: el alta, el login, el logout, el perfil, el
  cambio de contraseña y las direcciones guardadas
- los errores del dominio salen como JSON con un **código estable** (`coupon_not_applicable`,
  `invalid_cart_state`, `insufficient_stock`, `customer_required`, …) y con el estado que les
  corresponde (401, 409, 422 o 502)
- la API **siempre responde JSON**, así que un cliente sin la cabecera `Accept:
  application/json` recibe un error en JSON y no un redirect a un login que puede no existir
- el limitador de peticiones lo registra el paquete (`RateLimiter::for('sivarcart')`) y cuenta
  las peticiones de cada comprador por separado, no por IP
- la configuración gana la sección `api`, con `enabled`, `prefix`, `middleware` y `rate_limit`
- el comprador que ya compró recibe un **carrito nuevo**: `CartService::findOrCreate()` ya no
  devuelve el carrito que se convirtió en una orden, así que puede volver a comprar
- `CustomerService::verifyPassword()` deja que la aplicación (y la API) pidan la contraseña
  actual antes de cambiar la otra
- el paquete declara `laravel/framework` en sus dependencias, porque ya usa Form Requests de
  Laravel

### El comprador tiene cuenta y se autentica en el paquete

- **el comprador ahora es un usuario del paquete**: `Customer` implementa
  `Authenticatable`, `CanResetPassword` y `MustVerifyEmail`, así que inicia sesión con
  el guard del paquete y no con el de la aplicación
- el paquete registra su propio guard, su provider y su broker de contraseñas al
  arrancar, así que la aplicación **no toca su `config/auth.php`**. Los nombres salen de
  la sección `buyers` de la configuración del paquete
- el comprador y el personal de la tienda viven en sesiones distintas: el primero
  entra con `auth:sivarcart` y el segundo con el guard de la aplicación
- **desaparece el checkout de invitado**: `OrderService::place()` exige un comprador y
  lanza `CustomerRequired` cuando el carrito no tiene ninguno. `CartService::findOrCreate()`
  ahora pide el id del comprador y `CheckoutData` ya no lleva `$customerId`: el carrito
  es la única fuente de verdad de quién compra
- la columna `session_id` de `sivarcart_carts` se borra con una migración publicada: sin
  checkout de invitado no tiene consumidor
- el paquete trae **todo el ciclo de la cuenta**: el alta, la verificación del correo y
  el restablecimiento de la contraseña, con su broker, sus notificaciones y sus
  plantillas markdown publicables
- el nuevo `CustomerService` da de alta al comprador, actualiza su perfil, cambia su
  contraseña, verifica su correo, adopta las órdenes que puso como invitado, escribe
  `last_order_at`, guarda sus direcciones y lo inicia y cierra sesión
- la dirección del checkout se guarda como `CustomerAddress` del comprador y en el
  pedido siguiente se vuelve a leer: el comprador no la escribe otra vez
- el documento de identidad se valida contra el tipo que declara, con el dígito
  verificador de cada uno (DUI con módulo 10, NIT y RUC con módulo 11). La regla
  `CustomerDocument` sirve para los formularios y para el dominio
- las migraciones nuevas: `remember_token` en `sivarcart_customers` y la tabla
  `sivarcart_password_reset_tokens`
- la configuración gana la sección `buyers`, con el guard, el provider, el broker y las
  rutas de la aplicación a las que apuntan los enlaces del correo

### Las etiquetas de los enums salen de los archivos de idioma

- `label()` de los enums ya no escribe el texto dentro del código: lo busca con
  `__('sivarcart-core::messages...')`, así que la etiqueta habla el idioma de la
  aplicación. Los enums afectados son `CouponType`, `CustomerDocumentType`,
  `DocumentSequenceType`, `InventoryMovementType`, `OrderHistoryAxis`, `OrderStatus`,
  `PaymentMethodType`, `PaymentRecordStatus`, `PaymentStatus`,
  `PaymentTransactionStatus`, `ProductOptionType`, `ProductStatus`, `ProductType` y
  `ShippingMethodType`
- el texto en español que los enums traían escrito se conserva como el valor por
  defecto: vive en `lang/es/messages.php` y en `lang/es_SV/messages.php`, que ahora
  tienen un grupo por enum (`order_status`, `payment_status`,
  `inventory_movement_type`, ...) con una clave por caso
- `lang/en/messages.php` gana el texto en inglés de las mismas etiquetas, para que una
  aplicación en inglés no muestre las etiquetas en español

### El comprador recibe los correos de su orden

- el paquete trae la capa de notificación: cada evento del ciclo de la orden dispara
  una notificación de Laravel que entrega un *mailable* markdown. Los correos del
  comprador son los de la orden colocada, la orden pagada, la orden enviada, la orden
  entregada, la orden cancelada, el reembolso y el pago que no pasó
- las notificaciones van encoladas (`ShouldQueue`): el correo nunca alarga el checkout
  y un mailer caído no rompe una orden que ya está guardada. El error va al log
- **qué correos salen lo decide el comerciante en la base de datos**: la tabla nueva
  `sivarcart_notification_settings` guarda una fila por notificación que el panel de
  administración haya encendido o apagado. Una notificación sin fila sigue el valor de
  `config('sivarcart-core.notifications.enabled')`, que viene habilitado
- el enum `OrderNotification` es el registro de las notificaciones que existen: es lo
  que el panel lista
- el comando `sivarcart-core:publish-mailables` publica las plantillas markdown en
  `resources/views/vendor/sivarcart-core/mail`, con el tag `sivarcart-core-mailables`.
  Las vistas del namespace `sivarcart-core` se leen primero en la aplicación, así que
  la plantilla publicada gana sin tocar PHP
- los textos de los correos salen de los archivos de idioma: `lang/en/messages.php` deja
  de ser un placeholder y gana su versión en español (`lang/es/messages.php`) y la del
  locale de la tienda (`lang/es_SV/messages.php`)
- la configuración gana la sección `notifications`, con el valor por defecto y la
  dirección desde la que salen los correos

### La orden tiene un solo estado

- el enum `FulfillmentStatus` desaparece: su vocabulario (`processing`, `shipped`,
  `delivered`, `returned`) se integra en `OrderStatus`, que ahora cubre el ciclo
  completo de la orden. La columna `sivarcart_orders.fulfillment_status` se borra
  con una migración publicada
- `OrderStatus::PAID` desaparece del enum: el estado del pago ya lo dice
  `PaymentStatus`, y los dos campos nunca podían discrepar. Una orden pagada y sin
  preparar es `OrderStatus::PENDING`
- el pago es un eje aparte del ciclo de la orden: una orden contra entrega puede
  estar `delivered` y `unpaid` a la vez, y una orden pagada puede esperar días a
  que la preparen. `PaymentStatus` gana `allowedTransitions()` con la misma
  filosofía que el ciclo de la orden
- `Order::transitionTo()` ya no escribe el pago: el pago se mueve con
  `Order::transitionPaymentTo()`, que no hace nada cuando el pago ya está en ese
  estado y lanza `InvalidPaymentStatusTransition` cuando el cambio es imposible
- el historial de la orden guarda **un renglón por eje**: gana la columna `axis`
  (`status` o `payment`), las columnas `payment_status` y
  `previous_payment_status`, y `status` pasa a admitir nulos. El scope
  `forAxis()` los separa
- el grafo de transiciones cambia: una orden sin pagar ya se puede enviar, porque
  en el mercado local la orden contra entrega sale del store antes de que el
  comprador pague. `cancelled` y `refunded` siguen siendo finales, y una orden
  devuelta todavía se puede reembolsar

### El despacho de la orden

- las acciones `PrepareOrder`, `ShipOrder`, `DeliverOrder` y `ReturnOrder` mueven
  la orden por su ciclo físico, con las reglas del enum y un renglón en el
  historial de cada cambio
- los eventos `OrderPrepared`, `OrderShipped`, `OrderDelivered` y `OrderReturned`
  se emiten después del commit, como los demás
- el despacho **no mueve el stock**: el libro mayorista sigue con sus cuatro
  momentos (reserva, venta, liberación y devolución). Las unidades vuelven al
  stock cuando la orden se cancela o se reembolsa
- `OrderService` gana `prepare()`, `ship()`, `deliver()` y `markReturned()`, y las
  acciones de pago y de reembolso aceptan el usuario que las hizo, para que el
  historial diga quién movió cada eje

### La autenticación es la de Laravel

- el paquete ya no tiene autenticación propia: desaparecen la tabla `sivarcart_store_users`,
  el modelo `StoreUser` y su factory. Las credenciales, las sesiones y la recuperación de
  contraseña son de la aplicación que instala el paquete
- el personal de la tienda son los usuarios de la aplicación. El modelo se toma del
  proveedor `users` de `config/auth.php` y el guard de su guard por defecto, y ambos se
  pueden anular con las claves `auth.model` y `auth.guard` de la configuración del paquete
- la llave foránea de `sivarcart_store_role_user`, `sivarcart_inventory_movements` y
  `sivarcart_order_status_histories` se deriva del modelo de usuario, así que una
  aplicación con llaves `uuid` o con otra tabla de usuarios no necesita ningún ajuste
- el trait `HasStoreRoles` se agrega al modelo de usuario de la aplicación para que el
  usuario tenga sus roles y sus permisos, y `Support\Auth` resuelve el usuario
  autenticado, el modelo y el guard
- `Order::transitionTo()` ahora acepta cualquier `Authenticatable`, no un `StoreUser`

### Los permisos del personal ya protegen las rutas

- el vocabulario de permisos que el paquete siembra deja de ser decorativo: el middleware
  `EnsureStorePermission` y el ability `store-permission` del `Gate` los aplican de verdad
- el middleware queda registrado con el alias `store.permission`, así que la aplicación
  anfitriona protege sus rutas sin registrar nada:

```php
Route::middleware(['auth', 'store.permission:orders.view'])->group(function (): void {
    Route::get('/orders', OrderController::class)->name('orders.index');
});
```

- el permiso sale del parámetro del middleware y, cuando no hay, del parámetro `permission`
  de la ruta. Las plantillas preguntan por él con `@can('store-permission', 'orders.update')`
- el middleware **falla cerrado**: una petición sin permiso, sin usuario autenticado, o con
  un usuario cuyo modelo no lleva el trait `HasStoreRoles` recibe un 403. El paquete sigue
  sin autenticar a nadie: el middleware `auth` de la aplicación va delante
- el criterio de autorización vive en un solo sitio, `Support\Auth::hasStorePermission()`, que
  usan el middleware y el ability del `Gate`
- el paquete no pisa un ability `store-permission` que la aplicación ya haya definido

### La capa de dominio

- `CartService`, `PricingService`, `CouponService`, `InventoryService`, `OrderService`,
  `PaymentService`, `SequenceService` y `StoreService`: el comportamiento de la tienda,
  con los servicios como espina dorsal del dominio
- las acciones `PlaceOrder`, `StartPayment`, `HandleWompiPayment`, `MarkOrderAsPaid`,
  `CancelOrder` y `RefundOrder` orquestan los servicios y emiten los eventos
  `OrderPlaced`, `OrderPaid`, `OrderCancelled`, `OrderRefunded` y `PaymentFailed`
  después del commit
- el stock se reserva al colocar la orden, se convierte en venta al cobrarla y se
  libera al cancelarla, siempre con su entrada en el libro mayorista
- los precios se calculan con el IVA incluido o excluido según la tienda, y el
  descuento se reparte entre las líneas sin perder ni inventar un centavo
- el webhook de Wompi queda registrado en el paquete: valida la firma, responde siempre
  200 y es idempotente, así que la segunda entrega del mismo pago no cobra dos veces
- dos contratos para reemplazar lo que hace falta: `ShippingRateCalculator` y
  `PaymentGateway`, con un driver nuevo en el `PaymentGatewayManager`

### Correcciones

- las relaciones `variant` de `CartItem`, `OrderItem`, `ProductImage` e
  `InventoryMovement` apuntaban a una columna que no existe, así que nunca cargaban la
  variante
- la relación `method` de `Payment` apuntaba a `method_id` en lugar de
  `payment_method_id`
- el cupón de un carrito se quedaba cacheado al aplicarlo, y el total no tomaba el
  descuento
- el cliente de Wompi ya no se construye al resolver la pasarela, sino al crear el
  enlace de pago: una tienda sin credenciales de Wompi puede usar el resto del paquete
- `phpstan.neon.dist` excluía `workbench/storage`, que es un artefacto de compilación
  del workbench y no existe en un clon recién bajado. PHPStan se negaba a correr con
  ese error, así que la ruta se marca como opcional


## [v0.1.0](https://github.com/tarkontech/sivarcart-core/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
