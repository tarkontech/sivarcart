<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Models\Coupon;
use SivarcartCore\SivarcartCore\Models\Department;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

/**
 * The address a shopper sends when the order is placed.
 *
 * @return array<string, mixed>
 */
function shippingAddress(): array
{
    return [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'address_line1' => 'Calle 1',
        'municipality' => 'San Salvador',
        'department' => 'San Salvador',
    ];
}

it('asks for a buyer', function () {
    $this->getJson('/api/sivarcart/checkout/options')->assertUnauthorized();
});

it('lists the options of the checkout', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    PaymentMethod::factory()->create([
        'code' => 'contraentrega',
        'instructions' => 'Paga al recibir el pedido.',
        'icon' => 'efectivo.svg',
    ]);

    ShippingMethod::factory()->create([
        'code' => 'estandar',
        'name' => 'Envío estándar',
        'price' => 5,
        'instructions' => 'El pedido llega en tres días.',
    ]);

    Department::factory()->create(['name' => 'San Salvador', 'code' => 'SS']);

    $this->getJson('/api/sivarcart/checkout/options')
        ->assertOk()
        ->assertJsonPath('payment_methods.0.code', 'contraentrega')
        ->assertJsonPath('payment_methods.0.instructions', 'Paga al recibir el pedido.')
        ->assertJsonPath('payment_methods.0.icon', 'efectivo.svg')
        ->assertJsonPath('shipping_methods.0.code', 'estandar')
        ->assertJsonPath('shipping_methods.0.instructions', 'El pedido llega en tres días.')
        ->assertJsonPath('shipping_methods.0.price', 5)
        ->assertJsonPath('departments.0.name', 'San Salvador')
        ->assertJsonPath('store.currency', 'USD');
});

it('quotes the shipping of the cart', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    ShippingMethod::factory()->create(['code' => 'estandar', 'price' => 5]);

    $this->postJson('/api/sivarcart/checkout/shipping-quote', [
        'shipping_method_code' => 'estandar',
        'address' => shippingAddress(),
    ])
        ->assertOk()
        ->assertJsonPath('amount', 5)
        ->assertJsonPath('estimated_days', 2);
});

it('refuses a shipping method that is not available', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/checkout/shipping-quote', [
        'shipping_method_code' => 'inventado',
        'address' => shippingAddress(),
    ])->assertStatus(422);
});

it('places the order of the cart', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'customer_email' => 'ana@example.test',
        'shipping_address' => shippingAddress(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.customer_name', 'Ana Guzmán')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payment_status', 'unpaid')
        ->assertJsonPath('data.items.0.quantity', 1)
        ->assertJsonPath('data.total', 25.5);

    expect(Order::query()->count())->toBe(1);
});

it('places the order with the coupon of the shopper', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    Coupon::factory()->percentage(10)->create(['code' => 'DIEZ']);

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => shippingAddress(),
        'coupon_code' => 'DIEZ',
    ])
        ->assertCreated()
        ->assertJsonPath('data.coupon_code', 'DIEZ')
        ->assertJsonPath('data.discount_total', 2.55);
});

it('does not place the order when the coupon does not exist', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => shippingAddress(),
        'coupon_code' => 'NO-EXISTE',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'coupon_not_applicable');

    expect(Order::query()->count())->toBe(0);
});

it('does not place the order when the cart is empty', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $scenario->cart->items()->delete();

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => shippingAddress(),
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'invalid_cart_state');

    expect(Order::query()->count())->toBe(0);
});

it('does not place the order when the address is not complete', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => ['municipality' => 'San Salvador'],
    ])->assertStatus(422);
});

it('keeps the order when the gateway of the payment fails', function () {
    $scenario = CheckoutScenario::buyer(paymentMethodCode: 'wompi');
    $this->actingAs($scenario->customer, 'sivarcart');

    PaymentMethod::factory()->wompi()->create(['code' => 'wompi']);

    config(['wompi.client_id' => 'cliente', 'wompi.client_secret' => 'secreto']);

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => shippingAddress(),
        'payment_method_code' => 'wompi',
    ])
        ->assertCreated()
        ->assertJsonPath('payment_link', null)
        ->assertJsonPath('data.order_number', Order::query()->firstOrFail()->order_number);

    expect(Order::query()->count())->toBe(1);
});
