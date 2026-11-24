<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Data\CheckoutData;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Exceptions\CustomerRequired;
use SivarcartCore\SivarcartCore\Exceptions\InsufficientStock;
use SivarcartCore\SivarcartCore\Exceptions\InvalidCartState;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\Coupon;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Department;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('turns a cart into an order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($order->order_number)->toStartWith('SIV-')
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->payment_status)->toBe(PaymentStatus::UNPAID)
        ->and($order->customer_name)->toBe('Ana Guzmán')
        ->and($order->currency)->toBe('USD')
        ->and((float) $order->subtotal)->toBe(25.5)
        ->and((float) $order->total)->toBe(25.5);
});

it('closes the cart it took the order from', function () {
    $scenario = CheckoutScenario::buyer();

    app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($scenario->cart->fresh()->isConverted())->toBeTrue();
});

it('keeps a snapshot of what was bought in the order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(1)
        ->and((float) $order->items->first()->unit_price)->toBe(25.5)
        ->and((float) $order->items->first()->tax_rate)->toBe(0.13)
        ->and((float) $order->items->first()->line_total)->toBe(25.5);
});

it('does not change the price of an order when the catalog is edited', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $scenario->variant->update(['price' => 99.99]);

    expect((float) $order->items->first()->unit_price)->toBe(25.5)
        ->and((float) $order->items->first()->line_total)->toBe(25.5);
});

it('reserves the stock of the order', function () {
    $scenario = CheckoutScenario::buyer();

    app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($scenario->variant->fresh()->reserved_quantity)->toBe(1)
        ->and($scenario->variant->fresh()->stock_quantity)->toBe(25)
        ->and($scenario->variant->fresh()->availableStock())->toBe(24);
});

it('refuses to place an order without stock', function () {
    $scenario = CheckoutScenario::buyer();
    $scenario->variant->update(['stock_quantity' => 0]);

    expect(fn () => app(OrderService::class)->place($scenario->cart, $scenario->data))
        ->toThrow(InsufficientStock::class);
});

it('refuses to place the order of a cart that was already converted', function () {
    $scenario = CheckoutScenario::buyer();
    $orders = app(OrderService::class);

    $orders->place($scenario->cart, $scenario->data);

    expect(fn () => $orders->place($scenario->cart->fresh(), $scenario->data))
        ->toThrow(InvalidCartState::class);
});

it('refuses to place the order of an empty cart', function () {
    $scenario = CheckoutScenario::buyer();

    $empty = Cart::factory()->create(['customer_id' => $scenario->customer->getKey()]);

    expect(fn () => app(OrderService::class)->place($empty, $scenario->data))
        ->toThrow(InvalidCartState::class, 'El carrito está vacío.');
});

it('refuses to place the order of a cart without a buyer', function () {
    $scenario = CheckoutScenario::buyer();

    $orphan = Cart::factory()->create();

    expect(fn () => app(OrderService::class)->place($orphan, $scenario->data))
        ->toThrow(CustomerRequired::class);
});

it('applies the coupon of the cart to the order', function () {
    $scenario = CheckoutScenario::buyer(quantity: 2);
    $coupon = Coupon::factory()->percentage(10)->create(['code' => 'DIEZ']);
    $carts = app(CartService::class);

    $carts->applyCoupon($scenario->cart, 'DIEZ');

    $order = app(OrderService::class)->place($scenario->cart->fresh(), $scenario->data);

    expect($order->coupon_code)->toBe('DIEZ')
        ->and((float) $order->discount_total)->toBe(5.1)
        ->and((float) $order->total)->toBe(45.9)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

it('adds the cost of the shipping to the order', function () {
    $method = ShippingMethod::factory()->create(['code' => 'estandar', 'price' => 5]);
    $scenario = CheckoutScenario::buyer($method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect((float) $order->shipping_total)->toBe(5.0)
        ->and((float) $order->total)->toBe(30.5)
        ->and($order->shipping_method_code)->toBe('estandar');
});

it('refuses a shipping method that is not available', function () {
    $scenario = CheckoutScenario::buyer('inexistente');

    expect(fn () => app(OrderService::class)->place($scenario->cart, $scenario->data))
        ->toThrow(SivarcartCoreException::class);
});

it('refuses a shipping method that does not apply to the amount of the order', function () {
    $method = ShippingMethod::factory()->create(['code' => 'grande', 'min_order_amount' => 100]);
    $scenario = CheckoutScenario::buyer($method->code);

    expect(fn () => app(OrderService::class)->place($scenario->cart, $scenario->data))
        ->toThrow(SivarcartCoreException::class);
});

it('creates the pending payment of the order', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->method_code)->toBe('cash_on_delivery')
        ->and((float) $order->payments->first()->amount)->toBe(25.5)
        ->and($order->payments->first()->reference)->toStartWith('PAY-');
});

it('refuses a payment method that is not available', function () {
    $scenario = CheckoutScenario::buyer(paymentMethodCode: 'inexistente');

    expect(fn () => app(OrderService::class)->place($scenario->cart, $scenario->data))
        ->toThrow(SivarcartCoreException::class);
});

it('belongs the order to the customer of the checkout', function () {
    $customer = Customer::factory()->create();
    $scenario = CheckoutScenario::forCustomer($customer);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($order->customer_id)->toBe($customer->getKey());
});

it('writes the moment of the last order of the buyer', function () {
    $scenario = CheckoutScenario::buyer();

    expect($scenario->customer->last_order_at)->toBeNull();

    app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect($scenario->customer->fresh()->last_order_at)->not->toBeNull();
});

it('saves the address of the buyer', function () {
    $department = Department::factory()->create(['name' => 'San Salvador', 'code' => 'SS']);
    $scenario = CheckoutScenario::buyer();

    app(OrderService::class)->place($scenario->cart, $scenario->data);

    $address = $scenario->customer->addresses()->firstOrFail();

    expect($address->address_line1)->toBe('Calle 1')
        ->and($address->municipality)->toBe('San Salvador')
        ->and($address->department_id)->toBe($department->getKey())
        ->and($address->is_default)->toBeTrue();
});

it('reads the saved address of the buyer in the next order', function () {
    Department::factory()->create(['name' => 'San Salvador', 'code' => 'SS']);

    $scenario = CheckoutScenario::buyer();

    app(OrderService::class)->place($scenario->cart, $scenario->data);

    $second = CheckoutScenario::forCustomer($scenario->customer);

    $order = app(OrderService::class)->place(
        $second->cart,
        new CheckoutData(customerName: 'Ana Guzmán'),
    );

    expect($order->shipping_address['address_line1'])->toBe('Calle 1')
        ->and($order->shipping_address['department'])->toBe('San Salvador')
        ->and($scenario->customer->addresses()->count())->toBe(1);
});

it('marks an order as paid', function () {
    $scenario = CheckoutScenario::buyer();
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(OrderService::class)->markPaid($order);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::PAID)
        ->and($order->fresh()->paid_at)->not->toBeNull();
});

it('turns the reserved units into a sale when the order is paid', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);
    app(OrderService::class)->convertReservationToSale($order);

    expect($scenario->variant->fresh()->stock_quantity)->toBe(24)
        ->and($scenario->variant->fresh()->reserved_quantity)->toBe(0);
});

it('cancels an order and gives the reserved units back', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);
    app(OrderService::class)->cancel($order, 'El comprador se arrepintió');

    expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED)
        ->and($order->fresh()->cancelled_at)->not->toBeNull()
        ->and($scenario->variant->fresh()->reserved_quantity)->toBe(0)
        ->and($scenario->variant->fresh()->stock_quantity)->toBe(25)
        ->and($order->fresh()->payments->first()->status->value)->toBe('cancelled');
});

it('refunds an order and gives the units back to the stock', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);
    $payment = $order->payments->first();

    app(PaymentService::class)->markPaid($payment);
    app(OrderService::class)->markPaid($order);
    app(OrderService::class)->convertReservationToSale($order);

    app(OrderService::class)->refund($order, 25.50, 'Producto devuelto');

    expect($payment->fresh()->status->value)->toBe('refunded')
        ->and((float) $payment->fresh()->refunded_amount)->toBe(25.5)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::REFUNDED)
        ->and($order->fresh()->status)->toBe(OrderStatus::REFUNDED)
        ->and($scenario->variant->fresh()->stock_quantity)->toBe(25);
});

it('refuses a refund bigger than what was paid', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect(fn () => app(OrderService::class)->refund($order, 100.00))
        ->toThrow(SivarcartCoreException::class);
});

it('refuses a refund of no money', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect(fn () => app(OrderService::class)->refund($order, 0))
        ->toThrow(SivarcartCoreException::class);
});

it('prepares an order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $prepared = app(OrderService::class)->prepare($order);

    expect($prepared->status)->toBe(OrderStatus::PROCESSING);
});

it('ships an order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $shipped = app(OrderService::class)->ship($order);

    expect($shipped->status)->toBe(OrderStatus::SHIPPED)
        ->and($shipped->fulfilled_at)->not->toBeNull();
});

it('delivers an order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    $delivered = app(OrderService::class)->deliver($order);

    expect($delivered->status)->toBe(OrderStatus::DELIVERED);
});

it('registers the return of an order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    $returned = app(OrderService::class)->markReturned($order);

    expect($returned->status)->toBe(OrderStatus::RETURNED);
});

it('does not move the stock when the order is shipped', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(OrderService::class)->ship($order);

    $variant = $scenario->variant->fresh();

    expect($variant->stock_quantity)->toBe(25)
        ->and($variant->reserved_quantity)->toBe(1);
});
