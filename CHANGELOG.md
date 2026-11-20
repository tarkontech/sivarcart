# Release Notes

## [Unreleased](https://github.com/tarkontech/sivarcart-core/compare/v0.1.0...1.x)

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


## [v0.1.0](https://github.com/tarkontech/sivarcart-core/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
