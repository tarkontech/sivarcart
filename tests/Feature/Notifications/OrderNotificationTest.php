<?php

declare(strict_types=1);

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Support\Facades\Notification;
use Tarkontech\SivarCart\Actions\CancelOrder;
use Tarkontech\SivarCart\Actions\DeliverOrder;
use Tarkontech\SivarCart\Actions\HandleWompiPayment;
use Tarkontech\SivarCart\Actions\MarkOrderAsPaid;
use Tarkontech\SivarCart\Actions\PlaceOrder;
use Tarkontech\SivarCart\Actions\RefundOrder;
use Tarkontech\SivarCart\Actions\ShipOrder;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\PaymentMethod;
use Tarkontech\SivarCart\Notifications\OrderCancelledNotification;
use Tarkontech\SivarCart\Notifications\OrderDeliveredNotification;
use Tarkontech\SivarCart\Notifications\OrderPaidNotification;
use Tarkontech\SivarCart\Notifications\OrderPlacedNotification;
use Tarkontech\SivarCart\Notifications\OrderRefundedNotification;
use Tarkontech\SivarCart\Notifications\OrderShippedNotification;
use Tarkontech\SivarCart\Notifications\PaymentFailedNotification;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Tests\Support\CheckoutScenario;
use Tarkontech\SivarCart\Tests\Support\WompiPayload;

/**
 * An order that was placed and is waiting for its payment.
 */
$placedOrder = function (): Order {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    return app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);
};

it('tells the buyer that the order was placed', function () {
    Notification::fake();

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertSentOnDemand(
        OrderPlacedNotification::class,
        fn (OrderPlacedNotification $notification): bool => $notification->order->is($order)
            && $notification->recipient === $order->customer_email,
    );
});

it('tells the buyer that the order was paid', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(MarkOrderAsPaid::class)->execute($order);

    Notification::assertSentOnDemand(
        OrderPaidNotification::class,
        fn (OrderPaidNotification $notification): bool => $notification->order->is($order)
            && $notification->recipient === $order->customer_email,
    );
});

it('tells the buyer that the order was shipped', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(ShipOrder::class)->execute($order);

    Notification::assertSentOnDemand(
        OrderShippedNotification::class,
        fn (OrderShippedNotification $notification): bool => $notification->order->is($order),
    );
});

it('tells the buyer that the order was delivered', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(ShipOrder::class)->execute($order);
    app(DeliverOrder::class)->execute($order);

    Notification::assertSentOnDemand(
        OrderDeliveredNotification::class,
        fn (OrderDeliveredNotification $notification): bool => $notification->order->is($order),
    );
});

it('tells the buyer that the order was cancelled', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(CancelOrder::class)->execute($order, 'El comprador se arrepintió');

    Notification::assertSentOnDemand(
        OrderCancelledNotification::class,
        fn (OrderCancelledNotification $notification): bool => $notification->order->is($order),
    );
});

it('tells the buyer that the order was refunded', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(MarkOrderAsPaid::class)->execute($order);
    app(RefundOrder::class)->execute($order, 25.50, 'Producto devuelto');

    Notification::assertSentOnDemand(
        OrderRefundedNotification::class,
        fn (OrderRefundedNotification $notification): bool => $notification->order->is($order)
            && $notification->amount === 25.50,
    );
});

it('tells the buyer that the payment did not go through', function () {
    Notification::fake();

    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(HandleWompiPayment::class)->execute(
        WompiPayload::rejected($order->payments->first()->reference),
    );

    Notification::assertSentOnDemand(
        PaymentFailedNotification::class,
        fn (PaymentFailedNotification $notification): bool => $notification->order->is($order),
    );
});

it('survives the trip through the queue', function () {
    Notification::fake()->serializeAndRestore();

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertSentOnDemand(
        OrderPlacedNotification::class,
        fn (OrderPlacedNotification $notification): bool => $notification->order->is($order)
            && $notification->recipient === $order->customer_email,
    );
});

it('writes to nobody when the order has no address', function () {
    Notification::fake();

    $scenario = CheckoutScenario::forCustomer(Customer::factory()->guest()->create());

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertNothingSent();
});

it('keeps the order when the mailer is down', function () {
    $this->app->bind(MailFactory::class, fn (): MailFactory => new class implements MailFactory
    {
        public function mailer($name = null)
        {
            throw new RuntimeException('The mailer is down.');
        }
    });

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    expect($order->fresh()->order_number)->not->toBeEmpty()
        ->and($order->fresh()->items)->toHaveCount(1);
});
