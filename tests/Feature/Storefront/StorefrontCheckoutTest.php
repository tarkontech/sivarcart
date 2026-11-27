<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Department;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\PaymentMethod;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ShippingMethod;

it('shows the screen of the checkout', function () {
    shopper();

    $this->get('/tienda/checkout')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Checkout/Index')
            ->has('cart')
            ->has('payment_methods')
            ->has('shipping_methods')
            ->has('departments')
            ->has('buyer'),
        );
});

it('shows the métodos de pago y de envío disponibles', function () {
    shopper();

    PaymentMethod::factory()->create(['name' => 'Tarjeta']);
    ShippingMethod::factory()->create(['name' => 'Entrega']);
    Department::factory()->create(['name' => 'San Salvador']);

    $this->get('/tienda/checkout')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.name', 'Tarjeta')
            ->has('shipping_methods', 1)
            ->where('shipping_methods.0.name', 'Entrega')
            ->has('departments', 1)
            ->where('departments.0.name', 'San Salvador'),
        );
});

it('places the order of the cart', function () {
    $customer = shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
        'customer_email' => $customer->email,
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

it('sends the shopper to the confirmation of the order', function () {
    $customer = shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $response = $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
        'customer_email' => $customer->email,
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ]);

    $order = Order::query()->firstOrFail();

    $response->assertRedirect(route('sivarcart.storefront.orders.show', [
        'order' => $order->uuid,
    ]));
});

it('says so when the order cannot be placed', function () {
    shopper();

    $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ])->assertSessionHasErrors('checkout');
});

it('does not place the order without the address', function () {
    shopper();

    $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
    ])->assertSessionHasErrors('shipping_address');

    expect(Order::query()->count())->toBe(0);
});

it('quotes the shipping of the cart', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $method = ShippingMethod::factory()->create(['price' => 7.5]);

    $this->postJson('/tienda/checkout/envio', [
        'shipping_method_code' => $method->code,
        'address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ])->assertOk()->assertJsonPath('amount', 7.5);
});

it('shows the orders of the shopper', function () {
    $customer = shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
        'customer_email' => $customer->email,
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ]);

    $this->get('/tienda/pedidos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Orders/Index')
            ->has('orders.data', 1),
        );
});

it('shows the page of an order', function () {
    $customer = shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
        'customer_email' => $customer->email,
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ]);

    $order = Order::query()->firstOrFail();

    $this->get('/tienda/pedidos/'.$order->uuid)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Orders/Show')
            ->where('order.number', $order->order_number)
            ->has('order.items', 1),
        );
});

it('does not show the order of another buyer', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $this->post('/tienda/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ]);

    $order = Order::query()->firstOrFail();

    $this->actingAs(Customer::factory()->create(), 'sivarcart');

    $this->get('/tienda/pedidos/'.$order->uuid)->assertNotFound();
});
