<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\PaymentMethod;
use Tarkontech\SivarCart\Payments\PaymentGatewayManager;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Tests\Support\CheckoutScenario;
use Tarkontech\SivarCart\Tests\Support\FakePaymentGateway;

/**
 * An order of a buyer that is waiting for a payment link.
 */
function orderWaitingForPayment(?FakePaymentGateway $gateway = null): Order
{
    if ($gateway !== null) {
        app(PaymentGatewayManager::class)->extend('fake', fn (): FakePaymentGateway => $gateway);
    }

    $method = PaymentMethod::factory()->create(['code' => 'wompi', 'driver' => 'fake']);

    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    return app(OrderService::class)->place($scenario->cart, $scenario->data);
}

it('asks for a buyer', function () {
    $this->getJson('/api/sivarcart/orders')->assertUnauthorized();
});

it('lists the orders of the buyer', function () {
    $scenario = CheckoutScenario::buyer();
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $this->actingAs($scenario->customer, 'sivarcart');

    $this->getJson('/api/sivarcart/orders')
        ->assertOk()
        ->assertJsonPath('data.0.id', $order->uuid)
        ->assertJsonPath('data.0.order_number', $order->order_number)
        ->assertJsonPath('data.0.status', 'pending');
});

it('does not list the orders of another buyer', function () {
    $scenario = CheckoutScenario::buyer();
    app(OrderService::class)->place($scenario->cart, $scenario->data);

    $this->actingAs(Customer::factory()->create(), 'sivarcart');

    $this->getJson('/api/sivarcart/orders')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('shows an order of the buyer', function () {
    $scenario = CheckoutScenario::buyer();
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $this->actingAs($scenario->customer, 'sivarcart');

    $this->getJson('/api/sivarcart/orders/'.$order->uuid)
        ->assertOk()
        ->assertJsonPath('data.id', $order->uuid)
        ->assertJsonPath('data.items.0.name', $scenario->variant->name)
        ->assertJsonPath('data.payment', null);
});

it('shows the payment of an order', function () {
    $method = PaymentMethod::factory()->create(['code' => 'contraentrega']);

    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $this->actingAs($scenario->customer, 'sivarcart');

    $this->getJson('/api/sivarcart/orders/'.$order->uuid)
        ->assertOk()
        ->assertJsonPath('data.payment.status', 'pending')
        ->assertJsonPath('data.payment.method_code', 'contraentrega')
        ->assertJsonPath('data.payment.amount', 25.5);
});

it('does not show an order of another buyer', function () {
    $scenario = CheckoutScenario::buyer();
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $this->actingAs(Customer::factory()->create(), 'sivarcart');

    $this->getJson('/api/sivarcart/orders/'.$order->uuid)->assertNotFound();
});

it('gives the link to pay an order', function () {
    $gateway = new FakePaymentGateway;
    $order = orderWaitingForPayment($gateway);

    $this->actingAs($order->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/orders/'.$order->uuid.'/payment')
        ->assertOk()
        ->assertJsonPath('url', 'https://pay.example.test/'.$order->payments->first()->reference)
        ->assertJsonPath('reference', 'enlace-'.$order->payments->first()->reference);

    expect($gateway->calls)->toBe(1);
});

it('answers with a 502 when the gateway of the payment fails', function () {
    $gateway = new FakePaymentGateway;
    $gateway->failing = true;

    $order = orderWaitingForPayment($gateway);

    $this->actingAs($order->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/orders/'.$order->uuid.'/payment')
        ->assertStatus(502)
        ->assertJsonPath('code', 'payment_gateway_failed');
});

it('does not give a link to pay an order that does not wait for a payment', function () {
    $scenario = CheckoutScenario::buyer();
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $this->actingAs($scenario->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/orders/'.$order->uuid.'/payment')
        ->assertStatus(422)
        ->assertJsonPath('code', 'sivar_cart_exception');
});
