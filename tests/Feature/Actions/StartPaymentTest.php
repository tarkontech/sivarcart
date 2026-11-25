<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SivarcartCore\SivarcartCore\Actions\StartPayment;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Exceptions\PaymentGatewayFailed;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Payments\PaymentGatewayManager;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;
use SivarcartCore\SivarcartCore\Tests\Support\FakePaymentGateway;

/**
 * An order that is waiting for a payment link.
 */
$orderWaitingForPayment = function (?FakePaymentGateway $gateway = null): Order {
    if ($gateway !== null) {
        app(PaymentGatewayManager::class)->extend('fake', fn (): FakePaymentGateway => $gateway);
    }

    $method = PaymentMethod::factory()->create(['code' => 'wompi', 'driver' => 'fake']);
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    return app(OrderService::class)->place($scenario->cart, $scenario->data);
};

it('asks the gateway for the payment link of the order', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    $result = app(StartPayment::class)->execute($order);

    expect($gateway->calls)->toBe(1)
        ->and($result->url)->toBe('https://pay.example.test/'.$order->payments->first()->reference)
        ->and($result->reference)->toBe('enlace-'.$order->payments->first()->reference);
});

it('keeps the link of the payment in the order', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    app(StartPayment::class)->execute($order);

    $payment = $order->payments->first()->fresh();

    expect($payment->gateway_reference)->toBe('enlace-'.$payment->reference)
        ->and($payment->meta['payment_link_url'])->toBe('https://pay.example.test/'.$payment->reference)
        ->and($payment->attempt_count)->toBe(1);
});

it('does not ask the gateway twice for the same order', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    $first = app(StartPayment::class)->execute($order);
    $second = app(StartPayment::class)->execute($order);

    expect($gateway->calls)->toBe(1)
        ->and($second->url)->toBe($first->url)
        ->and($order->payments->first()->fresh()->attempt_count)->toBe(1);
});

it('fails when the gateway refuses to work', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $gateway->failing = true;
    $order = $orderWaitingForPayment($gateway);

    expect(fn () => app(StartPayment::class)->execute($order))
        ->toThrow(PaymentGatewayFailed::class);
});

it('keeps the order and its reservation when the gateway fails', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $gateway->failing = true;
    $order = $orderWaitingForPayment($gateway);

    try {
        app(StartPayment::class)->execute($order);
    } catch (PaymentGatewayFailed) {
        // The shopper can pay later.
    }

    expect($order->fresh()->status->value)->toBe('pending')
        ->and($order->payments->first()->fresh()->attempt_count)->toBe(1);
});

it('leaves the payment unpaid when the gateway fails', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $gateway->failing = true;
    $order = $orderWaitingForPayment($gateway);

    try {
        app(StartPayment::class)->execute($order);
    } catch (PaymentGatewayFailed) {
        // The shopper can ask for the link again.
    }

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::UNPAID);
});

it('leaves the payment of the order pending when the link is created', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    app(StartPayment::class)->execute($order);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('leaves the payment of the order pending when the link already exists', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    app(StartPayment::class)->execute($order);

    $order->update(['payment_status' => PaymentStatus::UNPAID]);

    app(StartPayment::class)->execute($order->fresh());

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('records the change of the payment in the history of the order', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    app(StartPayment::class)->execute($order);

    $history = $order->fresh()->statusHistories()->forAxis(OrderHistoryAxis::PAYMENT)->first();

    expect($history->payment_status)->toBe(PaymentStatus::PENDING)
        ->and($history->previous_payment_status)->toBe(PaymentStatus::UNPAID);
});

it('refuses an order without a pending payment', function () use ($orderWaitingForPayment) {
    $gateway = new FakePaymentGateway;
    $order = $orderWaitingForPayment($gateway);

    $order->payments->first()->update(['status' => 'cancelled']);

    expect(fn () => app(StartPayment::class)->execute($order->fresh()))
        ->toThrow(SivarcartCoreException::class);
});

it('creates the payment link with Wompi', function () {
    config([
        'wompi.client_id' => 'client-de-prueba',
        'wompi.client_secret' => 'secreto-de-prueba',
        'wompi.token_cache.store' => 'array',
    ]);

    Http::fake([
        'id.wompi.sv/connect/token' => Http::response([
            'access_token' => 'token-de-prueba',
            'expires_in' => 3600,
        ]),
        'api.wompi.sv/EnlacePago' => Http::response([
            'idEnlace' => '9999',
            'urlEnlace' => 'https://pay.wompi.sv/l/9999',
        ], 201),
    ]);

    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);
    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);
    $payment = $order->payments->first();

    $result = app(StartPayment::class)->execute($order);

    expect($result->reference)->toBe('9999')
        ->and($result->url)->toBe('https://pay.wompi.sv/l/9999')
        ->and($payment->fresh()->gateway_reference)->toBe('9999');

    Http::assertSent(function (Request $request) use ($payment): bool {
        return str_contains($request->url(), 'EnlacePago')
            && $request['identificadorEnlaceComercio'] === $payment->reference
            && $request['monto'] === 25.5;
    });
});
