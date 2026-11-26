# Release Notes

## [Unreleased](https://github.com/tarkontech/sivarcart-core/compare/v0.1.0...1.x)

### El panel de administración entra en el núcleo

- **el paquete trae el panel del personal de la tienda**: el tablero, los pedidos (lista y
  detalle, con el ciclo de vida de la orden), el catálogo (los productos, las categorías, las
  marcas, los métodos de envío y los de pago), los clientes, los ajustes de la tienda, los
  roles y el personal. Vive bajo `sivarcart/admin` y se apaga con `admin.enabled`
- el panel **no autentica a nadie**: usa los usuarios, el guard y la sesión de la
  aplicación, así que el modelo del usuario tiene que llevar el trait `HasStoreRoles`. Sin
  él, el panel abre su pantalla de instalación y explica qué hacer
- **el panel es una aplicación de Inertia y React**: sus pantallas son componentes de React
  escritos en TypeScript, y el panel tiene su propio root view y su propio middleware, así
  que una aplicación que ya tenga un Inertia de su propio no se ve afectada
- **el panel no le pide Node a nadie**: el bundle viaja con el paquete ya compilado y lo
  sirve el propio paquete, así que el comerciante instala el paquete y el panel está. Si el
  comerciante compila su propio bundle, **el suyo gana**, sin tocar la configuración
- **el panel se sobreescribe de cuatro maneras**: la página entera (con
  `SivarcartCore::admin()->page()` y `SivarcartCore::admin()->widget()`), los tokens del
  tema (la sección `admin.theme` de la configuración), el bundle propio, y el código
  (con `vendor:publish --tag=sivarcart-core-admin-source` o con el registro de shadcn del
  paquete)
- **ningún color del panel es un color de la paleta de Tailwind**: todos son tokens
  semánticos (`bg-background`, `text-foreground`, `bg-primary`…), así que un tema es un
  cambio del CSS y nunca un cambio de los componentes. Un test del paquete lo comprueba
- el panel trae su propia hoja de estilo con **Tailwind 4 y el sistema de diseño de
  shadcn/ui sobre Base UI**, y sus textos viajan del servidor, así que el panel habla el
  idioma de la aplicación
- **la escritura del panel va por sus propias rutas**: mover una orden pide
  `orders.update` y guardar los ajustes pide `settings.update`, un permiso nuevo que el
  `manager` lleva y el `staff` no
- **el comando `sivarcart-core:create-admin`** crea al primer miembro del personal de la tienda
  y le da su rol (`owner` por defecto). El seeder dejaba los roles, pero no las personas: el
  personal de la tienda son los usuarios de la aplicación, y sin una persona con rol el panel
  no tenía a quién mostrarle nada
- el paquete depende ahora de `inertiajs/inertia-laravel` (`^3.0`) y **ya no de
  `livewire/livewire`**
- **los assets del panel se versionan con el hash de su contenido**: la dirección
  de un archivo solo cambia cuando el archivo cambia, así que el navegador nunca
  se queda con un bundle que ya no es el que trae el paquete
- **la versión de los assets es una carpeta de la dirección** (`/assets/{version}/admin.js`),
  y no una consulta: el bundle está partido en piezas y las piezas se piden entre ellas con
  direcciones relativas, así que la carpeta viaja con ellas. Con la versión de consulta el
  navegador servía de su caché el `admin.js` de la versión anterior y el panel mezclaba dos
  bundles (dos copias de React y una pantalla en blanco)
- **el dinero del panel se formatea con la etiqueta del idioma que entiende
  `Intl`**: Laravel escribe el idioma con guion bajo (`es_SV`) y el estándar de
  las etiquetas de idioma usa el guion (`es-SV`)
- **el formulario de los ajustes de la tienda envía un `PUT`**, que es el método
  de la ruta que los guarda

### El panel de administración escribe

- **el panel ya no es de lectura**: escribe el catálogo (los productos y sus variantes, las
  categorías, las marcas, los métodos de envío y los de pago), el personal, los pedidos y los
  ajustes de la tienda
- **leer y escribir son permisos distintos**: `catalog.update` para el catálogo, `orders.update`
  para los pedidos, `settings.update` para los ajustes y sus imágenes, `roles.update` para los
  roles y `staff.update` para el personal. El panel esconde los botones al personal que no los
  tiene, pero la ruta los negaría igual
- **la pantalla del producto escribe el producto y sus variantes juntas** (precio, sku,
  existencias, umbral de existencias y la variante por defecto), lo publica, lo archiva y lo
  saca del catálogo. Las categorías y la marca se escriben en la misma pantalla
- **una categoría no puede quedar dentro de sí misma**, y el servicio lo niega
- **las credenciales de un método de pago nunca vuelven al navegador**: el panel ve las claves
  y la bolsa vacía, y las vuelve a escribir cuando cambian
- **dar de baja al personal no borra al usuario de la aplicación**: le quita los roles de la
  tienda, así que deja de ver el panel. Los usuarios son de la aplicación
- **los roles de la tienda conservan sus comodines** (`*`, `catalog.*`) cuando el personal no
  los tocó, así que guardar un rol no le quita en silencio un permiso que el comodín daba
- **la pantalla del pedido reembolsa y registra la devolución**: el reembolso lo escribe
  `OrderService::refund()` y la devolución `OrderService::markReturned()`, así que el stock y
  los pagos se mueven como deben
- **los ajustes de la tienda suben su logo y su favicon**, y los acepta el `MediaService`, así
  que un archivo que la tienda no acepta no deja nada detrás

### El pago de la orden sigue a su pago

- **el eje del pago de la orden deja de ser decorativo**: `PaymentStatus::PENDING`,
  `::FAILED` y `::CANCELLED` ya no son casos sin consumidor. Cada uno lo pone la acción
  que atiende el dinero, y el cambio queda en el historial de la orden
- `StartPayment` deja la orden en `pending` cuando ya hay enlace de pago, y **una
  pasarela que falla la deja en `unpaid`**: el comprador puede pedir el enlace otra vez
- el webhook de Wompi deja la orden en `failed` cuando la pasarela no aprueba la
  transacción, y el cambio se escribe junto al del pago, en la misma transacción
- `CancelOrder` deja la orden en `cancelled`, **salvo que ya estuviera pagada**: una
  orden que cobró y luego se cancela conserva `paid`, porque devolver el dinero es el
  camino del reembolso
- las transiciones del pago están protegidas: una orden ya pagada no vuelve a `failed`
  cuando llega un webhook tardío, y el webhook no se pierde

### El personal de la tienda repone el stock

- **el comando `sivarcart:restock`** pone unidades de vuelta en el stock de una variante
  y deja el movimiento escrito en el libro mayorista, como los del checkout
- con la cantidad suma unidades (movimiento `restock`) y con `--stock` fija la cantidad
  exacta (movimiento `adjustment`), que es la forma de explicar una diferencia de
  inventario
- el stock y su movimiento se escriben en la misma transacción, así que los dos siempre
  quedan de acuerdo

### El núcleo ya no declara nada sin uso

- **la tabla `sivarcart_settings` desaparece**, junto con el modelo `Setting`: ningún
  servicio, comando o API la leía o la escribía. La migración que la creaba sale del
  paquete y la nueva `2026_01_01_000038_drop_sivarcart_settings_table` limpia la base de
  las instalaciones que ya la tenían publicada
- **la nota interna de la orden (`orders.admin_note`) también desaparece**: las notas de
  una orden viven en el historial de sus cambios. La migración
  `2026_01_01_000039_drop_admin_note_from_sivarcart_orders_table` la borra, y su `down()`
  la vuelve a crear
- `Store::meta` sale del `fillable` del modelo: la columna y el cast se quedan, pero el
  modelo ya no acepta escribirlos
- **el permiso `reports.*` sale del rol `manager`**: la capa de reportes no entra en el
  núcleo, así que el permiso protegía algo que no existe. Un permiso que no protege nada
  engaña al personal de la tienda
- las zonas de envío por departamento **se quedan en un módulo opcional**, como ya decía
  el README: el contrato `ShippingRateCalculator` es el punto de extensión real
- una prueba nueva comprueba el vocabulario sembrado de los permisos, para que ningún
  permiso vuelva a proteger una función que el núcleo no tiene

### La tienda sube, valida y miniatura sus imágenes

- **el paquete ya sabe guardar imágenes**: el `MediaService` sube las fotos de los
  productos, de las variantes, de las marcas y de la tienda, valida el tipo y el tamaño del
  archivo, y escribe las columnas del dominio
- las imágenes se guardan con [Spatie Media Library](https://github.com/spatie/laravel-medialibrary),
  pero **en la tabla del propio paquete** (`sivarcart_product_images`): así todas las
  tablas siguen llevando el prefijo `sivarcart_` y la migración de la librería no hay que
  publicarla
- la nueva migración `2026_01_01_000037_add_media_columns_to_sivarcart_product_images_table`
  **solo agrega columnas**: una instalación que ya tenía el paquete no pierde ni una fila
  ni un archivo. La columna `product_id` pasa a admitir nulos, porque el logo de una marca
  y el de la tienda no son de ningún producto
- **el `MediaService` no deja nada a medias**: un archivo que la tienda no acepta lanza
  `InvalidMediaFile` (422, código `invalid_media_file`) y no deja ni fila ni archivo
- las imágenes de una variante **son también del producto**, y la galería del producto las
  muestra y las numera en el mismo orden
- cada modelo declara sus colecciones: `Product` y `ProductVariant` tienen `images`, `Brand`
  tiene `logo` y `Store` tiene `logo` y `favicon`. Los logos son de un archivo solo: el
  nuevo reemplaza al viejo
- las miniaturas salen de la sección `media` de la configuración: el disco, el tamaño
  máximo, los tipos de archivo y el mapa de miniaturas
- `Brand::logo_path`, `Store::logo_path`, `Store::favicon_path` y `Product::thumbnail_path`
  por fin tienen un escritor: antes se leían y nadie los escribía
- la API de tienda devuelve las imágenes con `url` y `thumbnail_url` además de los campos
  de siempre, y la marca devuelve `logo_url`
- el paquete declara `spatie/laravel-medialibrary` como dependencia, así que **el servidor
  necesita la extensión `exif` de PHP** (y `gd` para las miniaturas)
- la CI instala `exif` y `gd` para que la matriz siga en verde en Linux y en Windows

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
