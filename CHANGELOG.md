# Release Notes

## [Unreleased](https://github.com/tarkontech/sivarcart-core/compare/v0.1.0...1.x)

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
