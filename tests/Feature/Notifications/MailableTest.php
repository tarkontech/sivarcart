<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Mail\OrderPaidMail;
use SivarcartCore\SivarcartCore\Mail\OrderPlacedMail;
use SivarcartCore\SivarcartCore\Mail\OrderRefundedMail;
use SivarcartCore\SivarcartCore\Mail\PaymentFailedMail;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

/**
 * An order of a guest checkout.
 */
$placedOrder = function (): Order {
    $scenario = CheckoutScenario::guest();

    return app(OrderService::class)->place($scenario->cart, $scenario->data);
};

it('writes the summary of the order to the buyer', function () use ($placedOrder) {
    $order = $placedOrder();

    $mail = new OrderPlacedMail($order, 'ana@example.test');

    $mail->assertSeeInHtml($order->order_number);
    $mail->assertSeeInHtml((string) $order->items->first()->name);
    $mail->assertSeeInHtml(number_format((float) $order->total, 2).' '.$order->currency);
});

it('writes to the address the mailable carries', function () use ($placedOrder) {
    $order = $placedOrder();

    $mail = new OrderPlacedMail($order, 'ana@example.test');

    $mail->assertTo('ana@example.test');
    $mail->assertHasSubject((string) trans('sivarcart-core::messages.order_notifications.order_placed.subject', [
        'number' => $order->order_number,
    ]));
});

it('writes from the address the configuration names', function () use ($placedOrder) {
    config()->set('sivarcart-core.notifications.from', 'hola@tienda.test');

    $mail = new OrderPlacedMail($placedOrder(), 'ana@example.test');

    $mail->assertFrom('hola@tienda.test');
});

it('writes from the address of the application when the package names none', function () use ($placedOrder) {
    $mail = new OrderPlacedMail($placedOrder(), 'ana@example.test');

    expect($mail->envelope()->from)->toBeNull();
});

it('renders the markdown with the components of Laravel', function () use ($placedOrder) {
    $mail = new OrderPlacedMail($placedOrder(), 'ana@example.test');

    $rendered = $mail->render();

    expect($rendered)->toContain('<div class="table"')
        ->and($rendered)->toContain('class="button button-primary"');
});

it('writes how much was refunded', function () use ($placedOrder) {
    $mail = new OrderRefundedMail($placedOrder(), 'ana@example.test', 25.50);

    $mail->assertSeeInHtml('25.50 USD');
});

it('writes why the payment did not go through', function () use ($placedOrder) {
    $mail = new PaymentFailedMail($placedOrder(), 'ana@example.test', 'Fondos insuficientes');

    $mail->assertSeeInHtml('Fondos insuficientes');
});

it('writes how much the order cost', function () use ($placedOrder) {
    $order = $placedOrder();

    $mail = new OrderPaidMail($order, 'ana@example.test');

    $mail->assertSeeInHtml(number_format((float) $order->total, 2).' '.$order->currency);
});

it('lets the merchant rewrite the template', function () use ($placedOrder) {
    $published = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sivarcart-core-published'.DIRECTORY_SEPARATOR.Str::uuid()->toString();

    File::ensureDirectoryExists($published.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'sivarcart-core'.DIRECTORY_SEPARATOR.'mail');

    File::put(
        $published.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'sivarcart-core'.DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'order-placed.blade.php',
        '<x-mail::message>El correo que escribio el comerciante.</x-mail::message>',
    );

    app('view')->getFinder()->prependNamespace(
        'sivarcart-core',
        $published.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'sivarcart-core',
    );

    try {
        $mail = new OrderPlacedMail($placedOrder(), 'ana@example.test');

        $mail->assertSeeInHtml('El correo que escribio el comerciante.');
        $mail->assertDontSeeInHtml((string) trans('sivarcart-core::messages.order_notifications.order_placed.cta'));
    } finally {
        File::deleteDirectory($published);
    }
});
