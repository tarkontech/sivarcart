<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\PaymentMethod;
use Tarkontech\SivarCart\Models\PaymentTransaction;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Tests\Support\CheckoutScenario;

beforeEach(function () {
    config([
        'wompi.client_id' => 'client-de-prueba',
        'wompi.client_secret' => 'secreto-de-prueba',
    ]);
});

/**
 * An order that is waiting for a payment of Wompi.
 */
function wompiOrder(): Order
{
    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    return app(OrderService::class)->place($scenario->cart, $scenario->data);
}

/**
 * The body Wompi sends when the payment of an order was approved.
 */
function wompiBody(string $reference, float $amount = 25.50): string
{
    return (string) json_encode([
        'IdTransaccion' => 'txn-1',
        'IdIntentoPago' => 'attempt-1',
        'EnlacePago' => [
            'IdentificadorEnlaceComercio' => $reference,
            'IdEnlace' => 'enlace-1',
        ],
        'Monto' => $amount,
        'EsAprobada' => true,
        'EsProductiva' => true,
        'ResultadoTransaccion' => 'ExitosaAprobada',
        'FormaPagoUtilizada' => 'PagoNormal',
        'FechaTransaccion' => '2026-01-15 10:00:00',
        'CodigoAutorizacion' => '123456',
    ]);
}

/**
 * The signature of a body, the way Wompi computes it.
 */
function wompiSignature(string $body): string
{
    return hash_hmac('sha256', $body, 'secreto-de-prueba');
}

it('registers the route of the webhook', function () {
    expect(Route::has('sivarcart.wompi.webhook'))->toBeTrue();

    $route = Route::getRoutes()->getByName('sivarcart.wompi.webhook');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('webhooks/wompi')
        ->and($route->methods())->toContain('POST')
        ->and($route->gatherMiddleware())->toContain('wompi.webhook');
});

it('marks the order as paid when the webhook arrives', function () {
    $order = wompiOrder();
    $body = wompiBody($order->payments->first()->reference);

    $this->call('POST', '/webhooks/wompi', [], [], [], [
        'HTTP_wompi_hash' => wompiSignature($body),
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and(PaymentTransaction::query()->count())->toBe(1);
});

it('answers with a 200 even when the payment cannot be applied', function () {
    $body = wompiBody('PAGO-DESCONOCIDO');

    $this->call('POST', '/webhooks/wompi', [], [], [], [
        'HTTP_wompi_hash' => wompiSignature($body),
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect(PaymentTransaction::query()->count())->toBe(0);
});

it('does not apply the same webhook twice', function () {
    $order = wompiOrder();
    $body = wompiBody($order->payments->first()->reference);

    foreach ([1, 2] as $attempt) {
        $this->call('POST', '/webhooks/wompi', [], [], [], [
            'HTTP_wompi_hash' => wompiSignature($body),
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();
    }

    expect(PaymentTransaction::query()->count())->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('refuses a webhook with an invalid signature', function () {
    $order = wompiOrder();
    $body = wompiBody($order->payments->first()->reference);

    $this->call('POST', '/webhooks/wompi', [], [], [], [
        'HTTP_wompi_hash' => 'firma-invalida',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and(PaymentTransaction::query()->count())->toBe(0);
});

it('refuses a webhook without a signature', function () {
    $body = wompiBody('PAGO-1');

    $this->call('POST', '/webhooks/wompi', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(400);

    expect(PaymentTransaction::query()->count())->toBe(0);
});

it('does not mark an order as paid when the transaction was only a test', function () {
    $order = wompiOrder();

    $body = (string) json_encode([
        'IdTransaccion' => 'txn-9',
        'IdIntentoPago' => 'attempt-9',
        'EnlacePago' => [
            'IdentificadorEnlaceComercio' => $order->payments->first()->reference,
        ],
        'Monto' => 25.50,
        'EsAprobada' => true,
        'EsProductiva' => false,
        'ResultadoTransaccion' => 'ExitosaAprobada',
    ]);

    $this->call('POST', '/webhooks/wompi', [], [], [], [
        'HTTP_wompi_hash' => wompiSignature($body),
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->payments->first()->fresh()->status->value)->toBe('failed');
});
